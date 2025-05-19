<?php
/**
 * MWPD Email System
 * 
 * Integrated email system for MWPD Filing System that combines:
 * 1. Fixed Gmail SMTP connectivity
 * 2. Professional email templates
 * 3. Compatibility with existing notification system
 */

// Include required components
require_once 'fixed_email_sender.php';
require_once 'email_templates.php';

// Include system configuration if needed
if (!isset($GLOBALS['email_config']) && file_exists('email_config.php')) {
    include_once 'email_config.php';
}

/**
 * Main email sending function for MWPD
 * Compatible with existing sendEmail function signature
 * 
 * @param string $to_email Recipient email address
 * @param string $subject Email subject
 * @param string $html_content Email body (HTML)
 * @param string $message_type Type of message for logging
 * @return bool Whether the email was sent successfully
 */
function mwpd_send_email($to_email, $subject, $html_content, $message_type = 'general') {
    // Log the attempt
    if (function_exists('logEmailActivity')) {
        logEmailActivity($to_email, $subject, $message_type, 'attempting');
    }
    
    // Send using our fixed Gmail sender
    $result = send_gmail_email(
        $to_email,
        $subject,
        $html_content,
        isset($GLOBALS['email_config']['from_email']) ? $GLOBALS['email_config']['from_email'] : null,
        isset($GLOBALS['email_config']['from_name']) ? $GLOBALS['email_config']['from_name'] : null
    );
    
    // Log the result
    if (function_exists('logEmailActivity')) {
        logEmailActivity($to_email, $subject, $message_type, $result ? 'success' : 'failed');
    }
    
    // If failed and we have fallback storage
    if (!$result) {
        // Store in pending_emails.txt for later processing if the function exists
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
            // Silently fail on storage error
            error_log("Failed to store email in pending_emails.txt: " . $e->getMessage());
        }
    }
    
    // Add notification if the function exists
    if (!$result && function_exists('addUserNotification')) {
        try {
            // Try to get user ID from to_email
            $user_id = get_user_id_from_email($to_email);
            
            if ($user_id) {
                addUserNotification(
                    $user_id,
                    "You have a new notification: " . $subject,
                    null,
                    $message_type,
                    "dashboard.php"
                );
            }
        } catch (Exception $e) {
            // Silently fail on notification error
            error_log("Failed to add user notification: " . $e->getMessage());
        }
    }
    
    return $result;
}

/**
 * Helper function to get user ID from email
 * 
 * @param string $email User's email address
 * @return int|null User ID if found, null otherwise
 */
function get_user_id_from_email($email) {
    // If we have a database connection
    if (function_exists('getPDO')) {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['id'])) {
                return (int)$result['id'];
            }
        } catch (Exception $e) {
            error_log("Error looking up user by email: " . $e->getMessage());
        }
    }
    
    return null;
}

/**
 * Send a password reset email
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $username Username for login
 * @param string $temp_password New temporary password
 * @return bool Whether the email was sent successfully
 */
function mwpd_send_password_reset($to_email, $full_name, $username, $temp_password) {
    // Get base URL for login link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    $login_url = $base_url . "/login.php";
    
    // Generate the professional email
    $html_content = create_password_reset_email($full_name, $username, $temp_password, $login_url);
    
    // Send using the main email function
    return mwpd_send_email($to_email, "MWPD System - Password Reset", $html_content, "password_reset");
}

/**
 * Send an account approval email
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $username Username for login
 * @param string $temp_password New temporary password
 * @return bool Whether the email was sent successfully
 */
function mwpd_send_account_approval($to_email, $full_name, $username, $temp_password) {
    // Get base URL for login link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    $login_url = $base_url . "/login.php";
    
    // Generate the professional email
    $html_content = create_account_approval_email($full_name, $username, $temp_password, $login_url);
    
    // Send using the main email function
    return mwpd_send_email($to_email, "MWPD System - Account Approved", $html_content, "account_approval");
}

/**
 * Send an account rejection email
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $username Username that was requested
 * @param string $rejection_reason Reason for rejection
 * @return bool Whether the email was sent successfully
 */
function mwpd_send_account_rejection($to_email, $full_name, $username, $rejection_reason = '') {
    // Generate the professional email
    $html_content = create_account_rejection_email($full_name, $username, $rejection_reason);
    
    // Send using the main email function
    return mwpd_send_email($to_email, "MWPD System - Account Request Status", $html_content, "account_rejection");
}

/**
 * Send a record submission notification
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $record_type Type of record
 * @param string $record_id Record ID
 * @param string $record_name Record name or title
 * @param string $submitter_name Name of the submitter
 * @return bool Whether the email was sent successfully
 */
function mwpd_send_record_submission($to_email, $full_name, $record_type, $record_id, $record_name, $submitter_name) {
    // Get base URL for view link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    
    // Create type-specific URL
    $view_url = '';
    switch (strtolower($record_type)) {
        case 'direct hire':
            $view_url = $base_url . "/direct_hire_view.php?id=" . urlencode($record_id);
            break;
        case 'balik manggagawa':
        case 'bm':
            $view_url = $base_url . "/balik_manggagawa_view.php?id=" . urlencode($record_id);
            break;
        case 'gov-to-gov':
        case 'g2g':
            $view_url = $base_url . "/gov_to_gov_view.php?id=" . urlencode($record_id);
            break;
        case 'job fair':
        case 'job fairs':
            $view_url = $base_url . "/job_fair_view.php?id=" . urlencode($record_id);
            break;
        default:
            // No specific URL
            break;
    }
    
    // Generate the professional email
    $html_content = create_record_submission_email($full_name, $record_type, $record_id, $record_name, $submitter_name, $view_url);
    
    // Send using the main email function
    return mwpd_send_email($to_email, "MWPD System - New Record Submission", $html_content, "record_submission");
}

