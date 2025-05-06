<?php
require_once 'connection.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if status column exists in bm table
    $check_status = $pdo->prepare("SHOW COLUMNS FROM bm LIKE 'status'");
    $check_status->execute();
    $status_exists = $check_status->rowCount() > 0;
    
    if (!$status_exists) {
        // Add status column if it doesn't exist
        $pdo->exec("ALTER TABLE bm ADD COLUMN status ENUM('Pending', 'Approved', 'Declined') DEFAULT 'Pending'");
        echo "Added status column to bm table<br>";
    } else {
        // Modify status column to use ENUM with specific values
        $pdo->exec("ALTER TABLE bm MODIFY COLUMN status ENUM('Pending', 'Approved', 'Declined') DEFAULT 'Pending'");
        echo "Modified status column in bm table to use ENUM values<br>";
    }
    
    // Update existing records to use the standardized values
    $pdo->exec("UPDATE bm SET status = 'Pending' WHERE status IS NULL OR status = 'pending' OR status = 'draft'");
    $pdo->exec("UPDATE bm SET status = 'Approved' WHERE status = 'approved'");
    $pdo->exec("UPDATE bm SET status = 'Declined' WHERE status = 'denied' OR status = 'declined'");
    echo "Updated existing records with standardized status values<br>";
    
    // Check if remarks column exists
    $check_remarks = $pdo->prepare("SHOW COLUMNS FROM bm LIKE 'remarks'");
    $check_remarks->execute();
    $remarks_exists = $check_remarks->rowCount() > 0;
    
    if (!$remarks_exists) {
        // Add remarks column if it doesn't exist
        $pdo->exec("ALTER TABLE bm ADD COLUMN remarks VARCHAR(255) DEFAULT NULL");
        echo "Added remarks column to bm table<br>";
    }
    
    // Commit the transaction
    $pdo->commit();
    echo "Database structure updated successfully!";
    
} catch (PDOException $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
?>
