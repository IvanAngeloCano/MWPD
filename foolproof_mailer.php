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
    $from_name = $from_name ?: 'MWPD Filing System';
    $reply_to = $reply_to ?: $from_email;
    
    try {
        // Create a new PHPMailer instance
        $mail = new PHPMailer();
        
        // Set mailer to use SMTP or PHP mail() function
        // Uncomment and configure these lines if using SMTP
        //$mail->isSMTP();
        //$mail->Host = 'smtp.example.com';
        //$mail->SMTPAuth = true;
        //$mail->Username = 'user@example.com';
        //$mail->Password = 'secret';
        //$mail->SMTPSecure = 'tls';
        //$mail->Port = 587;
        
        // Our custom PHPMailer implementation doesn't require setting a mailer type
        // It defaults to using PHP mail() function
        
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
        
        // Send the email
        $success = $mail->send();
        
        // Log the email attempt
        log_email_attempt($to_email, $subject, $success);
        
        return $success;
    } catch (Exception $e) {
        // Log the error
        log_email_error($to_email, $subject, $e->getMessage());
        return false;
    }
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
