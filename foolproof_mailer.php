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
        
        // Use PHP mail() function by default
        $mail->isMail();
        
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
