<?php
/**
 * Unified Email System for MWPD
 * 
 * This file integrates all email components into one streamlined system:
 * 1. Professional email templates
 * 2. Fixed Gmail SMTP connectivity
 * 3. Error handling and fallbacks
 */

// Include required components
require_once 'fixed_email_sender.php';
require_once 'email_templates.php';

// Skip foolproof_mailer to avoid circular references
// require_once 'foolproof_mailer.php';

// Set up consistent email configuration
if (!isset($GLOBALS['email_config'])) {
    $GLOBALS['email_config'] = [
        'from_email' => 'noreply@mwpd.gov.ph',
        'from_name' => 'MWPD Filing System',
        'reply_to' => 'support@mwpd.gov.ph',
        'signature' => '<p>Thank you,<br>MWPD Administration</p>',
        'logo_url' => 'https://mwpd.gov.ph/wp-content/uploads/2022/10/DMW-Logo.png',
    ];
}

// Set up consistent server configuration
if (!isset($GLOBALS['email_server'])) {
    $GLOBALS['email_server'] = [
        'enabled' => true,
        'method' => 'smtp',
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'secure' => 'tls',
            'auth' => true,
            'username' => 'luxsmith656@gmail.com',
            'password' => 'lxnfpqehppfhopgv',
            'debug' => 2,
            'options' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]
        ],
    ];
}

/**
 * Main function for sending any MWPD email
 * This will completely bypass the old email system and use our fixed Gmail sender
 * 
 * @param string $to_email Recipient email address
 * @param string $subject Email subject
 * @param string $html_content Email HTML content
 * @param string $message_type Message type for logging
 * @return bool Whether the email was sent successfully
 */
function unified_send_email($to_email, $subject, $html_content, $message_type = 'general') {
    // Log the attempt in both systems for compatibility
    error_log(date('Y-m-d H:i:s') . " - [UNIFIED] Sending {$message_type} email to {$to_email}");
    
    // Log in the standard format if the function exists
    if (function_exists('logEmailActivity')) {
        logEmailActivity($to_email, $subject, $message_type, 'attempting');
    }
    
    // Use our fixed Gmail sender directly
    $result = send_gmail_email(
        $to_email,
        $subject,
        $html_content,
        isset($GLOBALS['email_config']['from_email']) ? $GLOBALS['email_config']['from_email'] : null,
        isset($GLOBALS['email_config']['from_name']) ? $GLOBALS['email_config']['from_name'] : null
    );
    
    // Log the result in both systems
    $status = $result ? 'success' : 'failed';
    error_log(date('Y-m-d H:i:s') . " - [UNIFIED] Email to {$to_email} status: {$status}");
    
    // Log in the standard format if the function exists
    if (function_exists('logEmailActivity')) {
        logEmailActivity($to_email, $subject, $message_type, $status);
    }
    
    // If failed, store for later recovery
    if (!$result) {
        try {
            $email_data = [
                'to' => $to_email,
                'subject' => $subject, 
                'message' => $html_content,
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => $message_type
            ];
            
            $email_json = json_encode($email_data) . "\n";
            file_put_contents('pending_emails.txt', $email_json, FILE_APPEND);
        } catch (Exception $e) {
            error_log(date('Y-m-d H:i:s') . " - [UNIFIED] Failed to store pending email: " . $e->getMessage());
        }
    }
    
    return $result;
}

/**
 * Send a password reset email using the professional template
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $username Username
 * @param string $temp_password Temporary password
 * @return bool Whether the email was sent successfully
 */
function unified_send_password_reset($to_email, $full_name, $username, $temp_password) {
    // Get base URL for login link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    $login_url = $base_url . "/login.php";
    
    // Generate professional email using our template
    $html_content = create_password_reset_email($full_name, $username, $temp_password, $login_url);
    
    // Send using our unified sender
    return unified_send_email($to_email, "MWPD System - Password Reset", $html_content, "password_reset");
}

/**
 * Send an account approval email using the professional template
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $username Username
 * @param string $temp_password Temporary password
 * @return bool Whether the email was sent successfully
 */
