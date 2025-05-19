<?php
/**
 * MWPD Email Test Tool
 * 
 * This script allows you to test the fixed email functionality
 * for the MWPD system.
 */

// Include the fixed email sender
include_once 'fixed_email_sender.php';

// Include original email configuration
if (file_exists('email_config.php')) {
    include_once 'email_config.php';
}

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>MWPD Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #0056b3; }
        h2 { color: #333; margin-top: 30px; }
        pre { background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        form { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        input, button { padding: 8px; margin: 5px 0; }
        button { background-color: #0056b3; color: white; border: none; cursor: pointer; padding: 10px 15px; }
        .note { background-color: #e7f3fe; border-left: 6px solid #2196F3; padding: 10px; margin: 15px 0; }
        .integration { background-color: #e8f5e9; border-left: 6px solid #4caf50; padding: 10px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>MWPD System Email Test</h1>
    <div class="note">
        <p>This tool tests the fixed email functionality for MWPD.</p>
    </div>';

// Display information about currently configured email settings
$smtp_username = isset($GLOBALS['email_server']['smtp']['username']) ? 
                $GLOBALS['email_server']['smtp']['username'] : 'Not configured';
$smtp_password = isset($GLOBALS['email_server']['smtp']['password']) ? 
                '[Hidden]' : 'Not configured';
$from_email = isset($GLOBALS['email_config']['from_email']) ? 
              $GLOBALS['email_config']['from_email'] : 'Not configured';
$from_name = isset($GLOBALS['email_config']['from_name']) ? 
             $GLOBALS['email_config']['from_name'] : 'Not configured';

echo '<h2>Current Email Configuration</h2>';
echo '<pre>';
echo "SMTP Username: {$smtp_username}\n";
echo "SMTP Password: {$smtp_password}\n";
echo "From Email: {$from_email}\n";
echo "From Name: {$from_name}\n";
echo '</pre>';

// Test form
echo '<h2>Send Test Email</h2>';
echo '<form method="post">
    <div>
        <label for="test_email">Send test email to:</label>
        <input type="email" id="test_email" name="test_email" required style="width: 300px;">
    </div>
    <div>
        <label for="test_type">Test type:</label>
        <select id="test_type" name="test_type">
            <option value="simple">Simple Test Email</option>
            <option value="password_reset">Password Reset Notification</option>
            <option value="account_approval">Account Approval Notification</option>
        </select>
    </div>
    <button type="submit">Send Test Email</button>
</form>';

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_email'])) {
    $to_email = $_POST['test_email'];
    $test_type = isset($_POST['test_type']) ? $_POST['test_type'] : 'simple';
    
    echo '<h2>Sending Email...</h2>';
    
    switch ($test_type) {
        case 'password_reset':
            $subject = 'MWPD System - Password Reset';
            $temp_password = generateRandomPassword();
            $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h1 style="color: #0056b3;">MWPD System - Password Reset</h1>
                <p>Your password has been reset by an administrator.</p>
                <p>Your new temporary password is: <strong>' . $temp_password . '</strong></p>
                <p>Please login and change your password immediately.</p>
                <hr>
                <p style="font-size: 12px; color: #666;">This is a system-generated email from the MWPD Filing System.</p>
            </div>';
            break;
            
        case 'account_approval':
            $subject = 'MWPD System - Account Approved';
            $username = 'testuser' . rand(100, 999);
            $temp_password = generateRandomPassword();
            $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h1 style="color: #0056b3;">MWPD System - Account Approved</h1>
                <p>Your account has been approved.</p>
                <p><strong>Username:</strong> ' . $username . '</p>
                <p><strong>Password:</strong> ' . $temp_password . '</p>
                <p>Please login and change your password immediately.</p>
                <hr>
                <p style="font-size: 12px; color: #666;">This is a system-generated email from the MWPD Filing System.</p>
            </div>';
            break;
            
        default: // simple
            $subject = 'MWPD System Test Email - ' . date('Y-m-d H:i:s');
            $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                <h1 style="color: #0056b3;">MWPD System Test Email</h1>
                <p>This is a test email from the MWPD Filing System.</p>
                <p>The time is now: ' . date('Y-m-d H:i:s') . '</p>
                <hr>
                <p style="font-size: 12px; color: #666;">This is a system-generated email from the MWPD Filing System.</p>
            </div>';
    }
    
    // Send the email
    $result = send_notification_email($to_email, $subject, $body, 'test');
    
    if ($result) {
        echo '<div class="success">Email sent successfully to: ' . htmlspecialchars($to_email) . '</div>';
        echo '<p>Check your inbox (and spam folder) for the email.</p>';
    } else {
        echo '<div class="error">Failed to send email. Check the logs for more details.</div>';
        
        // Show log file contents
        if (file_exists('email_log.txt')) {
            echo '<h3>Recent Email Log Entries:</h3>';
            echo '<pre>';
            $log_content = file_get_contents('email_log.txt');
            $log_lines = explode("\n", $log_content);
            $log_lines = array_slice($log_lines, -10); // Get last 10 lines
            echo htmlspecialchars(implode("\n", $log_lines));
            echo '</pre>';
        }
    }
    
    echo '<div class="integration">
        <h3>Using the Fixed Email Sender in MWPD</h3>
        <p>To use this improved email sender in the entire MWPD system:</p>
        <ol>
            <li>Include the fixed_email_sender.php in email_notifications.php</li>
            <li>Replace the sendEmail function to use send_notification_email</li>
            <li>Keep both systems running in parallel while testing</li>
        </ol>
        <p>The fixed email sender has been designed to work with all the existing functionality in the MWPD system.</p>
    </div>';
}

// Helper function for generating random passwords
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

echo '</body>
</html>';
?>
