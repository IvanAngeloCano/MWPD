<?php
// Simple script to encrypt all plaintext passwords in the database
require_once 'connection.php';

// Basic styling
?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Encryption</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Password Encryption</h1>
        
        <?php
        try {
            // Check if users table exists
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
            
            if ($tableCheck == 0) {
                echo "<p class='error'>Users table not found.</p>";
            } else {
                // Get all users with plaintext passwords (not starting with $2y$)
                $stmt = $pdo->query("SELECT id, username, password FROM users WHERE password NOT LIKE '$2y$%'");
                $plainTextUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $count = count($plainTextUsers);
                if ($count > 0) {
                    echo "<p class='info'>Found {$count} users with plaintext passwords. Converting to secure hashes...</p>";
                    
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $successCount = 0;
                    
                    foreach ($plainTextUsers as $user) {
                        // Hash the plaintext password
                        $secureHash = password_hash($user['password'], PASSWORD_BCRYPT);
                        
                        // Update the database
                        $updateStmt->execute([$secureHash, $user['id']]);
                        
                        echo "<p class='success'>Encrypted password for user: {$user['username']}</p>";
                        $successCount++;
                    }
                    
                    echo "<p class='success'><strong>Successfully encrypted {$successCount} out of {$count} passwords.</strong></p>";
                } else {
                    // Check if there are any users with already encrypted passwords
                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE password LIKE '$2y$%'");
                    $encryptedCount = $stmt->fetchColumn();
                    
                    if ($encryptedCount > 0) {
                        echo "<p class='info'>All passwords are already encrypted. Found {$encryptedCount} users with secure password hashes.</p>";
                    } else {
                        echo "<p class='error'>No users found in the database.</p>";
                    }
                }
            }
            
            echo "<p><a href='login.php'>Return to Login Page</a></p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>
