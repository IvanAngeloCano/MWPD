<?php
/**
 * MWPD Email Diagnostic Tool
 * 
 * This tool helps diagnose issues with emails in the MWPD System
 */

// Include required files
include 'connection.php';
require_once 'email_templates.php';
require_once 'fixed_email_sender.php';

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>MWPD Email Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #0056b3; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .box { border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; }
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        form { margin-bottom: 20px; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background-color: #0056b3; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>MWPD Email Diagnostic Tool</h1>';

// Function to show an error
function show_error($message) {
    echo '<div class="box" style="border-color: #dc3545; background-color: #f8d7da;">';
    echo '<p class="error">' . htmlspecialchars($message) . '</p>';
    echo '</div>';
}

// Function to show success
function show_success($message) {
    echo '<div class="box" style="border-color: #28a745; background-color: #d4edda;">';
    echo '<p class="success">' . htmlspecialchars($message) . '</p>';
    echo '</div>';
}

// Test database connection
echo '<div class="box">';
echo '<h2>Database Connection Test</h2>';

// Verify we have a working PDO connection
if (isset($pdo) && $pdo instanceof PDO) {
    echo '<p class="success">✓ Database connection is working</p>';
    
    // Check if users table exists
    try {
        $tables = $pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
        if (count($tables) > 0) {
            echo '<p class="success">✓ Users table exists</p>';
            
            // Get table structure
            $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
            $email_column = null;
            
            echo '<table>';
            echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>';
            
            foreach ($columns as $column) {
                echo '<tr>';
                foreach ($column as $key => $value) {
                    // Highlight the email field
                    if ($key == 'Field' && strtolower($value) == 'email') {
                        echo '<td><strong style="color: #0056b3;">' . htmlspecialchars($value) . ' (Email field)</strong></td>';
                        $email_column = $value;
                    } else {
                        echo '<td>' . htmlspecialchars($value) . '</td>';
                    }
                }
                echo '</tr>';
            }
            echo '</table>';
            
            // Email field found?
            if ($email_column) {
                echo '<p class="success">✓ Email field found: ' . htmlspecialchars($email_column) . '</p>';
            } else {
                echo '<p class="error">✗ No column named "email" was found in the users table!</p>';
                
                // Suggest possible email fields
                foreach ($columns as $column) {
                    if (stripos($column['Field'], 'mail') !== false || stripos($column['Field'], 'email') !== false) {
                        echo '<p>Possible email field: ' . htmlspecialchars($column['Field']) . '</p>';
                    }
                }
            }
        } else {
            echo '<p class="error">✗ Users table does not exist!</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">✗ Error querying database structure: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p class="error">✗ Database connection is not available!</p>';
}
echo '</div>';

// List users and their email addresses
echo '<div class="box">';
echo '<h2>User Email Address Check</h2>';

try {
    $users = $pdo->query("SELECT id, username, full_name, email FROM users LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Status</th><th>Actions</th></tr>';
        
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td>' . htmlspecialchars($user['username']) . '</td>';
            echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
            
            // Email status
            $email_status = '';
            if (isset($user['email'])) {
                if (empty($user['email'])) {
                    $email_status = '<span class="badge badge-danger">Empty</span>';
                } else {
                    $email_status = '<span class="badge badge-success">Present</span>';
                }
            } else {
                $email_status = '<span class="badge badge-danger">Not set</span>';
            }
            
            echo '<td>' . (empty($user['email']) ? '<em>No email</em>' : htmlspecialchars($user['email'])) . '</td>';
            echo '<td>' . $email_status . '</td>';
            
            // Actions
            echo '<td>';
            if (!empty($user['email'])) {
                echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">';
                echo '<input type="hidden" name="action" value="test_email">';
                echo '<input type="hidden" name="user_id" value="' . $user['id'] . '">';
                echo '<button type="submit" class="btn btn-success">Test Email</button>';
                echo '</form>';
            }
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

// Process form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'test_email' && isset($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        
        try {
            // Get user details
            $stmt = $pdo->prepare("SELECT username, full_name, email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['email'])) {
                echo '<div class="box">';
                echo '<h2>Email Test Results</h2>';
                
                // Display the values we're using
                echo '<h3>User Data</h3>';
                echo '<pre>';
                print_r($user);
                echo '</pre>';
                
                // Generate a test password
                $temp_password = generateTestPassword();
                
                // Get base URL for link
                $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $base_url .= "://" . $_SERVER['HTTP_HOST'];
                $login_url = $base_url . "/login.php";
                
                // Create password reset email
                $html_content = create_password_reset_email(
                    $user['full_name'], 
                    $user['username'], 
                    $temp_password,
                    $login_url
                );
                
                // Send the email directly using our fixed sender
                $result = send_gmail_email(
                    $user['email'],
                    "MWPD Test - Password Reset", 
                    $html_content
                );
                
                if ($result) {
                    show_success("Test email sent successfully to " . htmlspecialchars($user['email']));
                } else {
                    show_error("Failed to send test email. Check the server logs for details.");
                }
                
                echo '</div>';
            } else {
                show_error("User not found or has no email address.");
            }
        } catch (Exception $e) {
            show_error("Error: " . $e->getMessage());
        }
    }
}

// Generate a test password
function generateTestPassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    $char_length = strlen($chars) - 1;
    
    // Create a 10-character random password
    for ($i = 0; $i < 10; $i++) {
        $password .= $chars[rand(0, $char_length)];
    }
    
    return $password;
}

// Debug log viewer
echo '<div class="box">';
echo '<h2>Email Log</h2>';

$log_file = 'email_log.txt';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    if (!empty($log_content)) {
        echo '<pre>' . htmlspecialchars($log_content) . '</pre>';
    } else {
        echo '<p><em>Log file is empty.</em></p>';
    }
} else {
    echo '<p><em>Log file does not exist.</em></p>';
}
echo '</div>';

// Email debug log viewer
echo '<div class="box">';
echo '<h2>Email Debug Log</h2>';

$debug_file = 'email_debug.txt';
if (file_exists($debug_file)) {
    $debug_content = file_get_contents($debug_file);
    if (!empty($debug_content)) {
        echo '<pre>' . htmlspecialchars($debug_content) . '</pre>';
    } else {
        echo '<p><em>Debug log file is empty.</em></p>';
    }
} else {
    echo '<p><em>Debug log file does not exist.</em></p>';
}
echo '</div>';

// Fix for empty email fields
echo '<div class="box">';
echo '<h2>Email Field Fixer</h2>';

// Show form for fixing a specific email
echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">';
echo '<input type="hidden" name="action" value="fix_email">';
echo '<div style="margin-bottom: 15px;">';
echo '<label for="user_id" style="display: block; margin-bottom: 5px;">User ID:</label>';
echo '<input type="number" id="user_id" name="user_id" required style="padding: 8px; width: 100px;">';
echo '</div>';
echo '<div style="margin-bottom: 15px;">';
echo '<label for="email" style="display: block; margin-bottom: 5px;">New Email Address:</label>';
echo '<input type="email" id="email" name="email" required style="padding: 8px; width: 300px;">';
echo '</div>';
echo '<button type="submit" class="btn btn-primary">Update Email</button>';
echo '</form>';

// Process email fix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fix_email') {
    if (isset($_POST['user_id']) && isset($_POST['email'])) {
        $user_id = (int)$_POST['user_id'];
        $email = trim($_POST['email']);
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $result = $stmt->execute([$email, $user_id]);
                
                if ($result) {
                    show_success("Email updated successfully for user ID {$user_id}.");
                } else {
                    show_error("Failed to update email.");
                }
            } catch (PDOException $e) {
                show_error("Database error: " . $e->getMessage());
            }
        } else {
            show_error("Invalid email address format.");
        }
    } else {
        show_error("Missing required parameters.");
    }
}
echo '</div>';

echo '</body></html>';
?>
