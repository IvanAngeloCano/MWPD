<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'connection.php';

// Log function for debugging
function logDebug($message) {
    file_put_contents('fix_approval_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Starting approval table fix script");

try {
    // Check if direct_hire_approvals table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'direct_hire_approvals'")->rowCount() > 0;
    
    if ($tableExists) {
        logDebug("direct_hire_approvals table exists, checking if id is auto_increment");
        
        // Check if id column is auto_increment
        $stmt = $pdo->query("SHOW COLUMNS FROM direct_hire_approvals WHERE Field = 'id'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($column && strpos($column['Extra'], 'auto_increment') === false) {
            logDebug("id column exists but is not auto_increment, fixing...");
            
            // Fix the id column to be auto_increment
            $pdo->exec("ALTER TABLE direct_hire_approvals MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
            echo "<p style='color:green'>Success: The approval table has been fixed. The id column is now auto-incrementing.</p>";
            logDebug("id column has been set to auto_increment");
        } else {
            echo "<p style='color:blue'>Info: The id column is already set to auto_increment. No changes needed.</p>";
            logDebug("id column is already auto_increment");
        }
    } else {
        logDebug("direct_hire_approvals table does not exist, creating it");
        
        // Create the table with auto_increment id
        $sql = "CREATE TABLE direct_hire_approvals (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            direct_hire_id INT NOT NULL,
            status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
            submitted_by INT NULL,
            approved_by INT NULL,
            comments TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (direct_hire_id) REFERENCES direct_hire(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>Success: The approval table has been created with auto-incrementing id.</p>";
        logDebug("direct_hire_approvals table created successfully");
    }
    
    // Check if there are any existing records with id = 0
    $stmt = $pdo->query("SELECT COUNT(*) FROM direct_hire_approvals WHERE id = 0");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        logDebug("Found {$count} records with id = 0, fixing...");
        
        // Create a temporary id column
        $pdo->exec("ALTER TABLE direct_hire_approvals ADD COLUMN temp_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        
        // Drop the original id column
        $pdo->exec("ALTER TABLE direct_hire_approvals DROP COLUMN id");
        
        // Rename temp_id to id
        $pdo->exec("ALTER TABLE direct_hire_approvals CHANGE temp_id id INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        
        echo "<p style='color:green'>Success: Fixed {$count} records with id = 0.</p>";
        logDebug("Fixed records with id = 0");
    }
    
    echo "<p><a href='index.php'>Return to Dashboard</a></p>";
    logDebug("Script completed successfully");
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    logDebug("Error: " . $e->getMessage());
}
?>
