<?php
session_start();
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in. Please <a href='login.php'>login</a> first.";
    exit();
}

// Display session information
echo "<h2>Session Information</h2>";
echo "<pre>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Full Name: " . ($_SESSION['full_name'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "</pre>";

// Query the database to get the actual user information
try {
    $stmt = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>Database Information</h2>";
        echo "<pre>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Full Name: " . ($user['full_name'] ?? 'NULL') . "<br>";
        echo "Role: " . ($user['role'] ?? 'NULL') . "<br>";
        echo "</pre>";
    } else {
        echo "<p>User not found in database.</p>";
    }
    
    // Check if the columns exist in the users table
    echo "<h2>Database Structure Check</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Columns in users table: " . implode(", ", $columns) . "</p>";
        
        if (!in_array('full_name', $columns)) {
            echo "<p style='color: red;'>The 'full_name' column does not exist in the users table!</p>";
        }
        
        if (!in_array('role', $columns)) {
            echo "<p style='color: red;'>The 'role' column does not exist in the users table!</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error checking table structure: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Add SQL to update user information
echo "<h2>Update User Information</h2>";
echo "<p>Run the following SQL in phpMyAdmin to add the missing columns and update your user information:</p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px;'>";
echo "-- Add columns if they don't exist\n";
echo "ALTER TABLE users\n";
echo "ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) DEFAULT NULL,\n";
echo "ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'User';\n\n";
echo "-- Update your user information\n";
echo "UPDATE users\n";
echo "SET full_name = 'Your Full Name',\n";
echo "    role = 'Your Role'\n";
echo "WHERE id = " . $_SESSION['user_id'] . ";\n";
echo "</pre>";
?>

<p><a href="dashboard.php">Return to Dashboard</a></p>
