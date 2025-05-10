<?php
include 'session.php';
require_once 'connection.php';

// Check if user is admin
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'administrator') {
    $_SESSION['error_message'] = "Access denied. Only administrators can run database updates.";
    header('Location: index.php');
    exit();
}

// Log function for debugging
function logUpdate($message) {
    file_put_contents('db_update_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logUpdate("Starting database update for approval/denial notes");

try {
    // Check if the approval_note column exists
    $checkApprovalNote = $pdo->query("SHOW COLUMNS FROM direct_hire LIKE 'approval_note'");
    $hasApprovalNote = $checkApprovalNote->rowCount() > 0;
    
    // Check if the denial_note column exists
    $checkDenialNote = $pdo->query("SHOW COLUMNS FROM direct_hire LIKE 'denial_note'");
    $hasDenialNote = $checkDenialNote->rowCount() > 0;
    
    // Add columns if they don't exist
    if (!$hasApprovalNote) {
        $pdo->exec("ALTER TABLE direct_hire ADD COLUMN approval_note TEXT NULL AFTER note");
        logUpdate("Added approval_note column to direct_hire table");
        echo "<p style='color:green'>Successfully added approval_note column to direct_hire table</p>";
    } else {
        echo "<p style='color:blue'>approval_note column already exists in direct_hire table</p>";
    }
    
    if (!$hasDenialNote) {
        $pdo->exec("ALTER TABLE direct_hire ADD COLUMN denial_note TEXT NULL AFTER approval_note");
        logUpdate("Added denial_note column to direct_hire table");
        echo "<p style='color:green'>Successfully added denial_note column to direct_hire table</p>";
    } else {
        echo "<p style='color:blue'>denial_note column already exists in direct_hire table</p>";
    }
    
    echo "<p>Database update completed successfully!</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Return to Dashboard</a></p>";
    
} catch (PDOException $e) {
    logUpdate("Error updating database: " . $e->getMessage());
    echo "<p style='color:red'>Error updating database: " . $e->getMessage() . "</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Return to Dashboard</a></p>";
}
?>
