<?php
include 'session.php';
require_once 'connection.php';
require_once 'alert_system.php'; // Using our alert system

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logDebug($message) {
    file_put_contents('approval_debug_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Process approval script started");

// Ensure only regional directors can access this page
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    $_SESSION['error_message'] = "Access denied. Only Regional Directors can process approvals.";
    logDebug("Access denied - not a regional director");
    header('Location: index.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    logDebug("Invalid request method");
    header('Location: approval_view_simple.php');
    exit();
}

// Get form data
$action = isset($_POST['action']) ? $_POST['action'] : '';
$approval_id = isset($_POST['approval_id']) ? (int)$_POST['approval_id'] : 0;
$record_id = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

logDebug("Form data: action=$action, record_id=$record_id, approval_id=$approval_id");

// Validate data
if ($approval_id <= 0 || $record_id <= 0) {
    $_SESSION['error_message'] = "Invalid record or approval ID.";
    logDebug("Invalid record or approval ID");
    header('Location: approval_view_simple.php');
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    logDebug("Transaction started");
    
    // Determine the status based on the action
    $status = ($action === 'approve') ? 'approved' : 'denied';
    logDebug("Status determined: $status");
    
    // Get record details
    $record_stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
    $record_stmt->execute([$record_id]);
    $record = $record_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception("Record not found");
    }
    
    // Get the name of the record
    $name = $record['name'] ?? 'Record';
    
    // Update the approval record
    $approval_stmt = $pdo->prepare("
        UPDATE clearance_approvals 
        SET status = ?, comments = ?, approved_by = ?, approved_at = NOW() 
        WHERE id = ?
    ");
    $approval_stmt->execute([$status, $comments, $_SESSION['user_id'], $approval_id]);
    logDebug("Approval record updated");
    
    // Update the direct_hire record
    $update_stmt = $pdo->prepare("
        UPDATE direct_hire 
        SET status = ?, note = CONCAT(IFNULL(note, ''), ?) 
        WHERE id = ?
    ");
    
    // Format the note with a clear prefix
    $date_str = date('Y-m-d H:i:s');
    $approver_name = $_SESSION['name'] ?? 'Regional Director';
    
    if ($status === 'approved') {
        $formatted_note = "[APPROVAL NOTE - $date_str by $approver_name]\n";
        if (!empty($comments)) {
            $formatted_note .= $comments . "\n\n";
        } else {
            $formatted_note .= "Approved without additional comments.\n\n";
        }
    } else {
        $formatted_note = "[DENIAL NOTE - $date_str by $approver_name]\n";
        if (!empty($comments)) {
            $formatted_note .= $comments . "\n\n";
        } else {
            $formatted_note .= "Denied without additional comments.\n\n";
        }
    }
    
    $update_stmt->execute([$status, $formatted_note, $record_id]);
    logDebug("Direct hire record updated");
    
    // Get the user who submitted the record for approval
    $submitter_stmt = $pdo->prepare("
        SELECT submitted_by FROM clearance_approvals WHERE id = ?
    ");
    $submitter_stmt->execute([$approval_id]);
    $submitted_by = $submitter_stmt->fetchColumn();
    
    // Send notification to the submitter
    if ($submitted_by) {
        logDebug("Found submitter ID: $submitted_by");
        
        $approver_name = $_SESSION['name'] ?? 'Regional Director';
        
        if ($status === 'approved') {
            $notification_message = "Your record for '$name' has been APPROVED by $approver_name";
            $link = "direct_hire_view.php?id=$record_id";
            $alert_type = "success";
        } else {
            $notification_message = "Your record for '$name' has been DENIED by $approver_name";
            $link = "direct_hire_view.php?id=$record_id";
            $alert_type = "danger";
        }
        
        // Add notification using our session-based system
        addUserNotification($submitted_by, $notification_message, $alert_type, $link);
        logDebug("Session notification sent to user ID: $submitted_by");
        
        // Also add a global alert that will be shown to the user on their next page load
        addAlert("Status update: " . $notification_message, $alert_type, $link);
        
        // Add to simple database notification system
        try {
            // Create a simple notification table if it doesn't exist
            $pdo->exec("CREATE TABLE IF NOT EXISTS simple_notifications (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                link VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Add notification to the simple table
            $simpleStmt = $pdo->prepare("INSERT INTO simple_notifications (user_id, message, link) VALUES (?, ?, ?)");
            $simpleResult = $simpleStmt->execute([$submitted_by, $notification_message, $link]);
            
            if ($simpleResult) {
                logDebug("Successfully added simple notification to database");
            } else {
                logDebug("Failed to add simple notification: " . implode(', ', $simpleStmt->errorInfo()));
            }
        } catch (Exception $e) {
            logDebug("Error with simple notification: " . $e->getMessage());
        }
    } else {
        // If submitted_by is not set in the approval record, try to find the user who submitted it
        try {
            $submitter_query = $pdo->prepare("SELECT u.id FROM users u 
                                             JOIN direct_hire dh ON dh.created_by = u.id 
                                             WHERE dh.id = ?");
            $submitter_query->execute([$record_id]);
            $submitter_id = $submitter_query->fetchColumn();
            
            if ($submitter_id) {
                $record_name = $pdo->prepare("SELECT name FROM direct_hire WHERE id = ?");
                $record_name->execute([$record_id]);
                $name = $record_name->fetchColumn() ?: 'Record';
                
                $approver_name = $_SESSION['name'] ?? 'Regional Director';
                
                if ($status === 'approved') {
                    $notification_message = "Your record for '$name' has been APPROVED by $approver_name";
                    $link = "direct_hire_view.php?id=$record_id";
                    $alert_type = "success";
                } else {
                    $notification_message = "Your record for '$name' has been DENIED by $approver_name";
                    $link = "direct_hire_view.php?id=$record_id";
                    $alert_type = "danger";
                }
                
                // Add notification using our session-based system
                addUserNotification($submitter_id, $notification_message, $alert_type, $link);
                logDebug("Session notification sent to record creator (ID: $submitter_id)");
                
                // Also add a global alert that will be shown to the user on their next page load
                addAlert("Status update: " . $notification_message, $alert_type, $link);
                
                // Add to simple database notification system
                try {
                    // Create a simple notification table if it doesn't exist
                    $pdo->exec("CREATE TABLE IF NOT EXISTS simple_notifications (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        message TEXT NOT NULL,
                        link VARCHAR(255) NULL,
                        is_read TINYINT(1) NOT NULL DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    // Add notification to the simple table
                    $simpleStmt = $pdo->prepare("INSERT INTO simple_notifications (user_id, message, link) VALUES (?, ?, ?)");
                    $simpleResult = $simpleStmt->execute([$submitter_id, $notification_message, $link]);
                    
                    if ($simpleResult) {
                        logDebug("Successfully added simple notification to database");
                    } else {
                        logDebug("Failed to add simple notification: " . implode(', ', $simpleStmt->errorInfo()));
                    }
                } catch (Exception $e) {
                    logDebug("Error with simple notification: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            logDebug("Error finding submitter: " . $e->getMessage());
        }
    }
    
    // Commit transaction
    $pdo->commit();
    logDebug("Transaction committed successfully");
    
    // Set success message
    if ($status === 'approved') {
        $_SESSION['success_message'] = "Record has been approved.";
    } else {
        $_SESSION['success_message'] = "Record has been denied.";
    }
    
    // Redirect back to the approval detail view
    header("Location: approval_detail_view.php?id=$approval_id");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    logDebug("Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: approval_detail_view.php?id=$approval_id");
    exit();
}
?>
