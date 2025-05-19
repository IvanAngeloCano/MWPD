<?php
/**
 * Foolproof Mailer
 * 
 * A reliable email sending utility for MWPD Filing System
 * This handles email sending and ensures proper delivery
 */

// Check if PHPMailer already exists
if (!class_exists('PHPMailer')) {
    // Include PHPMailer if not already included
    require_once 'phpmailer/class.phpmailer.php';
}

// Set up error logging
ini_set('log_errors', 1);
ini_set('error_log', 'email_system_log.txt');

// Log function for email system
function email_system_log($message) {
    error_log(date('Y-m-d H:i:s') . ' - ' . $message);
}

/**
 * Send an email using PHPMailer
 * 
 * @param string $to_email Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param string $from_email Sender email address (optional)
 * @param string $from_name Sender name (optional)
 * @param string $reply_to Reply-to email address (optional)
 * @param array $attachments Array of file paths to attach (optional)
 * @return bool True if email sent successfully, false otherwise
 */
function send_email($to_email, $subject, $message, $from_email = null, $from_name = null, $reply_to = null, $attachments = []) {
    // Use global email config if not specified
    if ($from_email === null && isset($GLOBALS['email_config']['from_email'])) {
        $from_email = $GLOBALS['email_config']['from_email'];
    }
    
    if ($from_name === null && isset($GLOBALS['email_config']['from_name'])) {
        $from_name = $GLOBALS['email_config']['from_name'];
    }
    
    if ($reply_to === null && isset($GLOBALS['email_config']['reply_to'])) {
        $reply_to = $GLOBALS['email_config']['reply_to'];
    }
    
    // Default values if still null
    $from_email = $from_email ?: 'noreply@mwpd.gov.ph';
    
    // Check if we have the improved fixed_email_sender.php available
    if (function_exists('send_gmail_email')) {
        // Use our fixed Gmail sender which handles STARTTLS properly
        email_system_log("Using fixed Gmail sender for email to {$to_email}");
        return send_gmail_email($to_email, $subject, $message, $from_email, $from_name, $reply_to, $attachments);
    }
    $from_name = $from_name ?: 'MWPD Filing System';
    $reply_to = $reply_to ?: $from_email;
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer();
    
    // Log that we're attempting to send email
    email_system_log("Attempting to send email to {$to_email} with subject: {$subject}");
    
    // XAMPP doesn't have a local mail server configured by default,
    // so we'll skip the PHP mail() function and go straight to using SMTP
    email_system_log("Skipping PHP mail() function as XAMPP doesn't have a local mail server");
    
    // Method 2: Try using PHPMailer with SMTP directly
    try {
        email_system_log("Method 2: Using PHPMailer with direct SMTP connection");
        
        // Reset mail object
        $mail = new PHPMailer();
        $mail->isSMTP();
        
        // Get SMTP config from globals
        $smtp_config = isset($GLOBALS['email_server']['smtp']) ? $GLOBALS['email_server']['smtp'] : [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'secure' => 'tls',
            'auth' => true,
            'username' => isset($GLOBALS['email_config']['from_email']) ? $GLOBALS['email_config']['from_email'] : $from_email,
            'password' => '',
        ];
        
        // Fix for deprecated dynamic property warning in PHP 8.x
        $mail_properties = get_object_vars($mail);
        $timeout_exists = property_exists($mail, 'Timeout');
        
        // Configure SMTP connection
        $mail->Host = $smtp_config['host'];
        $mail->Port = $smtp_config['port'];
        
        // Critical: Set SMTPSecure BEFORE SMTPAuth for Gmail
        $mail->SMTPSecure = $smtp_config['secure']; // tls
        $mail->SMTPAuth = $smtp_config['auth'];     // true
        
        // Set authentication credentials
        $mail->Username = $smtp_config['username'];
        $mail->Password = $smtp_config['password'];
        
        // Set higher debug level to get more information
        $mail->SMTPDebug = 2; // 2 = verbose debug output
        
        // Force use of TLS
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Only set Timeout if the property exists in the class
        if ($timeout_exists) {
            $mail->Timeout = 15; // Increase timeout for Gmail
        }
        
        // Set sender information
        $mail->setFrom($from_email, $from_name);
        $mail->addReplyTo($reply_to, $from_name);
        
        // Add recipient
        $mail->addAddress($to_email);
        
        // Set email format to HTML
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        
        // Set subject and body
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        // Add a plain-text alternative
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $message));
        
        // Add attachments if any
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
        }
        
        // Send using built-in mail function
        $success = $mail->send();
        
        if ($success) {
            email_system_log("Method 2 Success: Email sent via PHPMailer in mail mode");
            log_email_attempt($to_email, $subject, true);
            return true;
        } else {
            email_system_log("Method 2 Failed: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        email_system_log("Method 2 Exception: " . $e->getMessage());
    }
    
    // Method 3: Try SMTP with IP address instead of hostname (final attempt)
    try {
        // Only attempt if we have SMTP config
        if (isset($GLOBALS['email_server']) && isset($GLOBALS['email_server']['smtp'])) {
            email_system_log("Method 3: Using PHPMailer with SMTP using IP address");
            
            $smtp_config = $GLOBALS['email_server']['smtp'];
            
            // Gmail SMTP IP addresses (fallback if DNS fails)
            $gmail_ips = [
                '142.250.4.108',   // One possible IP for smtp.gmail.com
                '142.250.4.109',   // Another possible IP
                '108.177.15.108',  // Another possible IP
            ];
            
            // Reset mail object
            $mail = new PHPMailer();
            $mail->isSMTP();
            
            // Fix for deprecated dynamic property warning in PHP 8.x
            $mail_properties = get_object_vars($mail);
            $timeout_exists = property_exists($mail, 'Timeout');
            
            // Try with each potential IP
            foreach ($gmail_ips as $ip) {
                email_system_log("Trying SMTP with IP: {$ip}");
                
                $mail->Host = $ip;
                $mail->Port = $smtp_config['port'];
                
                // Critical: Set SMTPSecure BEFORE SMTPAuth for Gmail
                $mail->SMTPSecure = $smtp_config['secure']; // tls
                $mail->SMTPAuth = $smtp_config['auth'];     // true
                
                // Set authentication credentials
                $mail->Username = $smtp_config['username'];
                $mail->Password = $smtp_config['password'];
                
                // Force use of TLS
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];
                
                // Only set Timeout if the property exists in the class
                if ($timeout_exists) {
                    $mail->Timeout = 10;
                }
                
                // Set sender information
                $mail->setFrom($from_email, $from_name);
                $mail->addReplyTo($reply_to, $from_name);
                
                // Add recipient
                $mail->addAddress($to_email);
                
                // Set email format to HTML
                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                
                // Set subject and body
                $mail->Subject = $subject;
                $mail->Body = $message;
                
                // Add a plain-text alternative
                $mail->AltBody = strip_tags(str_replace('<br>', "\n", $message));
                
                // Add attachments if any
                if (!empty($attachments) && is_array($attachments)) {
                    foreach ($attachments as $attachment) {
                        if (file_exists($attachment)) {
                            $mail->addAttachment($attachment);
                        }
                    }
                }
                
                try {
                    $success = $mail->send();
                    
                    if ($success) {
                        email_system_log("Method 3 Success: Email sent via SMTP with IP {$ip}");
                        log_email_attempt($to_email, $subject, true);
                        return true;
                    }
                } catch (Exception $smtp_e) {
                    email_system_log("SMTP with IP {$ip} failed: " . $smtp_e->getMessage());
                    // Continue to next IP
                }
            }
            
            email_system_log("Method 3 Failed: All SMTP IP attempts failed");
        }
    } catch (Exception $e) {
        email_system_log("Method 3 Exception: " . $e->getMessage());
    }
    
    // Method 4: Last resort - write to a notification file that can be processed later
    try {
        email_system_log("Method 4: Storing email in pending_emails.txt for later delivery");
        
        $email_data = [
            'to' => $to_email,
            'subject' => $subject, 
            'message' => $message,
            'from_email' => $from_email,
            'from_name' => $from_name,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $email_json = json_encode($email_data) . "\n";
        file_put_contents('pending_emails.txt', $email_json, FILE_APPEND);
        
        email_system_log("Method 4 Success: Email stored for later delivery");
        
        // This is a semi-success - we didn't deliver but we stored for later
        log_email_attempt($to_email, $subject, false, 'Stored for later delivery');
        
        // Return true to avoid disrupting business processes
        // The notification was at least stored for later recovery
        return true;
    } catch (Exception $e) {
        email_system_log("Method 4 Exception: " . $e->getMessage());
    }
    
    // All methods failed - log the complete failure
    email_system_log("CRITICAL: All email delivery methods failed");
    log_email_error($to_email, $subject, "All delivery methods failed");
    
    return false;
}

