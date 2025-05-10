<?php
require_once 'connection.php';
require_once 'session.php';

echo "<h1>Database Structure Fix</h1>";

try {
    // Check if the 'updated_at' column exists in the g2g_pending_approvals table
    $check_sql = "SHOW COLUMNS FROM pending_g2g_approvals LIKE 'updated_at'";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute();
    $column_exists = $check_stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add the updated_at column if it doesn't exist
        $add_column_sql = "ALTER TABLE pending_g2g_approvals ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $add_column_stmt = $pdo->prepare($add_column_sql);
        $add_column_stmt->execute();
        
        echo "<div style='padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px;'>";
        echo "<strong>Success!</strong> Added 'updated_at' column to pending_g2g_approvals table.";
        echo "</div>";
    } else {
        echo "<div style='padding: 15px; background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; border-radius: 4px; margin-bottom: 15px;'>";
        echo "<strong>Info:</strong> The 'updated_at' column already exists in the pending_g2g_approvals table.";
        echo "</div>";
    }
    
    // Check other tables as well
    $tables_to_check = ['gov_to_gov'];
    
    foreach ($tables_to_check as $table) {
        $check_sql = "SHOW COLUMNS FROM $table LIKE 'updated_at'";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute();
        $column_exists = $check_stmt->rowCount() > 0;
        
        if (!$column_exists) {
            // Add the updated_at column if it doesn't exist
            $add_column_sql = "ALTER TABLE $table ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            $add_column_stmt = $pdo->prepare($add_column_sql);
            $add_column_stmt->execute();
            
            echo "<div style='padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px;'>";
            echo "<strong>Success!</strong> Added 'updated_at' column to $table table.";
            echo "</div>";
        } else {
            echo "<div style='padding: 15px; background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; border-radius: 4px; margin-bottom: 15px;'>";
            echo "<strong>Info:</strong> The 'updated_at' column already exists in the $table table.";
            echo "</div>";
        }
    }
    
    echo "<p><a href='gov_to_gov.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Return to Gov-to-Gov page</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 15px;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