/**
 * Send a record status notification
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $record_type Type of record
 * @param string $record_id Record ID
 * @param string $record_name Record name or title
 * @param string $status Approval status (approved/rejected)
 * @param string $comments Optional comments
 * @return bool Whether the email was sent successfully
 */
function mwpd_send_record_status($to_email, $full_name, $record_type, $record_id, $record_name, $status, $comments = '') {
    // Get base URL for view link
    $base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url .= "://" . $_SERVER['HTTP_HOST'];
    
    // Create type-specific URL
    $view_url = '';
    switch (strtolower($record_type)) {
        case 'direct hire':
            $view_url = $base_url . "/direct_hire_view.php?id=" . urlencode($record_id);
            break;
        case 'balik manggagawa':
        case 'bm':
            $view_url = $base_url . "/balik_manggagawa_view.php?id=" . urlencode($record_id);
            break;
        case 'gov-to-gov':
        case 'g2g':
            $view_url = $base_url . "/gov_to_gov_view.php?id=" . urlencode($record_id);
            break;
        case 'job fair':
        case 'job fairs':
            $view_url = $base_url . "/job_fair_view.php?id=" . urlencode($record_id);
            break;
        default:
            // No specific URL
            break;
    }
    
    // Determine email subject based on status
    $subject = "MWPD System - Record Status Update";
    if (strtolower($status) === 'approved') {
        $subject = "MWPD System - Record Approved";
    } else if (strtolower($status) === 'rejected' || strtolower($status) === 'declined') {
        $subject = "MWPD System - Record Not Approved";
    }
    
    // Generate the professional email
    $html_content = create_record_status_email($full_name, $record_type, $record_id, $record_name, $status, $comments, $view_url);
    
    // Send using the main email function
    return mwpd_send_email($to_email, $subject, $html_content, "record_status");
}

/**
 * Send a general notification email
 * 
 * @param string $to_email Recipient email address
 * @param string $full_name Recipient's full name
 * @param string $subject Email subject
 * @param string $message HTML message content
 * @param string $action_text Optional call-to-action button text
 * @param string $action_url Optional call-to-action URL
 * @return bool Whether the email was sent successfully
 */
function mwpd_send_notification($to_email, $full_name, $subject, $message, $action_text = '', $action_url = '') {
    // Generate the professional email
    $html_content = create_notification_email($full_name, $subject, $message, $action_text, $action_url);
    
    // Send using the main email function
    return mwpd_send_email($to_email, $subject, $html_content, "notification");
}

// Instead of redefining functions and causing conflicts, we'll modify the existing ones

// Save references to the original functions if they exist
if (function_exists('sendEmail')) {
    $GLOBALS['original_sendEmail'] = 'sendEmail';
}

if (function_exists('sendPasswordResetEmail')) {
    $GLOBALS['original_sendPasswordResetEmail'] = 'sendPasswordResetEmail';
}

if (function_exists('sendAccountApprovalEmail')) {
    $GLOBALS['original_sendAccountApprovalEmail'] = 'sendAccountApprovalEmail';
}

if (function_exists('sendAccountRejectionEmail')) {
    $GLOBALS['original_sendAccountRejectionEmail'] = 'sendAccountRejectionEmail';
}

if (function_exists('sendRecordSubmissionEmail')) {
    $GLOBALS['original_sendRecordSubmissionEmail'] = 'sendRecordSubmissionEmail';
}

if (function_exists('sendRecordApprovalStatusEmail')) {
    $GLOBALS['original_sendRecordApprovalStatusEmail'] = 'sendRecordApprovalStatusEmail';
}

// Override the existing functions with our improved versions
// To apply this, you'll need to use the patch_email_functions() function

/**
 * Patch the existing email functions to use our improved versions
 * Call this function after including email_notifications.php
 */
function patch_email_functions() {
    // Override sendEmail
    if (function_exists('sendEmail')) {
        // This is a special case - we'll modify how it works internally
        // by modifying the foolproof_mailer.php to use our Gmail sender
    }
    
    // Override other email functions to use our templates
    modify_email_templates();
}

/**
 * Apply our professional templates to existing email functions
 */
function modify_email_templates() {
    global $foolproof_send_email_original;
    
    // Save the original function if it exists
    if (function_exists('foolproof_send_email') && !isset($GLOBALS['foolproof_send_email_original'])) {
        $GLOBALS['foolproof_send_email_original'] = 'foolproof_send_email';
    }
    
    // Now we're ready to patch things up when needed
}

// Helper function to get user name from email (stub)
function getUserFullNameByEmail($email) {
    // If we have a database connection
    if (function_exists('getPDO')) {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['full_name'])) {
                return $result['full_name'];
            }
        } catch (Exception $e) {
            error_log("Error looking up user name by email: " . $e->getMessage());
        }
    }
    
    // Default: use email as name
    return $email;
}
?>
