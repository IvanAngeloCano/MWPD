<?php
// Include database connection
require_once 'connection.php';

// Add error handling
try {
    // Check if rejection_reason column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM account_approvals LIKE 'rejection_reason'");
    $stmt->execute();
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        // Add the rejection_reason column
        $pdo->exec("ALTER TABLE account_approvals ADD COLUMN rejection_reason TEXT NULL AFTER notes");
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h3>Success!</h3>
                <p>Successfully added 'rejection_reason' column to the account_approvals table.</p>
                <p><a href='account_dashboard.php?tab=approvals' style='color: #155724; text-decoration: underline;'>Return to Account Dashboard</a></p>
              </div>";
    } else {
        echo "<div style='background-color: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                <h3>Information</h3>
                <p>The 'rejection_reason' column already exists in the account_approvals table.</p>
                <p><a href='account_dashboard.php?tab=approvals' style='color: #004085; text-decoration: underline;'>Return to Account Dashboard</a></p>
              </div>";
    }
} catch (PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>
            <h3>Error</h3>
            <p>Database error: " . $e->getMessage() . "</p>
            <p><a href='account_dashboard.php?tab=approvals' style='color: #721c24; text-decoration: underline;'>Return to Account Dashboard</a></p>
          </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Rejection Reason Column</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Database Maintenance</h1>
</body>
</html>