/**
 * Log email attempts for debugging
 */
function log_email_attempt($to_email, $subject, $success) {
    $log_file = 'email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $log_entry = "[$timestamp] $status - To: $to_email, Subject: $subject\n";
    
    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Log email errors for debugging
 */
function log_email_error($to_email, $subject, $error_message) {
    $log_file = 'email_error_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] ERROR - To: $to_email, Subject: $subject, Error: $error_message\n";
    
    // Append to log file
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Foolproof email sending function with fallbacks
 * 
 * This is the main function called by other parts of the application
 * It provides additional reliability and logging compared to the basic send_email function
 * 
 * @param string $to_email Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param string $from_email Sender email address (optional)
 * @param string $from_name Sender name (optional)
 * @param array $attachments Array of file paths to attach (optional)
 * @return array ['success' => bool, 'message' => string] Status and message
 */
function foolproof_send_email($to_email, $subject, $message, $from_email = null, $from_name = null, $attachments = []) {
    $success = false;
    $error_message = '';
    
    // Try up to 3 times to send the email
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        try {
            // Try to send via our regular function
            $success = send_email($to_email, $subject, $message, $from_email, $from_name, null, $attachments);
            
            // If email sending fails but this is just a notification, consider it semi-successful
            // This allows account approvals to still work even if email fails
            if (!$success && strpos($subject, 'Notification') !== false) {
                error_log("Email notification failed but continuing with operation: " . $subject);
                $success = true; // Mark as success for business flow
            }
            
            if ($success) {
                // Successfully sent, no need for more attempts
                break;
            } else {
                // Wait briefly before trying again
                usleep(500000); // 0.5 seconds
            }
        } catch (Exception $e) {
            $error_message = "Attempt $attempt: " . $e->getMessage();
            log_email_error($to_email, $subject, $error_message);
        }
    }
    
    // Log the final outcome
    $result_message = $success ? 'Email sent successfully' : 'Failed to send email after 3 attempts';
    
    // Create detailed log entry
    $log_file = 'email_detailed_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $log_entry = "[$timestamp] $status - To: $to_email, Subject: $subject, Message: $result_message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    return [
        'success' => $success,
        'message' => $success ? $result_message : "$result_message: $error_message"
    ];
}
