<?php
/**
 * Fixed Gmail Email Sender for MWPD
 * 
 * This is a replacement email sender that uses raw SMTP
 * to correctly communicate with Gmail's servers.
 */

// Include the system configurations
if (file_exists('email_config.php')) {
    include_once 'email_config.php';
}

/**
 * Fixed Gmail sender using raw SMTP
 * 
 * @param string $to_email Recipient email address
 * @param string $subject Email subject
 * @param string $html_body HTML email body
 * @param string $from_email Optional sender email
 * @param string $from_name Optional sender name
 * @return bool True if email sent successfully, false otherwise
 */
function send_gmail_email($to_email, $subject, $html_body, $from_email = null, $from_name = null) {
    // Log attempt
    error_log(date('Y-m-d H:i:s') . " - Attempting to send email to {$to_email} via fixed_email_sender.php");
    
    // Get configuration
    if (isset($GLOBALS['email_server']['smtp'])) {
        $smtp_config = $GLOBALS['email_server']['smtp'];
    } else {
        // Default Gmail settings
        $smtp_config = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => isset($GLOBALS['email_config']['from_email']) ? $GLOBALS['email_config']['from_email'] : '',
            'password' => ''
        ];
    }
    
    // Use defaults if not provided
    if (empty($from_email)) {
        $from_email = isset($GLOBALS['email_config']['from_email']) ? 
                      $GLOBALS['email_config']['from_email'] : 
                      $smtp_config['username'];
    }
    
    if (empty($from_name)) {
        $from_name = isset($GLOBALS['email_config']['from_name']) ? 
                     $GLOBALS['email_config']['from_name'] : 
                     'MWPD System';
    }
    
    // Create text version of email
    $text_body = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html_body));
    
    // Connect to SMTP server
    $socket = fsockopen($smtp_config['host'], $smtp_config['port'], $errno, $errstr, 30);
    if (!$socket) {
        error_log("SMTP Connection Error: {$errstr} ({$errno})");
        return false;
    }
    
    // Helper function to read server response
    $read_response = function() use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    };
    
    // Helper to send command and get response
    $send_command = function($command) use ($socket, $read_response) {
        fwrite($socket, $command . "\r\n");
        return $read_response();
    };
    
    try {
        // Read initial greeting
        $greeting = $read_response();
        
        // Send EHLO
        $send_command("EHLO localhost");
        
        // Start TLS
        $tls_response = $send_command("STARTTLS");
        if (substr($tls_response, 0, 3) != '220') {
            throw new Exception("STARTTLS failed: {$tls_response}");
        }
        
        // Enable crypto
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // Send EHLO again after TLS
        $send_command("EHLO localhost");
        
        // Authenticate
        $send_command("AUTH LOGIN");
        $send_command(base64_encode($smtp_config['username']));
        $auth_response = $send_command(base64_encode($smtp_config['password']));
        
        if (substr($auth_response, 0, 3) != '235') {
            throw new Exception("Authentication failed: {$auth_response}");
        }
        
        // Set sender and recipient
        $send_command("MAIL FROM:<{$from_email}>");
        $send_command("RCPT TO:<{$to_email}>");
        
        // Start data
        $data_init = $send_command("DATA");
        if (substr($data_init, 0, 3) != '354') {
            throw new Exception("DATA command failed: {$data_init}");
        }
        
        // Compose email headers and body
        $email = "From: {$from_name} <{$from_email}>\r\n";
        $email .= "To: {$to_email}\r\n";
        $email .= "Subject: {$subject}\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: multipart/alternative; boundary=\"boundary-MWPD\"\r\n";
        $email .= "\r\n";
        
        // Plain text part
        $email .= "--boundary-MWPD\r\n";
        $email .= "Content-Type: text/plain; charset=utf-8\r\n";
        $email .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $email .= "\r\n";
        $email .= $text_body . "\r\n";
        
        // HTML part
        $email .= "--boundary-MWPD\r\n";
        $email .= "Content-Type: text/html; charset=utf-8\r\n";
        $email .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $email .= "\r\n";
        $email .= $html_body . "\r\n";
        
        // End boundary
        $email .= "--boundary-MWPD--\r\n";
        
        // End data with single dot on a line
        $email .= "\r\n.\r\n";
        
        // Send data
        $data_response = $send_command($email);
        if (substr($data_response, 0, 3) != '250') {
            throw new Exception("Email was not accepted: {$data_response}");
        }
        
        // Quit and close
        $send_command("QUIT");
        fclose($socket);
        
        // Log success
        error_log(date('Y-m-d H:i:s') . " - Email sent successfully to {$to_email}");
        return true;
        
    } catch (Exception $e) {
        // Log error
        error_log(date('Y-m-d H:i:s') . " - Email send error: " . $e->getMessage());
        
        // Try to close connection
        if (is_resource($socket)) {
            fclose($socket);
        }
        
        return false;
    }
}

/**
 * Send a notification email
 * 
 * @param string $to_email Recipient email address
 * @param string $subject Email subject
 * @param string $message_body Email body (HTML)
 * @param string $message_type Type of message for logging
 * @return bool Whether the email was sent successfully
 */
function send_notification_email($to_email, $subject, $message_body, $message_type = 'notification') {
    // Log attempt
    error_log(date('Y-m-d H:i:s') . " - Sending email to {$to_email}");
    
    // Try to send the email up to 3 times
    $max_attempts = 3;
    $success = false;
    $last_error = "";
    
    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
        try {
            $result = send_gmail_email($to_email, $subject, $message_body);
            
            if ($result) {
                $success = true;
                break;
            } else {
                // Wait a moment before retrying
                sleep(1);
                $last_error = "Attempt {$attempt} failed.";
            }
        } catch (Exception $e) {
            $last_error = $e->getMessage();
            sleep(1);
        }
    }
    
    // Log the final status - ONLY ONE STATUS LOG!  
    if ($success) {
        log_fixed_email_activity($to_email, $subject, $message_type, "success");
        return true;
    } else {
        error_log(date('Y-m-d H:i:s') . " - FAILED - To: {$to_email}, Subject: {$subject}");
        log_fixed_email_activity($to_email, $subject, $message_type, "failed", "Failed to send email after {$max_attempts} attempts: {$last_error}");
        
        // If failed, store for later delivery
        $email_data = [
            'to' => $to_email,
            'subject' => $subject, 
            'message' => $message_body,
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $message_type
        ];
        
        $email_json = json_encode($email_data) . "\n";
        file_put_contents('pending_emails.txt', $email_json, FILE_APPEND);
        
        return false;
    }
}

/**
 * Logs email activities for audit and debugging
 * renamed to avoid conflicts with email_notifications.php
 */
function log_fixed_email_activity($to, $subject, $message_type, $status, $error = null) {
    $log_file = 'email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] TO: {$to} | SUBJECT: {$subject} | TYPE: {$message_type} | STATUS: {$status}";
    
    if ($error) {
        $log_message .= " | ERROR: {$error}";
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

// Test function that can be called separately
function test_gmail_connection($to_email) {
    $subject = "MWPD Gmail Test - " . date('Y-m-d H:i:s');
    $body = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h1 style="color: #0056b3;">MWPD Email Test</h1>
        <p>This is a test email sent at: ' . date('Y-m-d H:i:s') . '</p>
        <p>If you received this email, it means the Gmail connection is working correctly!</p>
    </div>';
    
    return send_notification_email($to_email, $subject, $body, 'test');
}
?>
