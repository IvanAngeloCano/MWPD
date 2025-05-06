<?php
include 'connection.php';

try {
    // Check users table structure
    $stmt = $conn->query("DESCRIBE users");
    echo "<h3>Users Table Structure</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Check if account_approvals table exists
    $tables = $conn->query("SHOW TABLES LIKE 'account_approvals'")->fetchAll();
    echo "<h3>Account Approvals Table Exists: " . (count($tables) > 0 ? 'Yes' : 'No') . "</h3>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
