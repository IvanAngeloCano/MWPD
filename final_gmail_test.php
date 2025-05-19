<?php
/**
 * Final Gmail Test for MWPD System
 * 
 * This script bypasses all the fallback methods and directly attempts
 * a Gmail SMTP connection with proper STARTTLS sequence.
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load required libraries
if (file_exists('phpmailer/class.phpmailer.php')) {
    require_once 'phpmailer/class.phpmailer.php';
}

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>MWPD Gmail Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #0056b3; }
        h2 { color: #333; margin-top: 30px; }
        pre { background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background-color: #e7f3fe; border-left: 6px solid #2196F3; padding: 10px; margin: 15px 0; }
        .warning { background-color: #ffffcc; border-left: 6px solid #ffeb3b; padding: 10px; margin: 15px 0; }
        form { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        input, button { padding: 8px; margin: 5px 0; }
        button { background-color: #0056b3; color: white; border: none; cursor: pointer; padding: 10px 15px; }
    </style>
</head>
<body>
    <h1>MWPD Gmail Test Tool</h1>
    <div class="info">
        <p>This tool tests Gmail SMTP connection directly with the proper STARTTLS sequence.</p>
    </div>';

// Check if PHPMailer is available
if (!class_exists('PHPMailer')) {
    echo '<div class="error">Error: PHPMailer class not found!</div>';
    exit;
}

// Load email configuration
try {
    if (file_exists('email_config.php')) {
        include 'email_config.php';
        echo '<div class="success">Successfully loaded email_config.php</div>';
    } else {
        echo '<div class="error">Error: email_config.php not found!</div>';
        exit;
    }

    // Load email_notifications.php as backup if needed
    if (!isset($GLOBALS['email_server']) && file_exists('email_notifications.php')) {
        include 'email_notifications.php';
        echo '<div class="info">Loaded settings from email_notifications.php as fallback</div>';
    }

    if (!isset($GLOBALS['email_server']['smtp'])) {
        echo '<div class="error">Error: SMTP configuration not found in config files!</div>';
        exit;
    }

    $smtp_config = $GLOBALS['email_server']['smtp'];
    $from_email = isset($GLOBALS['email_config']['from_email']) ? 
                 $GLOBALS['email_config']['from_email'] : 
                 $smtp_config['username'];

    echo '<h2>Current Gmail Settings</h2>';
    echo '<pre>';
    echo "SMTP Host: {$smtp_config['host']}\n";
    echo "SMTP Port: {$smtp_config['port']}\n";
    echo "Security: {$smtp_config['secure']}\n";
    echo "Username: {$smtp_config['username']}\n";
    echo "Password: " . (empty($smtp_config['password']) ? "NOT SET" : "[HIDDEN]") . "\n";
    echo "From Email: {$from_email}\n";
    echo '</pre>';

} catch (Exception $e) {
    echo '<div class="error">Error loading configuration: ' . $e->getMessage() . '</div>';
    exit;
}

// Display test form
echo '<h2>Send Test Email</h2>';
echo '<form method="post">
    <div>
        <label for="test_email">Send test email to:</label>
        <input type="email" id="test_email" name="test_email" required>
    </div>
    <div>
        <label for="test_subject">Subject:</label>
        <input type="text" id="test_subject" name="test_subject" value="MWPD Gmail Test (' . date('Y-m-d H:i:s') . ')" style="width: 100%;">
    </div>
    <button type="submit">Send Test Email</button>
</form>';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_email'])) {
    $to_email = $_POST['test_email'];
    $subject = !empty($_POST['test_subject']) ? $_POST['test_subject'] : 'MWPD Test Email';
    
    echo '<h2>Testing Email Delivery</h2>';
    echo '<div class="info">Sending test email to: ' . htmlspecialchars($to_email) . '</div>';
    
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer();
        $mail->isSMTP();
        
        // Set debugging level
        $mail->SMTPDebug = 2; // Verbose debugging output
        
        // The crucial part - set up SMTP with proper order for Gmail
        $mail->Host = $smtp_config['host'];
        $mail->Port = $smtp_config['port'];
        
        // CRITICAL: Set SMTPSecure before SMTPAuth for Gmail
        // This ensures STARTTLS is initiated before authentication
        $mail->SMTPSecure = 'tls'; 
        
        // Only then set authentication
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_config['username'];
        $mail->Password = $smtp_config['password'];
        
        // Fix for PHP 8.x deprecation warnings
        if (property_exists($mail, 'SMTPOptions')) {
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
        
        // Set sender and recipient
        $mail->setFrom($from_email, 'MWPD System');
        $mail->addAddress($to_email);
        
        // Set email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <h1 style="color: #0056b3;">MWPD Email System Test</h1>
            <p>This is a test email sent from the MWPD Filing System.</p>
            <p>If you received this email, it means the Gmail SMTP connection is working correctly.</p>
            <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
            <hr>
            <p style="font-size: 12px; color: #666;">MWPD Filing System</p>
        </div>';
        
        // Set plain text version
        $mail->AltBody = "MWPD Email System Test\n\nThis is a test email sent from the MWPD Filing System. If you received this email, it means the Gmail SMTP connection is working correctly.\n\nTime sent: " . date('Y-m-d H:i:s');
        
        // Start output buffering to capture debug output
        ob_start();
        
        // Attempt to send
        $success = $mail->send();
        
        // Get debug output
        $debug_output = ob_get_clean();
        
        // Display debug information
        echo '<h3>SMTP Debug Output</h3>';
        echo '<pre>' . htmlspecialchars($debug_output) . '</pre>';
        
        if ($success) {
            echo '<div class="success">SUCCESS! Email sent successfully to ' . htmlspecialchars($to_email) . '</div>';
            echo '<p>If you don\'t see the email in your inbox, please check your spam folder.</p>';
        } else {
            echo '<div class="error">FAILED to send email: ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">Exception occurred: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
    echo '<h3>What To Do If Emails Still Fail</h3>';
    echo '<ol>
        <li><strong>Check Your Gmail Account:</strong> Login to your Gmail and look for security alerts.</li>
        <li><strong>Generate a New App Password:</strong> If you have 2-factor authentication enabled, <a href="https://myaccount.google.com/apppasswords" target="_blank">generate a new app password</a>.</li>
        <li><strong>Update Your Google Security Settings:</strong> If you don\'t use 2FA, enable "Less secure app access" in your <a href="https://myaccount.google.com/security" target="_blank">security settings</a>.</li>
        <li><strong>Try Another Email Provider:</strong> If Gmail continues to fail, consider using a different email provider like Outlook or a dedicated SMTP service.</li>
    </ol>';
}

// Add information about fixing the main application
echo '<div class="warning">
    <h3>Fixing Gmail in Your MWPD System</h3>
    <p>If this test tool works but emails still don\'t send from your MWPD application, update the following files with the proper Gmail settings:</p>
    <ol>
        <li><strong>email_config.php</strong> - Make sure it has the correct Gmail app password</li>
        <li><strong>email_notifications.php</strong> - Ensure it has the same username and password as email_config.php</li>
        <li><strong>foolproof_mailer.php</strong> - Make sure it handles STARTTLS properly</li>
    </ol>
</div>';

echo '</body></html>';
?>