function unified_send_account_approval($to_email, $full_name, $username, $temp_password) {
    // Get base URL for login link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    $login_url = $base_url . "/login.php";
    
    // Generate professional email using our template
    $html_content = create_account_approval_email($full_name, $username, $temp_password, $login_url);
    
    // Send using our unified sender
    return unified_send_email($to_email, "MWPD System - Account Approved", $html_content, "account_approval");
}

/**
 * Send an account rejection email using the professional template
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $username Username
 * @param string $rejection_reason Reason for rejection
 * @return bool Whether the email was sent successfully
 */
function unified_send_account_rejection($to_email, $full_name, $username, $rejection_reason = '') {
    // Generate professional email using our template
    $html_content = create_account_rejection_email($full_name, $username, $rejection_reason);
    
    // Send using our unified sender
    return unified_send_email($to_email, "MWPD System - Account Request Status", $html_content, "account_rejection");
}

/**
 * Send a record submission notification using the professional template
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $record_type Type of record
 * @param string $record_id Record ID
 * @param string $record_name Record name
 * @param string $submitter_name Submitter's name
 * @return bool Whether the email was sent successfully
 */
function unified_send_record_submission($to_email, $full_name, $record_type, $record_id, $record_name, $submitter_name) {
    // Get base URL for view link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    $view_url = $base_url . "/" . strtolower(str_replace(' ', '_', $record_type)) . "_view.php?id=" . urlencode($record_id);
    
    // Generate professional email using our template
    $html_content = create_record_submission_email($full_name, $record_type, $record_id, $record_name, $submitter_name, $view_url);
    
    // Send using our unified sender
    return unified_send_email($to_email, "MWPD System - New Record Submission", $html_content, "record_submission");
}

/**
 * Send a notification to Regional Directors about new user account requests
 * 
 * @param string $to_email Recipient email address (Regional Director)
 * @param string $requester_name Name of person who requested the account
 * @param string $username Requested username
 * @param string $full_name Full name of the requested account
 * @param string $role Requested role
 * @return bool Whether the email was sent successfully
 */
function unified_send_new_account_request($to_email, $requester_name, $username, $full_name, $role) {
    // Get base URL for approval link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    $approval_url = $base_url . "/account_approvals.php";
    
    // Create a professional email using a notification template
    $subject = "MWPD System - New Account Request";
    $message = '<p>A new user account has been requested in the MWPD Filing System and requires your approval.</p>';
    $message .= '<div style="background-color: #f0f7ff; border-left: 4px solid #0056b3; padding: 15px; margin: 15px 0;">';
    $message .= '<p><strong>Account Details:</strong></p>';
    $message .= '<p>Username: <strong>' . htmlspecialchars($username) . '</strong><br>';
    $message .= 'Full Name: <strong>' . htmlspecialchars($full_name) . '</strong><br>';
    $message .= 'Role: <strong>' . htmlspecialchars($role) . '</strong><br>';
    $message .= 'Requested by: <strong>' . htmlspecialchars($requester_name) . '</strong></p>';
    $message .= '</div>';
    
    // Use a generic template for this notification
    $html_content = create_notification_email(
        "Regional Director", 
        $subject, 
        $message, 
        "Review Account Request", 
        $approval_url
    );
    
    // Send using our unified sender
    return unified_send_email($to_email, $subject, $html_content, "new_account_request");
}

/**
 * OVERRIDE FUNCTIONS BELOW
 * These functions replace existing email functions in the system
 */

// Override sendPasswordResetEmail
if (!function_exists('sendPasswordResetEmail')) {
    function sendPasswordResetEmail($to, $full_name, $username, $temp_password) {
        return unified_send_password_reset($to, $full_name, $username, $temp_password);
    }
} else {
    // Save original function and replace it
    $GLOBALS['original_sendPasswordResetEmail'] = 'sendPasswordResetEmail';
    
    // PHPMailer might prevent function redefinition, so we'll modify reset_password.php instead
}

// Override sendNewAccountRequestEmail for account_add.php
if (!function_exists('sendNewAccountRequestEmail')) {
    function sendNewAccountRequestEmail($to, $requester_name, $username, $full_name, $role) {
        return unified_send_new_account_request($to, $requester_name, $username, $full_name, $role);
    }
} else {
    // Save original function and replace it
    $GLOBALS['original_sendNewAccountRequestEmail'] = 'sendNewAccountRequestEmail';
}

// The rest of the overrides follow the same pattern...

?>
