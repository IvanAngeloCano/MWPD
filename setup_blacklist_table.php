<?php
// Script to create the blacklist table in the database

// Start a session for database credentials
session_start();

// Include database connection the same way other pages do
require_once 'connection.php';

try {
    // Create blacklist table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        passport_number VARCHAR(50),
        email VARCHAR(100),
        phone VARCHAR(50),
        reason TEXT NOT NULL,
        submitted_by INT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        notes TEXT,
        approved_by INT,
        approved_date DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "<p style='color:green;'>Blacklist table created successfully!</p>";
    
    // Insert some sample data
    $insertSql = "INSERT INTO blacklist 
        (full_name, passport_number, email, phone, reason, submitted_by, status) VALUES 
        ('John Smith', 'P12345678', 'john@example.com', '+1234567890', 'Fraudulent documentation', 1, 'pending'),
        ('Mary Johnson', 'P87654321', 'mary@example.com', '+0987654321', 'Contract violations', 2, 'approved')
    ";
    
    // Only insert if no records exist
    $check = $pdo->query("SELECT COUNT(*) FROM blacklist");
    if ($check->fetchColumn() == 0) {
        $pdo->exec($insertSql);
        echo "<p style='color:green;'>Sample data added to blacklist table!</p>";
    } else {
        echo "<p style='color:blue;'>Table already contains data. No sample data added.</p>";
    }
    
    echo "<p><a href='dashboard.php'>Return to Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error creating blacklist table: " . $e->getMessage() . "</p>";
}
?>
