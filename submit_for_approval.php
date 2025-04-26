<?php
include 'session.php';
require_once 'connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logDebug($message) {
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Script started");

// Check if record ID is provided
if (!isset($_GET['id']) && !isset($_POST['id'])) {
    $_SESSION['error_message'] = "Missing record information.";
    logDebug("No ID provided");
    header('Location: direct_hire.php');
    exit();
}

// Get ID from either GET or POST
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['id'];
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
    
    // Check if direct_hire_clearance_approvals table exists
    try {
        // Try to insert into the approvals table
        $user_id = $_SESSION['user_id'] ?? null;
        logDebug("Attempting to insert into direct_hire_clearance_approvals table. User ID: " . ($user_id ?? 'null'));
        
        // First check if there's already a pending approval for this record
        $check_approval = $pdo->prepare("SELECT id FROM direct_hire_clearance_approvals WHERE direct_hire_id = ? AND status = 'pending' AND record_type = 'direct_hire'");
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
            $approval_stmt = $pdo->prepare("INSERT INTO direct_hire_clearance_approvals (direct_hire_id, record_type, status, submitted_by) VALUES (?, 'direct_hire', 'pending', ?)");
            $approval_stmt->execute([$record_id, $user_id]);
            logDebug("New approval inserted, ID: " . $pdo->lastInsertId());
        }
    } catch (PDOException $e) {
        // If table doesn't exist, log it but continue
        logDebug("Error with approvals table: " . $e->getMessage());
        $_SESSION['warning_message'] = "Warning: Approvals table not found. Please run the update_tables.sql script.";
    }
    
    // Commit the transaction
    $pdo->commit();
    logDebug("Transaction committed");

    $_SESSION['success_message'] = "Record has been submitted for approval to the Regional Director.";
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
