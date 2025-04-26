<?php
include 'session.php';
require_once 'connection.php';
require_once 'notifications.php'; // Include notification functions

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logDebug($message) {
    file_put_contents('clearance_approval_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Script started - submit_clearance_approval_new.php");

// Check if record ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Missing record information.";
    logDebug("No ID provided");
    header('Location: direct_hire.php');
    exit();
}

$record_id = (int)$_GET['id'];
logDebug("Record ID: " . $record_id);

try {
    // Start transaction
    $pdo->beginTransaction();
    logDebug("Transaction started");
    
    // Verify the record exists
    $check_stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
    $check_stmt->execute([$record_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        logDebug("Record not found for ID: " . $record_id);
        throw new Exception("Record not found.");
    }
    
    logDebug("Record found: " . json_encode($record));
    
    // Update the record status to pending
    $update_sql = "UPDATE direct_hire SET status = 'pending' WHERE id = ?";
    logDebug("SQL: " . $update_sql);
    
    $update_stmt = $pdo->prepare($update_sql);
    $result = $update_stmt->execute([$record_id]);
    logDebug("Update result: " . ($result ? 'true' : 'false') . ", Rows affected: " . $update_stmt->rowCount());
    
    // Check if direct_hire_clearance_approvals table exists, if not create it
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'direct_hire_clearance_approvals'")->rowCount() > 0;
        
        if (!$tableExists) {
            logDebug("Creating direct_hire_clearance_approvals table");
            
            // Create the direct_hire_clearance_approvals table
            $createTableSQL = "CREATE TABLE direct_hire_clearance_approvals (
                id INT(11) NOT NULL AUTO_INCREMENT,
                direct_hire_id INT(11) NOT NULL,
                status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
                submitted_by INT(11) NULL,
                approved_by INT(11) NULL,
                comments TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($createTableSQL);
            logDebug("Table created successfully");
        }
        
        // Check if there's already a pending approval for this record
        $check_approval = $pdo->prepare("SELECT id FROM direct_hire_clearance_approvals WHERE direct_hire_id = ? AND status = 'pending'");
        $check_approval->execute([$record_id]);
        $existing_approval = $check_approval->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_approval) {
            // Update existing approval
            logDebug("Existing approval found (ID: {$existing_approval['id']}), updating...");
            $approval_stmt = $pdo->prepare("UPDATE direct_hire_clearance_approvals SET updated_at = NOW() WHERE id = ?");
            $approval_stmt->execute([$existing_approval['id']]);
        } else {
            // Insert new approval
            logDebug("No existing approval found, creating new one...");
            $user_id = $_SESSION['user_id'] ?? null;
            
            // Make sure we store the submitter ID
            logDebug("Storing submitter user ID: $user_id for record ID: $record_id");
            
            $approval_stmt = $pdo->prepare("INSERT INTO direct_hire_clearance_approvals (direct_hire_id, status, submitted_by) VALUES (?, 'pending', ?)");
            $approval_stmt->execute([$record_id, $user_id]);
            $approval_id = $pdo->lastInsertId();
            logDebug("New approval inserted, ID: " . $approval_id);
            
            // Notify all Regional Directors about the new submission
            if ($user_id && $approval_id) {
                $submitter_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
                logDebug("Notifying Regional Directors about submission from $submitter_name for record $record_id");
                
                try {
                    // Find all Regional Directors
                    $rd_stmt = $pdo->prepare("SELECT id FROM users WHERE role LIKE '%regional director%'");
                    $rd_stmt->execute();
                    $rd_users = $rd_stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($rd_users)) {
                        $name = $record['name'] ?? 'Applicant';
                        $message = "New approval request for $name submitted by $submitter_name";
                        $link = "approval_detail_view.php?id=" . $approval_id;
                        
                        foreach ($rd_users as $rd_user_id) {
                            addNotification($rd_user_id, $message, $record_id, 'direct_hire', $link);
                            logDebug("Notification sent to Regional Director (ID: $rd_user_id)");
                        }
                    } else {
                        logDebug("No Regional Directors found in the system");
                    }
                } catch (Exception $e) {
                    logDebug("Error sending notifications: " . $e->getMessage());
                    // Continue with the process even if notifications fail
                }
            }
        }
    } catch (PDOException $e) {
        logDebug("Error with clearance approvals table: " . $e->getMessage());
        throw new Exception("Database error: " . $e->getMessage());
    }
    
    // Commit the transaction
    $pdo->commit();
    logDebug("Transaction committed successfully");

    $_SESSION['success_message'] = "Clearance has been submitted for approval to the Regional Director.";
    logDebug("Success message set, redirecting to direct_hire_view.php?id=" . $record_id);
    
    header("Location: direct_hire_view.php?id=$record_id");
    exit();

} catch (Exception $e) {
    // Rollback the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    logDebug("Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: direct_hire_view.php?id=$record_id");
    exit();
}
