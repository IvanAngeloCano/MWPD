<?php
// Script to encrypt all plaintext passwords in the database
require_once 'connection.php';

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>Password Encryption</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        h1 { color: #333; }
        .success { color: green; background: #f0fff0; padding: 10px; border-left: 4px solid green; margin: 10px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-left: 4px solid #721c24; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-left: 4px solid #0c5460; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>Password Encryption</h1>';

try {
    // Check if users table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
    
    if ($tableCheck == 0) {
        echo "<div class='error'>Users table not found.</div>";
    } else {
        // Get all users with plaintext passwords (not starting with $2y$)
        $stmt = $pdo->query("SELECT id, username, password FROM users WHERE password NOT LIKE '$2y$%'");
        $plainTextUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = count($plainTextUsers);
        if ($count > 0) {
            echo "<div class='info'>Found {$count} users with plaintext passwords. Converting to secure hashes...</div>";
            
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $successCount = 0;
            
            foreach ($plainTextUsers as $user) {
                // Hash the plaintext password
                $secureHash = password_hash($user['password'], PASSWORD_BCRYPT);
                
                // Update the database
                $updateStmt->execute([$secureHash, $user['id']]);
                
                echo "<div class='success'>Encrypted password for user: {$user['username']}</div>";
                $successCount++;
            }
            
            echo "<div class='success'><strong>Successfully encrypted {$successCount} out of {$count} passwords.</strong></div>";
        } else {
            // Check if there are any users with already encrypted passwords
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE password LIKE '$2y$%'");
            $encryptedCount = $stmt->fetchColumn();
            
            if ($encryptedCount > 0) {
                echo "<div class='info'>All passwords are already encrypted. Found {$encryptedCount} users with secure password hashes.</div>";
            } else {
                echo "<div class='error'>No users found in the database.</div>";
            }
        }
        
        // Verify the login.php file has the correct password verification code
        if (file_exists('login.php')) {
            $loginContent = file_get_contents('login.php');
            
            if (strpos($loginContent, 'password_verify') !== false) {
                echo "<div class='success'>Your login.php file is correctly set up to verify hashed passwords.</div>";
            } else {
                echo "<div class='error'>Warning: Your login.php file may not be properly set up to verify hashed passwords.</div>";
            }
        } else {
            echo "<div class='error'>Warning: Could not find login.php file to verify password handling.</div>";
        }
    }
    
    echo "<p><a href='login.php'>Return to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
