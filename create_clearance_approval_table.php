<?php
require_once 'connection.php';

try {
    // Create table for clearance approvals
    $pdo->exec("CREATE TABLE IF NOT EXISTS direct_hire_clearance_approvals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        direct_hire_id INT NOT NULL,
        document_id INT NOT NULL,
        status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
        comments TEXT,
        submitted_by INT,
        approved_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (direct_hire_id) REFERENCES direct_hire(id) ON DELETE CASCADE,
        FOREIGN KEY (document_id) REFERENCES direct_hire_documents(id) ON DELETE CASCADE
    )");
    
    echo "Clearance approval table created successfully!";
    
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
