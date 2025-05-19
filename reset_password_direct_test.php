<?php
/**
 * Direct Password Reset Test
 * 
 * This script tests the password reset email functionality directly
 * using our unified email system, bypassing all the circular references.
 */

// Include only what we need
require_once 'unified_email_system.php';

// HTML header
echo '<!DOCTYPE html>
<html>
<head>
    <title>Password Reset Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; max-width: 800px; margin: 0 auto; }
        h1 { color: #0056b3; }
        form { margin-bottom: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, button { margin-bottom: 15px; padding: 8px; width: 100%; }
        button { background-color: #0056b3; color: white; border: none; cursor: pointer; }
        .success { color: green; padding: 15px; background-color: #d4edda; border-radius: 5px; }
        .error { color: red; padding: 15px; background-color: #f8d7da; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Password Reset Email Test</h1>';

// Generate a test password
function generateTestPassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    // Ensure at least one uppercase, one lowercase, one number and one special char
    $password .= $chars[rand(26, 51)]; // Uppercase
    $password .= $chars[rand(0, 25)];  // Lowercase
    $password .= $chars[rand(52, 61)]; // Number
    $password .= $chars[rand(62, strlen($chars)-1)]; // Special char
    
    // Fill the rest randomly
    for ($i = 0; $i < 6; $i++) {
        $password .= $chars[rand(0, strlen($chars)-1)];
    }
    
    // Shuffle the password
    return str_shuffle($password);
}

// Show the email form if not submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<form method="post">
        <label for="email">Recipient Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="full_name">Full Name:</label>
        <input type="text" id="full_name" name="full_name" value="John Doe" required>
        
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="johndoe" required>
        
        <button type="submit">Send Password Reset Email</button>
    </form>';
} else {
    // Process the form submission
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $full_name = isset($_POST['full_name']) ? $_POST['full_name'] : '';
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    
    if (empty($email) || empty($full_name) || empty($username)) {
        echo '<div class="error">All fields are required.</div>';
    } else {
        // Generate a test password
        $temp_password = generateTestPassword();
        
        echo '<h2>Test Details</h2>';
        echo '<pre>';
        echo "Email: $email\n";
        echo "Name: $full_name\n";
        echo "Username: $username\n";
        echo "Generated Password: $temp_password\n";
        echo '</pre>';
        
        // Send the email directly using our unified system
        $result = unified_send_password_reset($email, $full_name, $username, $temp_password);
        
        if ($result) {
            echo '<div class="success">
                <p><strong>Success!</strong> Password reset email sent successfully.</p>
                <p>The recipient should receive a professionally designed email with their new password.</p>
            </div>';
        } else {
            echo '<div class="error">
                <p><strong>Error!</strong> Failed to send password reset email.</p>
                <p>Please check the email logs for more details.</p>
            </div>';
        }
        
        // Show logs for debugging
        echo '<h2>Email Logs</h2>';
        $log_file = 'email_log.txt';
        if (file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            $log_lines = explode("\n", $log_content);
            
            // Show only the last 10 lines
            $log_lines = array_slice($log_lines, max(0, count($log_lines) - 10));
            
            echo '<pre>';
            foreach ($log_lines as $line) {
                if (strpos($line, $email) !== false) {
                    echo "<strong>$line</strong>\n";
                } else {
                    echo "$line\n";
                }
            }
            echo '</pre>';
        } else {
            echo '<p>No log file found.</p>';
        }
    }
    
    echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">Send another test email</a></p>';
}

echo '</body></html>';
?>
