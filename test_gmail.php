<?php
/**
 * Gmail SMTP Connection Tester
 * 
 * This script is a simple tool to test Gmail SMTP connections directly
 * without going through the full application architecture.
 */

// Show all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Display header
echo '<html>
<head>
    <title>Gmail SMTP Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .container { border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #666; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="password"], input[type="email"] { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        input[type="submit"] { background-color: #0056b3; color: white; border: none; padding: 10px 15px; margin-top: 15px; cursor: pointer; border-radius: 4px; }
        .note { background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Gmail SMTP Connection Test</h1>
    <div class="note">
        <p><strong>Note:</strong> This tool tests your Gmail SMTP connection settings directly.</p>
        <p>It bypasses all the normal application code to give you clear feedback on whether your Gmail settings are correct.</p>
    </div>';

// Load current settings from config
$email_username = '';
$email_password = '';
$email_from = '';
$test_email = '';

// Try to load from configuration
if (file_exists('email_config.php')) {
    include 'email_config.php';
    
    if (isset($GLOBALS['email_server']) && isset($GLOBALS['email_server']['smtp'])) {
        $email_username = $GLOBALS['email_server']['smtp']['username'];
        $email_password = $GLOBALS['email_server']['smtp']['password'];
    }
    
    if (isset($GLOBALS['email_config']) && isset($GLOBALS['email_config']['from_email'])) {
        $email_from = $GLOBALS['email_config']['from_email'];
    }
}

// Display form
echo '<div class="container">
    <h2>Test Gmail Settings</h2>
    <form method="post" action="">
        <label for="email_username">Gmail Username:</label>
        <input type="text" id="email_username" name="email_username" value="' . htmlspecialchars($email_username) . '" required>
        
        <label for="email_password">Gmail App Password:</label>
        <input type="password" id="email_password" name="email_password" value="' . htmlspecialchars($email_password) . '" required>
        
        <label for="email_from">From Email (should match Gmail username):</label>
        <input type="email" id="email_from" name="email_from" value="' . htmlspecialchars($email_from) . '" required>
        
        <label for="test_email">Send Test Email To:</label>
        <input type="email" id="test_email" name="test_email" value="' . htmlspecialchars($test_email) . '" required>
        
        <input type="submit" value="Test SMTP Connection">
    </form>
</div>';

// If form is submitted, test the connection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_username = $_POST['email_username'];
    $email_password = $_POST['email_password'];
    $email_from = $_POST['email_from'];
    $test_email = $_POST['test_email'];
    
    echo '<div class="container">
        <h2>Test Results</h2>';
    
    // Save these settings to a simple file for future use
    $settings = "<?php\n";
    $settings .= "// Test settings saved on " . date('Y-m-d H:i:s') . "\n";
    $settings .= "\$email_username = '" . addslashes($email_username) . "';\n";
    $settings .= "\$email_password = '" . addslashes($email_password) . "';\n";
    $settings .= "\$email_from = '" . addslashes($email_from) . "';\n";
    
    file_put_contents('test_smtp_settings.php', $settings);
    
    // Try to use the PHPMailer class directly
    if (!class_exists('PHPMailer')) {
        // Check if we can find PHPMailer in various locations
        $phpmailer_locations = [
            'phpmailer/class.phpmailer.php',
            'vendor/phpmailer/phpmailer/class.phpmailer.php',
            'vendor/phpmailer/phpmailer/src/PHPMailer.php'
        ];
        
        $phpmailer_found = false;
        foreach ($phpmailer_locations as $location) {
            if (file_exists($location)) {
                require_once($location);
                $phpmailer_found = true;
                echo "<p>PHPMailer found at: <code>$location</code></p>";
                break;
            }
        }
        
        if (!$phpmailer_found) {
            echo '<p class="error">PHPMailer not found in expected locations. Using built-in mail function instead.</p>';
        }
    }
    
    echo '<h3>Step 1: Checking Gmail SMTP settings</h3>';
    echo '<pre>';
    echo "Gmail Username: " . htmlspecialchars($email_username) . "\n";
    echo "Gmail Password: " . (empty($email_password) ? "Not set" : "Set (hidden)") . "\n";
    echo "From Email: " . htmlspecialchars($email_from) . "\n";
    echo "SMTP Host: smtp.gmail.com\n";
    echo "SMTP Port: 587\n";
    echo "SMTP Security: TLS\n";
    echo '</pre>';
    
    if (class_exists('PHPMailer')) {
        echo '<h3>Step 2: Testing SMTP connection</h3>';
        
        try {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->SMTPDebug = 2; // Debug output
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->SMTPSecure = 'tls';
            $mail->SMTPAuth = true;
            $mail->Username = $email_username;
            $mail->Password = $email_password;
            $mail->setFrom($email_from, 'MWPD Test');
            $mail->addAddress($test_email);
            $mail->Subject = 'MWPD Gmail Test - ' . date('Y-m-d H:i:s');
            $mail->Body = 'This is a test email to verify Gmail SMTP connectivity from MWPD.';
            
            // Start output buffering to capture debug output
            ob_start();
            $success = $mail->send();
            $debug_output = ob_get_clean();
            
            echo '<pre>' . htmlspecialchars($debug_output) . '</pre>';
            
            if ($success) {
                echo '<p class="success">Success! Email sent successfully to ' . htmlspecialchars($test_email) . '</p>';
                echo '<p>Check your inbox (and spam folder) for the test email.</p>';
            } else {
                echo '<p class="error">Failed to send email: ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Exception occurred: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    } else {
        // Fallback to basic mail function
        echo '<h3>Step 2: Attempting to use PHP mail() function</h3>';
        
        $subject = 'MWPD Mail Test - ' . date('Y-m-d H:i:s');
        $message = '<html><body><p>This is a test email from MWPD.</p></body></html>';
        $headers = "From: " . $email_from . "\r\n";
        $headers .= "Reply-To: " . $email_from . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $mail_result = @mail($test_email, $subject, $message, $headers);
        
        if ($mail_result) {
            echo '<p class="success">Email sent using PHP mail() function.</p>';
        } else {
            echo '<p class="error">PHP mail() function failed. This is expected if your XAMPP installation does not have a mail server configured.</p>';
        }
    }
    
    echo '<h3>Gmail Troubleshooting Tips</h3>';
    echo '<ul>
        <li><strong>Make sure you\'re using an App Password</strong> if your Gmail has 2-factor authentication enabled. <a href="https://myaccount.google.com/apppasswords" target="_blank">Generate one here</a>.</li>
        <li>If you don\'t use 2-factor authentication, make sure <a href="https://myaccount.google.com/lesssecureapps" target="_blank">Less secure app access</a> is enabled.</li>
        <li>Check if your Gmail account has any security restrictions preventing SMTP access from new locations.</li>
        <li>If all else fails, look for a notification in your Gmail inbox about a blocked sign-in attempt.</li>
    </ul>';
    
    echo '</div>';
}

echo '</body></html>';
?>
