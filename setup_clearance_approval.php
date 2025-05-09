<?php
// Include database connection
require_once 'connection.php';

try {
    // Create the direct_hire_clearance_approvals table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS direct_hire_clearance_approvals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        direct_hire_id INT NOT NULL,
        document_id INT NOT NULL,
        status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
        comments TEXT,
        submitted_by INT,
        approved_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h2 style='color: #007bff;'>Clearance Approval System Setup</h2>";
    echo "<p style='color: #28a745; font-weight: bold;'>✓ Clearance approval table created successfully!</p>";
    
    // Check if the submit_clearance_approval.php file exists
    if (file_exists('submit_clearance_approval.php')) {
        echo "<p style='color: #28a745; font-weight: bold;'>✓ Submission handler is properly installed.</p>";
    } else {
        echo "<p style='color: #dc3545; font-weight: bold;'>✗ Submission handler file is missing.</p>";
    }
    
    // Provide instructions
    echo "<h3 style='margin-top: 20px;'>How to use the Clearance Approval System:</h3>";
    echo "<ol>";
    echo "<li>When a Direct Hire record is created, a clearance document is automatically generated.</li>";
    echo "<li>Go to the Direct Hire view page to see the generated clearance document.</li>";
    echo "<li>Click the <strong>Submit for Approval</strong> button next to the clearance document to send it to the Regional Director.</li>";
    echo "<li>Regional Directors can view, approve, or deny the clearance documents in their Approvals page.</li>";
    echo "</ol>";
    
    echo "<p><a href='direct_hire.php' style='display: inline-block; background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Direct Hire Records</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h2 style='color: #dc3545;'>Setup Error</h2>";
    echo "<p>Error creating table: " . $e->getMessage() . "</p>";
    echo "</div>";
}
