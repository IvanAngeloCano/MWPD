<?php
/**
 * Email Field Fixer for MWPD
 * 
 * This script checks and fixes email fields in the users table
 * to ensure proper email delivery for password resets and notifications.
 */

// Include database connection
require_once 'connection.php';

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>MWPD User Email Field Fixer</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .box { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        form { margin-bottom: 20px; }
        input[type="email"] { padding: 8px; width: 300px; }
        button { background-color: #0056b3; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>MWPD User Email Field Fixer</h1>';

// Check database structure
echo '<div class="box">';
echo '<h2>Database Structure Check</h2>';

try {
    // Check if the users table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($tables) === 0) {
        echo '<p class="error">Users table does not exist!</p>';
        exit;
    }
    
    // Check table structure
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for email field
    $email_field_exists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'email') {
            $email_field_exists = true;
            echo '<p class="success">✓ Email field exists in the users table.</p>';
            break;
        }
    }
    
    // If email field doesn't exist, check for similar fields
    if (!$email_field_exists) {
        echo '<p class="error">✗ No "email" field found in the users table!</p>';
        
        // Look for similar fields
        $email_like_fields = [];
        foreach ($columns as $column) {
            if (stripos($column['Field'], 'mail') !== false || stripos($column['Field'], 'email') !== false) {
                $email_like_fields[] = $column['Field'];
            }
        }
        
        if (count($email_like_fields) > 0) {
            echo '<p class="warning">Found similar fields that might contain email addresses: ' . implode(', ', $email_like_fields) . '</p>';
        } else {
            echo '<p class="error">No email-like fields found. You may need to add an email field to the table.</p>';
            
            // Option to add email field
            if (isset($_POST['action']) && $_POST['action'] === 'add_email_field') {
                try {
                    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) AFTER full_name");
                    echo '<p class="success">Successfully added email field to users table!</p>';
                    $email_field_exists = true;
                } catch (PDOException $e) {
                    echo '<p class="error">Error adding email field: ' . $e->getMessage() . '</p>';
                }
            } else {
                echo '<form method="post">';
                echo '<input type="hidden" name="action" value="add_email_field">';
                echo '<button type="submit">Add Email Field to Users Table</button>';
                echo '</form>';
            }
        }
    }
    
    // Print table structure
    echo '<h3>Users Table Structure:</h3>';
    echo '<table>';
    echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
    
    foreach ($columns as $column) {
        echo '<tr>';
        foreach ($column as $key => $value) {
            echo '<td>' . htmlspecialchars($value) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    
} catch (PDOException $e) {
    echo '<p class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</div>';

// List users and their email status
if (isset($email_field_exists) && $email_field_exists) {
    echo '<div class="box">';
    echo '<h2>User Email Address Status</h2>';
    
    try {
        $users = $pdo->query("SELECT id, username, full_name, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>';
            
            foreach ($users as $user) {
                echo '<tr>';
                echo '<td>' . $user['id'] . '</td>';
                echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
                
                $email_status = '';
                if (empty($user['email'])) {
                    $email_status = '<span class="error">Missing</span>';
                } else if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                    $email_status = '<span class="warning">Invalid Format</span>';
                } else {
                    $email_status = '<span class="success">Valid</span>';
                }
                
                echo '<td>' . (empty($user['email']) ? '<em>Not set</em>' : htmlspecialchars($user['email'])) . '</td>';
                echo '<td>' . $email_status . '</td>';
                
                echo '<td>';
                echo '<form method="post" style="display: inline;">';
                echo '<input type="hidden" name="action" value="update_email">';
                echo '<input type="hidden" name="user_id" value="' . $user['id'] . '">';
                echo '<input type="email" name="email" placeholder="Enter valid email" value="' . htmlspecialchars($user['email']) . '">';
                echo '<button type="submit">Update</button>';
                echo '</form>';
                echo '</td>';
                
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p>No users found in the database.</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">Error querying users: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    echo '</div>';
    
    // Process email update requests
    if (isset($_POST['action']) && $_POST['action'] === 'update_email' && isset($_POST['user_id']) && isset($_POST['email'])) {
        $user_id = (int)$_POST['user_id'];
        $email = trim($_POST['email']);
        
        echo '<div class="box">';
        echo '<h2>Email Update Result</h2>';
        
        if (empty($email)) {
            echo '<p class="warning">No email provided. Email field will be cleared.</p>';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo '<p class="error">Invalid email format: ' . htmlspecialchars($email) . '</p>';
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $result = $stmt->execute([$email, $user_id]);
            
            if ($result) {
                echo '<p class="success">Successfully updated email for user ID ' . $user_id . ' to: ' . htmlspecialchars($email) . '</p>';
                echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">Refresh the page</a> to see the updated list.</p>';
            } else {
                echo '<p class="error">Failed to update email.</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        echo '</div>';
    }
}

// Run a test password reset
echo '<div class="box">';
echo '<h2>Test Password Reset Email</h2>';

echo '<form method="post">';
echo '<input type="hidden" name="action" value="test_reset">';
echo '<p>Select a user to test password reset email:</p>';
echo '<select name="test_user_id" required>';

try {
    $users = $pdo->query("SELECT id, username, full_name, email FROM users WHERE email IS NOT NULL AND email != ''")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        foreach ($users as $user) {
            echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['email']) . ')</option>';
        }
    } else {
        echo '<option value="" disabled>No users with email addresses</option>';
    }
} catch (PDOException $e) {
    echo '<option value="" disabled>Error loading users</option>';
}

echo '</select>';
echo '&nbsp;<button type="submit">Test Password Reset</button>';
echo '</form>';

// Process test password reset
if (isset($_POST['action']) && $_POST['action'] === 'test_reset' && isset($_POST['test_user_id'])) {
    $test_user_id = (int)$_POST['test_user_id'];
    
    require_once 'unified_email_system.php';
    
    try {
        $stmt = $pdo->prepare("SELECT username, full_name, email FROM users WHERE id = ?");
        $stmt->execute([$test_user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['email'])) {
            // Generate a test password
            $new_password = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 10);
            
            // Send test email
            $result = unified_send_password_reset(
                $user['email'],
                $user['full_name'],
                $user['username'],
                $new_password
            );
            
            if ($result) {
                echo '<p class="success">Test password reset email sent successfully to ' . htmlspecialchars($user['email']) . '</p>';
                echo '<p>The test password is: <strong>' . $new_password . '</strong> (This is just a test, the actual password in the database has NOT been changed)</p>';
            } else {
                echo '<p class="error">Failed to send test password reset email. Check the email logs for more information.</p>';
            }
        } else {
            echo '<p class="error">User not found or has no email address.</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '</div>';

// Add link back to admin area
echo '<p><a href="accounts.php">&larr; Back to Accounts</a></p>';

echo '</body></html>';
?>
