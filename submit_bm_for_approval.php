<?php
include 'session.php';
require_once 'connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logDebug($message) {
    file_put_contents('bm_debug_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Script started");

// Check if record ID is provided
if (!isset($_GET['bmid']) && !isset($_POST['bmid'])) {
    $_SESSION['error_message'] = "Missing record information.";
    logDebug("No ID provided");
    header('Location: balik_manggagawa.php');
    exit();
}

// Get ID from either GET or POST
$record_id = isset($_GET['bmid']) ? (int)$_GET['bmid'] : (int)$_POST['bmid'];
logDebug("Record ID: " . $record_id);

try {
    // Start transaction
    $pdo->beginTransaction();
    logDebug("Transaction started");
    
    // Verify the record exists
    $check_stmt = $pdo->prepare("SELECT * FROM bm WHERE bmid = ?");
    $check_stmt->execute([$record_id]);
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        logDebug("Record not found for ID: " . $record_id);
        throw new Exception("Record not found.");
    }
    
    logDebug("Record found: " . json_encode($record));
    
    // Check if status column exists in bm table
    try {
        $check_column = $pdo->prepare("SHOW COLUMNS FROM bm LIKE 'status'");
        $check_column->execute();
        $status_exists = $check_column->rowCount() > 0;
        
        if (!$status_exists) {
            // Add status column if it doesn't exist
            $pdo->exec("ALTER TABLE bm ADD COLUMN status VARCHAR(20) DEFAULT 'draft'");
            logDebug("Added status column to bm table");
        }
        
        // Update the record status to Pending
        $update_sql = "UPDATE bm SET status = 'Pending' WHERE bmid = ?";
        logDebug("SQL: " . $update_sql);
        
        $update_stmt = $pdo->prepare($update_sql);
        $result = $update_stmt->execute([$record_id]);
        logDebug("Update result: " . ($result ? 'true' : 'false') . ", Rows affected: " . $update_stmt->rowCount());
    } catch (PDOException $e) {
        logDebug("Error updating status: " . $e->getMessage());
        // Continue with the process even if status update fails
    }
    
    // Check if bm_approvals table exists, if not create it
    try {
        // Try to insert into the approvals table
        $user_id = $_SESSION['user_id'] ?? null;
        logDebug("Attempting to insert into bm_approvals table. User ID: " . ($user_id ?? 'null'));
        
        // First check if there's already a pending approval for this record
        $check_approval = $pdo->prepare("SELECT id FROM direct_hire_clearance_approvals WHERE direct_hire_id = ? AND status = 'pending' AND record_type = 'balik_manggagawa'");
        $check_approval->execute([$record_id]);
        $existing_approval = $check_approval->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_approval) {
            // Update existing approval
            logDebug("Existing approval found (ID: {$existing_approval['id']}), updating...");
            $approval_stmt = $pdo->prepare("UPDATE direct_hire_clearance_approvals SET updated_at = NOW() WHERE id = ?");
            $approval_stmt->execute([$existing_approval['id']]);
            $approval_id = $existing_approval['id'];
        } else {
            // Check if the approvals table exists
            try {
                $pdo->query("SELECT 1 FROM direct_hire_clearance_approvals LIMIT 1");
            } catch (PDOException $e) {
                // Create the table if it doesn't exist
                $create_table_sql = "CREATE TABLE IF NOT EXISTS direct_hire_clearance_approvals (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    direct_hire_id INT NOT NULL,
                    record_type VARCHAR(50) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending',
                    submitted_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    approved_by INT,
                    approval_date TIMESTAMP NULL,
                    remarks TEXT
                )";
                $pdo->exec($create_table_sql);
                logDebug("Created direct_hire_clearance_approvals table");
            }
            
            // Insert new approval
            logDebug("No existing approval found, creating new one...");
            $approval_stmt = $pdo->prepare("INSERT INTO direct_hire_clearance_approvals (direct_hire_id, record_type, status, submitted_by) VALUES (?, 'balik_manggagawa', 'pending', ?)");
            $approval_stmt->execute([$record_id, $user_id]);
            $approval_id = $pdo->lastInsertId();
            logDebug("New approval inserted, ID: " . $approval_id);
            
            // Send notification to Regional Directors
            try {
                require_once 'notifications.php';
                
                // Ensure notifications table exists
                ensureNotificationsTableExists();
                
                // Get the name of the person
                $name_stmt = $pdo->prepare("SELECT CONCAT(given_name, ' ', last_name) as full_name FROM bm WHERE bmid = ?");
                $name_stmt->execute([$record_id]);
                $name = $name_stmt->fetchColumn();
                
                if (empty($name)) {
                    $name = "Balik Manggagawa Record #$record_id";
                }
                
                logDebug("Sending notification for record name: $name");
                
                // Get all Regional Directors
                $rd_stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(role) LIKE '%regional director%'");
                $rd_stmt->execute();
                $rd_users = $rd_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($rd_users)) {
                    logDebug("Found " . count($rd_users) . " Regional Directors to notify");
                    
                    // Get submitter name
                    $submitter_stmt = $pdo->prepare("SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?");
                    $submitter_stmt->execute([$user_id]);
                    $submitter = $submitter_stmt->fetchColumn();
                    
                    if (empty($submitter)) {
                        $submitter = "User #$user_id";
                    }
                    
                    // Create notification message
                    $message = "New Balik Manggagawa approval request for $name submitted by $submitter";
                    $link = "approval_view_simple.php?tab=balik_manggagawa&id=" . $approval_id;
                    
                    // Send notification to each Regional Director
                    $success = true;
                    foreach ($rd_users as $rd_user_id) {
                        logDebug("Sending notification to Regional Director (ID: $rd_user_id)");
                        if (!addNotification($rd_user_id, $message, $record_id, 'balik_manggagawa', $link)) {
                            logDebug("Failed to send notification to Regional Director (ID: $rd_user_id)");
                            $success = false;
                        }
                    }
                    
                    logDebug("Notification to Regional Directors " . ($success ? "sent successfully" : "failed"));
                } else {
                    logDebug("No Regional Directors found in the system");
                }
            } catch (Exception $e) {
                logDebug("Error sending notification to Regional Directors: " . $e->getMessage());
                // Continue with the process even if notification fails
            }
        }
    } catch (PDOException $e) {
        // If table doesn't exist, log it but continue
        logDebug("Error with approvals table: " . $e->getMessage());
        $_SESSION['warning_message'] = "Warning: Approvals table not found. Please run the update_tables.sql script.";
    }
    
    // Commit the transaction
    $pdo->commit();
    logDebug("Transaction committed");

    $_SESSION['success_message'] = "Balik Manggagawa record has been submitted for approval to the Regional Director.";
    logDebug("Success message set, redirecting to balik_manggagawa_edit.php?bmid=" . $record_id);
    
    header("Location: balik_manggagawa_edit.php?bmid=$record_id");
    exit();

} catch (Exception $e) {
    // Rollback the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    logDebug("Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: balik_manggagawa_edit.php?bmid=$record_id");
    exit();
}
?>
