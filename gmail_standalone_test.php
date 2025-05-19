<?php
/**
 * Standalone Gmail Test
 * 
 * This test completely bypasses the MWPD system and uses raw PHP
 * socket functions to test Gmail connection directly.
 */

// Show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send a simple email via raw SMTP
function test_gmail_smtp_raw($username, $password, $from, $to, $subject, $body) {
    // Gmail SMTP settings
    $smtp_server = 'smtp.gmail.com';
    $smtp_port = 587;
    
    // Open connection to SMTP server
    echo "Connecting to $smtp_server:$smtp_port...<br>";
    $socket = fsockopen($smtp_server, $smtp_port, $errno, $errstr, 30);
    
    if (!$socket) {
        return "ERROR: $errstr ($errno)";
    }
    
    // Helper function to read server response
    $read_socket = function() use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if(substr($line, 3, 1) == ' ') break;
        }
        return $response;
    };
    
    // Helper to send command and get response
    $send_command = function($command) use ($socket, $read_socket) {
        echo "CLIENT: $command<br>";
        fputs($socket, $command . "\r\n");
        $response = $read_socket();
        echo "SERVER: " . htmlspecialchars($response) . "<br>";
        return $response;
    };
    
    // Read initial greeting
    $greeting = $read_socket();
    echo "SERVER: " . htmlspecialchars($greeting) . "<br>";
    
    // Start communication
    $send_command("EHLO localhost");
    
    // Start TLS
    $tls_response = $send_command("STARTTLS");
    if (substr($tls_response, 0, 3) != '220') {
        fclose($socket);
        return "ERROR: STARTTLS not accepted by server";
    }
    
    // Enable crypto on the stream
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // After TLS, need to EHLO again
    $send_command("EHLO localhost");
    
    // Authenticate
    $send_command("AUTH LOGIN");
    $send_command(base64_encode($username));
    $auth_response = $send_command(base64_encode($password));
    
    if (substr($auth_response, 0, 3) != '235') {
        fclose($socket);
        return "ERROR: Authentication failed";
    }
    
    // Send email
    $send_command("MAIL FROM:<$from>");
    $send_command("RCPT TO:<$to>");
    $send_command("DATA");
    
    // Compose email headers and body
    $email = "From: $from\r\n";
    $email .= "To: $to\r\n";
    $email .= "Subject: $subject\r\n";
    $email .= "MIME-Version: 1.0\r\n";
    $email .= "Content-Type: text/html; charset=utf-8\r\n";
    $email .= "\r\n";
    $email .= $body;
    $email .= "\r\n.\r\n";
    
    $data_response = $send_command($email);
    
    if (substr($data_response, 0, 3) != '250') {
        fclose($socket);
        return "ERROR: Email not accepted by server";
    }
    
    // Quit
    $send_command("QUIT");
    fclose($socket);
    
    return "SUCCESS: Email sent successfully!";
}

// Load Gmail credentials
$username = '';
$password = '';
$from_email = '';

if (file_exists('email_config.php')) {
    include 'email_config.php';
    
    if (isset($GLOBALS['email_server']['smtp']['username'])) {
        $username = $GLOBALS['email_server']['smtp']['username'];
        $password = $GLOBALS['email_server']['smtp']['password'];
    }
    
    if (isset($GLOBALS['email_config']['from_email'])) {
        $from_email = $GLOBALS['email_config']['from_email'];
    }
}

if (empty($from_email)) {
    $from_email = $username;
}

// HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>Gmail Raw SMTP Test</title>
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
    </style>
</head>
<body>
    <h1>Gmail Raw SMTP Test</h1>
    <div class="note">
        <p>This tool tests Gmail SMTP connection directly using raw PHP socket functions.</p>
        <p>It bypasses PHPMailer and all other libraries to help identify the exact issue.</p>
    </div>';

// Display Gmail settings
echo '<h2>Current Gmail Settings</h2>';
echo '<pre>';
echo "Username: " . htmlspecialchars($username) . "\n";
echo "Password: " . (empty($password) ? "NOT SET" : "[HIDDEN]") . "\n";
echo "From Email: " . htmlspecialchars($from_email) . "\n";
echo '</pre>';

// Show test form
echo '<h2>Send Test Email</h2>';
echo '<form method="post">
    <div>
        <label for="test_email">Send test email to:</label>
        <input type="email" id="test_email" name="test_email" required style="width: 300px;">
    </div>
    <div>
        <label for="custom_username">Gmail Username (optional):</label>
        <input type="text" id="custom_username" name="custom_username" value="' . htmlspecialchars($username) . '" style="width: 300px;">
    </div>
    <div>
        <label for="custom_password">Gmail Password/App Password (optional):</label>
        <input type="password" id="custom_password" name="custom_password" value="' . htmlspecialchars($password) . '" style="width: 300px;">
    </div>
    <button type="submit">Test Gmail Connection</button>
</form>';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_email'])) {
    $to_email = $_POST['test_email'];
    
    // Use custom credentials if provided
    if (!empty($_POST['custom_username'])) {
        $username = $_POST['custom_username'];
    }
    
    if (!empty($_POST['custom_password'])) {
        $password = $_POST['custom_password'];
    }
    
    echo '<h2>Testing Gmail Connection</h2>';
    
    if (empty($username) || empty($password)) {
        echo '<div class="error">ERROR: Gmail username or password is not set.</div>';
    } else {
        $subject = 'Raw SMTP Gmail Test - ' . date('Y-m-d H:i:s');
        $body = '<h1>Gmail SMTP Test</h1><p>This is a test message sent directly via raw SMTP sockets at ' . date('Y-m-d H:i:s') . '</p>';
        
        echo '<div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 20px;">';
        echo '<h3>Connection Log:</h3>';
        $result = test_gmail_smtp_raw($username, $password, $from_email, $to_email, $subject, $body);
        echo '</div>';
        
        if (strpos($result, 'SUCCESS') === 0) {
            echo '<div class="success">' . $result . '</div>';
        } else {
            echo '<div class="error">' . $result . '</div>';
        }
        
        echo '<h3>What to do if this test fails:</h3>';
        echo '<ol>
            <li><strong>Generate a new App Password:</strong> Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">Google Account → Security → App Passwords</a></li>
            <li><strong>Check Gmail security settings:</strong> Login to your Gmail and look for security alerts</li>
            <li><strong>Try from a different network:</strong> Some ISPs or networks block outgoing SMTP traffic</li>
            <li><strong>Consider using a different email provider:</strong> If Gmail continues to be problematic</li>
        </ol>';
    }
}

echo '<div class="note" style="margin-top: 30px;">
    <h3>Important Gmail Security Information</h3>
    <p>Gmail has strict security requirements:</p>
    <ul>
        <li>If you use 2-Factor Authentication, you <strong>must</strong> use an App Password</li>
        <li>Without 2FA, you need to enable "Less secure app access" in your Google Account</li>
        <li>Google can silently block SMTP attempts even when your credentials are correct</li>
        <li>App passwords can expire or be automatically revoked by Google security systems</li>
    </ul>
</div>

</body>
</html>';
?>
