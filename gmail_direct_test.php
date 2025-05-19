<?php
// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Gmail Direct Connection Test</h1>";

// Attempt to load configuration
if (file_exists('email_config.php')) {
    include 'email_config.php';
    echo "<p>Loaded configuration from email_config.php</p>";
} else {
    die("<p>ERROR: Could not find email_config.php</p>");
}

if (!isset($GLOBALS['email_server']['smtp']) || !isset($GLOBALS['email_config']['from_email'])) {
    die("<p>ERROR: Invalid configuration in email_config.php</p>");
}

// Get SMTP settings
$smtp_config = $GLOBALS['email_server']['smtp'];
$from_email = $GLOBALS['email_config']['from_email'];

echo "<h2>Current Gmail Settings</h2>";
echo "<pre>";
echo "SMTP Host: " . $smtp_config['host'] . "\n";
echo "SMTP Port: " . $smtp_config['port'] . "\n";
echo "SMTP Security: " . $smtp_config['secure'] . "\n";
echo "SMTP Username: " . $smtp_config['username'] . "\n";
echo "SMTP Password: " . (empty($smtp_config['password']) ? "MISSING!" : "Set (hidden)") . "\n";
echo "From Email: " . $from_email . "\n";
echo "</pre>";

// Simple form for sending test email
echo "<h2>Send Test Email</h2>";
echo "<form method='post'>";
echo "<p>To: <input type='email' name='to_email' required></p>";
echo "<p><button type='submit'>Send Test Email</button></p>";
echo "</form>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['to_email'])) {
    $to_email = $_POST['to_email'];
    
    echo "<h2>Testing Email to: " . htmlspecialchars($to_email) . "</h2>";
    
    // Check if PHPMailer is available
    if (!file_exists('phpmailer/class.phpmailer.php')) {
        echo "<p>WARNING: PHPMailer not found in phpmailer/class.phpmailer.php</p>";
    } else {
        require_once 'phpmailer/class.phpmailer.php';
    }
    
    if (!class_exists('PHPMailer')) {
        die("<p>ERROR: PHPMailer class not found!</p>");
    }
    
    // Create detailed PHPMailer instance for testing
    $mail = new PHPMailer();
    $mail->isSMTP();
    
    // Set detailed debugging
    $mail->SMTPDebug = 3; // Maximum debugging output
    
    // Configure SMTP settings - ORDER IS CRITICAL FOR GMAIL
    $mail->Host = $smtp_config['host'];
    $mail->Port = $smtp_config['port'];
    
    // CRITICAL: Set security BEFORE auth for Gmail
    $mail->SMTPSecure = 'tls'; // Force TLS regardless of config
    
    // Fix deprecated property warning
    if (property_exists($mail, 'SMTPOptions')) {
        // Property exists in class definition, safe to set
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
    }
    
    // Authentication credentials AFTER setting security
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_config['username'];
    $mail->Password = $smtp_config['password'];
    
    // Set sender and recipient
    $mail->setFrom($from_email, 'MWPD System Test');
    $mail->addAddress($to_email);
    
    // Set content
    $mail->isHTML(true);
    $mail->Subject = 'Gmail Test ' . date('Y-m-d H:i:s');
    $mail->Body = '<h1>MWPD Gmail Test</h1><p>This email was sent using direct Gmail SMTP at: ' . date('Y-m-d H:i:s') . '</p>';
    
    // Start output buffering to capture debug output
    ob_start();
    
    // Try to send email
    $result = $mail->send();
    
    // Get debug output
    $debug = ob_get_clean();
    
    echo "<h3>Debug Output:</h3>";
    echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; max-height: 400px;'>";
    echo htmlspecialchars($debug);
    echo "</pre>";
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>SUCCESS! Email sent successfully.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>FAILED! Could not send email.</p>";
        echo "<p>Error: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    }
    
    echo "<h3>Gmail Troubleshooting Tips:</h3>";
    echo "<ol>";
    echo "<li><strong>Check App Password:</strong> Make sure you're using an App Password for your Gmail account if you have 2-factor authentication enabled. <a href='https://myaccount.google.com/apppasswords' target='_blank'>Create one here</a>.</li>";
    echo "<li><strong>Check Less Secure Apps:</strong> If you don't use 2-factor authentication, make sure 'Less Secure App Access' is enabled in your <a href='https://myaccount.google.com/security' target='_blank'>Google Account Security settings</a>.</li>";
    echo "<li><strong>Check Gmail Security Alerts:</strong> Login to your Gmail account and check for any security alerts about blocked sign-in attempts.</li>";
    echo "<li><strong>Try a Different App Password:</strong> Generate a new App Password specifically for this application.</li>";
    echo "</ol>";
}
?>
