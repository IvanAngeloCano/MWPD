<?php
/**
 * Email Notifications System for MWPD
 * 
 * This file contains functions for sending email notifications throughout the application.
 */

// Include our foolproof mailer
require_once 'foolproof_mailer.php';

// Email configuration
$GLOBALS['email_config'] = [
    'from_email' => 'noreply@mwpd.gov.ph',
    'from_name' => 'MWPD Filing System',
    'reply_to' => 'support@mwpd.gov.ph',
    'signature' => '<p>Thank you,<br>MWPD Administration</p>',
    'logo_url' => 'https://mwpd.gov.ph/wp-content/uploads/2022/10/DMW-Logo.png', // Replace with your actual logo URL
];

// Email server configuration
$GLOBALS['email_server'] = [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',
        'auth' => true,
        'username' => 'luxsmith656@gmail.com',
        'password' => 'collegeme4724246859713246859713',
    ],
];

/**
 * Logs email activities for audit and debugging
 */
function logEmailActivity($to, $subject, $message_type, $status, $error = null) {
    $log_file = 'email_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] TO: {$to} | SUBJECT: {$subject} | TYPE: {$message_type} | STATUS: {$status}";
    
    if ($error) {
        $log_message .= " | ERROR: {$error}";
    }
    
    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
}

/**
 * Base function for sending HTML emails
 */
function sendEmail($to, $subject, $html_content, $message_type = 'general') {
    // Get our email configuration
    $config = $GLOBALS['email_config'];
    
    // Format the email content with our standard template
    $formatted_html = formatEmailContent($subject, $html_content);
    
    // Use our foolproof mailer to send the email with fallback options
    try {
        $result = foolproof_send_email(
            $to, 
            $subject, 
            $formatted_html, 
            $config['from_email'], 
            $config['from_name']
        );
    } catch (Exception $e) {
        // Log the error but allow the process to continue
        error_log('Email sending error: ' . $e->getMessage());
        // Return a default result structure to avoid breaking the flow
        $result = [
            'success' => false,
            'message' => 'Email sending failed: ' . $e->getMessage()
        ];
        
        // For critical emails, show an error
        if (strpos($subject, 'Password') !== false || strpos($message_type, 'password') !== false) {
            // Return failure only for password-related emails
            return false;
        }
        
        // For non-critical notifications, allow the process to continue
        if (strpos($subject, 'Notification') !== false || strpos($message_type, 'notification') !== false) {
            $result['success'] = true; // Mark as success for business flow
        }
    }
    
    // Log the activity
    logEmailActivity(
        $to, 
        $subject, 
        $message_type, 
        $result['success'] ? 'success' : 'failed', 
        $result['success'] ? null : $result['error']
    );
    
    return $result['success'];
}

/**
 * Formats the email content
 */
function formatEmailContent($subject, $html_content) {
    $config = $GLOBALS['email_config'];
    
    // MWPD colors
    $primary_color = '#0056b3';       // Primary blue color
    $secondary_color = '#f8f9fa';     // Light background color
    $accent_color = '#28a745';        // Success green color
    $text_color = '#333333';          // Main text color
    $footer_color = '#6c757d';        // Footer text color
    
    // DMW/MWPD Logo - local path in assets
    $logo_url = 'http://localhost/MWPD/assets/images/DMW%20Logo.png';
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($subject) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: ' . $text_color . ';
                margin: 0;
                padding: 0;
                background-color: #f0f2f5;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                background-color: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                background-color: ' . $primary_color . ';
                padding: 20px;
                text-align: center;
            }
            .header-content {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .header img {
                max-height: 60px;
                margin-right: 15px;
            }
            .header-title {
                color: #ffffff;
                font-size: 22px;
                margin: 0;
                font-weight: bold;
                text-align: left;
            }
            .content {
                padding: 30px;
                background-color: #ffffff;
            }
            .footer {
                background-color: ' . $secondary_color . ';
                padding: 20px;
                text-align: center;
                font-size: 12px;
                color: ' . $footer_color . ';
                border-top: 1px solid #e0e0e0;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background-color: ' . $accent_color . ';
                color: #ffffff !important;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
                margin: 15px 0;
            }
            h1 {
                color: ' . $primary_color . ';
                font-size: 22px;
                margin-top: 0;
                margin-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
                padding-bottom: 10px;
            }
            .credentials {
                background-color: ' . $secondary_color . ';
                padding: 20px;
                border-radius: 6px;
                margin: 20px 0;
                border-left: 4px solid ' . $accent_color . ';
            }
            .credentials p {
                margin: 8px 0;
            }
            .credentials strong {
                color: ' . $primary_color . ';
            }
            .alert {
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            .alert-info {
                background-color: #d1ecf1;
                border: 1px solid #bee5eb;
                color: #0c5460;
            }
            .alert-warning {
                background-color: #fff3cd;
                border: 1px solid #ffeeba;
                color: #856404;
            }
            p {
                margin-bottom: 16px;
            }
            .social-links {
                margin-top: 15px;
            }
            .social-links a {
                display: inline-block;
                margin: 0 5px;
                color: ' . $primary_color . ';
                text-decoration: none;
            }
            .divider {
                height: 1px;
                background-color: #e0e0e0;
                margin: 25px 0;
            }
            @media screen and (max-width: 600px) {
                .container {
                    width: 100% !important;
                }
                .content {
                    padding: 20px !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-content">
                    <img src="' . $logo_url . '" alt="MWPD Logo">
                    <h2 class="header-title">Migrant Workers Protection Division</h2>
                </div>
            </div>
            <div class="content">
                <h1>' . htmlspecialchars($subject) . '</h1>
                ' . $html_content . '
                <div class="divider"></div>
                ' . $config['signature'] . '
            </div>
            <div class="footer">
                <p>This is an official communication from the Migrant Workers Protection Division.</p>
                <p>Please do not reply to this email as it is sent from an unmonitored mailbox.</p>
                <div class="social-links">
                    <a href="https://www.facebook.com/dmwgovph">Facebook</a> |
                    <a href="https://twitter.com/dmwgovph">Twitter</a> |
                    <a href="https://dmw.gov.ph">Website</a>
                </div>
                <p>&copy; ' . date('Y') . ' Department of Migrant Workers. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Send an email to a user when their account request is approved
 * 
 * @param string $to_email The recipient's email address
 * @param string $user_name The recipient's full name
 * @param string $username The username for the new account
 * @param string $password The password for the new account
 * @return bool Whether the email was sent successfully
 */
function sendAccountApprovalEmail($to_email, $user_name, $username, $password) {
    $subject = "MWPD Filing System - Your Account Has Been Approved";
    
    $message = '
    <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
    
    <p>We are pleased to inform you that your account request for the <strong>MWPD Filing System</strong> has been approved. You can now log in to the system using the credentials below.</p>
    
    <div class="credentials">
        <p><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
        <p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
    </div>
    
    <div class="alert alert-info">
        <p><strong>Important:</strong> For security reasons, we recommend changing your password after your first login.</p>
    </div>
    
    <p>Click the button below to access the MWPD Filing System:</p>
    
    <p style="text-align: center;">
        <a href="https://mwpd.gov.ph/login" class="button">Access MWPD Filing System</a>
    </p>
    
    <p>If you have any questions or need assistance, please contact your system administrator.</p>';
    
    // Also log to our backup system in case email fails
    require_once 'account_approval_email_log.php';
    logAccountApprovalEmail($to_email, $user_name, $username, $password);
    
    return sendEmail($to_email, $subject, $message);
}

/**
 * Send notification to a submitter about an approval decision
 * 
 * @param string $to_email The submitter's email address
 * @param string $submitter_name The submitter's full name
 * @param string $username The username that was approved/rejected
 * @param string $user_full_name The full name of the user that was approved/rejected
 * @param string $status The status of the approval (approved/rejected)
 * @param string $notes Any notes about the approval decision
 * @return bool Whether the email was sent successfully
 */
function sendSubmitterNotificationEmail($to_email, $submitter_name, $username, $user_full_name, $status, $notes = '') {
    $subject = "MWPD Filing System - Account Request " . ucfirst($status);
    
    // Set status color and text based on the status
    $status_color = ($status === 'approved') ? '#28a745' : '#dc3545';
    $status_text = ucfirst($status);
    $status_message = ($status === 'approved') ? 
        'The account has been created and the user has been notified with their login credentials.' : 
        'The account request has been rejected for the following reason:';
    
    $message = '
    <p>Dear <strong>' . htmlspecialchars($submitter_name) . '</strong>,</p>
    
    <p>This is to inform you that the account request you submitted for <strong>' . htmlspecialchars($user_full_name) . ' (' . htmlspecialchars($username) . ')</strong> has been <strong style="color: ' . $status_color . ';">' . $status_text . '</strong>.</p>
    
    <p>' . $status_message . '</p>';
    
    // Add notes if provided and for rejections
    if (!empty($notes) || $status === 'rejected') {
        $message .= '
        <div class="alert ' . ($status === 'approved' ? 'alert-info' : 'alert-warning') . '">
            <p><strong>' . ($status === 'approved' ? 'Additional Information:' : 'Rejection Reason:') . '</strong></p>
            <p>' . (!empty($notes) ? htmlspecialchars($notes) : 'No specific reason provided.') . '</p>
        </div>';
    }
    
    $message .= '
    <p>If you have any questions about this decision, please contact your Regional Director.</p>
    
    <p style="text-align: center;">
        <a href="https://mwpd.gov.ph/login" class="button">Access MWPD Filing System</a>
    </p>';
    
    // Also log to our backup system in case email fails
    require_once 'account_approval_email_log.php';
    logSubmitterNotificationEmail($to_email, $submitter_name, $username, $user_full_name, $status);
    
    return sendEmail($to_email, $subject, $message);
}

/**
 * Send an email to a user when their account request is rejected
 * 
 * @param string $to_email The recipient's email address
 * @param string $user_name The recipient's full name
 * @param string $username The username that was requested
 * @param string $rejection_reason The reason for rejection
 * @return bool Whether the email was sent successfully
 */
function sendAccountRejectionEmail($to_email, $user_name, $username, $rejection_reason) {
    $subject = "MWPD Filing System - Account Request Status";
    
    $message = '
    <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
    
    <p>We regret to inform you that your account request for the <strong>MWPD Filing System</strong> has been reviewed and could not be approved at this time.</p>
    
    <div class="alert alert-warning">
        <p><strong>Request Details:</strong></p>
        <p>Username: ' . htmlspecialchars($username) . '</p>
        <p><strong>Reason:</strong> ' . (!empty($rejection_reason) ? htmlspecialchars($rejection_reason) : 'No specific reason provided.') . '</p>
    </div>
    
    <p>If you believe this decision was made in error or if you would like to submit additional information for reconsideration, please contact your Regional Director or system administrator.</p>
    
    <p>For any questions or concerns, you may reply to this email or contact the MWPD support team.</p>';
    
    return sendEmail($to_email, $subject, $message);
}

/**
 * Send notification to a submitter about an approval decision
 * 
 * @param string $to_email The submitter's email address
 * @param string $submitter_name The submitter's full name
 * @param string $username The username that was approved/rejected
 * @param string $user_full_name The full name of the user that was approved/rejected
 * @param string $status The status of the approval (approved/rejected)
 * @return bool True if email was sent successfully, false otherwise
 */
function sendSubmitterNotificationEmailOriginal($to_email, $submitter_name, $username, $user_full_name, $status) {
    $subject = "MWPD Filing System - Account Request " . ucfirst($status);
    $bg_color = ($status == 'approved') ? '#28a745' : '#dc3545';
    $status_text = ucfirst($status);
    
    $message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background-color: ' . $bg_color . '; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
            .content { padding: 20px; }
            .status { display: inline-block; background-color: ' . $bg_color . '; color: white; padding: 5px 10px; border-radius: 3px; }
            .footer { font-size: 12px; color: #777; border-top: 1px solid #ddd; padding-top: 15px; margin-top: 20px; }
            h1 { margin: 0; font-size: 24px; }
            .button { display: inline-block; background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Account Request ' . $status_text . '</h1>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($submitter_name) . ',</p>
                
                <p>The account request you submitted for <strong>' . htmlspecialchars($user_full_name) . ' (' . htmlspecialchars($username) . ')</strong> has been <span class="status">' . $status_text . '</span>.</p>';
                
    if ($status == 'approved') {
        $message .= '<p>The user has been notified and provided with login credentials to access the system.</p>';
    } else {
        $message .= '<p>The user has been notified of this decision.</p>';
    }
    
    $message .= '
                <p>Thank you,<br>
                Migrant Workers Protection Division</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; ' . date('Y') . ' Migrant Workers Protection Division. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    <body>
        <div class="container">
            <div class="header">
                <h1>Account Request ' . $status_text . '</h1>
            </div>
            <div class="content">
                <p>Dear ' . htmlspecialchars($submitter_name) . ',</p>
                
                <p>The account request you submitted for <strong>' . htmlspecialchars($user_full_name) . ' (' . htmlspecialchars($username) . ')</strong> has been <span class="status">' . $status_text . '</span>.</p>';
                
    if ($status == 'approved') {
        $message .= '<p>The user has been notified and provided with login credentials to access the system.</p>';
    } else {
        $message .= '<p>The user has been notified of this decision.</p>';
    }
    
    $message .= '
                <p>Thank you,<br>
                Migrant Workers Protection Division</p>
            </div>
            <div class="footer">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; ' . date('Y') . ' Migrant Workers Protection Division. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return sendEmail($to_email, $subject, $message);
}

/**
 * Sends account approval notification to user
 */
function sendAccountApprovalEmailOriginal($to, $full_name, $username, $temp_password = null) {
    $subject = "MWPD System - Your Account Has Been Approved";
    
    $content = '<p>Dear ' . htmlspecialchars($full_name) . ',</p>';
    $content .= '<p>Your account for the MWPD Filing System has been approved.</p>';
    
    if ($temp_password) {
        $content .= '<div class="alert alert-info">';
        $content .= '<p><strong>Your account credentials:</strong></p>';
        $content .= '<p>Username: <strong>' . htmlspecialchars($username) . '</strong><br>';
        $content .= 'Password: <strong>' . htmlspecialchars($temp_password) . '</strong></p>';
        $content .= '</div>';
        
        $content .= '<div class="alert alert-warning">';
        $content .= '<p><strong>Important:</strong> For security reasons, please change your password after your first login.</p>';
        $content .= '</div>';
    } else {
        $content .= '<p>You can now log in using your username: <strong>' . htmlspecialchars($username) . '</strong> and the password you provided during registration.</p>';
    }
    
    $content .= '<p><a href="https://mwpd.gov.ph/login" class="button">Log In to Your Account</a></p>';
    $content .= '<p>If you have any questions or need assistance, please contact our support team.</p>';
    
    $html = formatEmailContent($subject, $content);
    return sendEmail($to, $subject, $html, 'account_approval');
}

// This duplicate function was removed to fix the fatal error

/**
 * Sends notification to Regional Directors about new user account request
 */
function sendNewAccountRequestEmail($to, $requester_name, $username, $full_name, $role) {
    $subject = "MWPD System - New Account Request";
    
    $content = '<p>Dear Regional Director,</p>';
    $content .= '<p>A new user account has been requested in the MWPD Filing System and requires your approval.</p>';
    
    $content .= '<div class="alert alert-info">';
    $content .= '<p><strong>Account Details:</strong></p>';
    $content .= '<p>Username: <strong>' . htmlspecialchars($username) . '</strong><br>';
    $content .= 'Full Name: <strong>' . htmlspecialchars($full_name) . '</strong><br>';
    $content .= 'Role: <strong>' . htmlspecialchars($role) . '</strong><br>';
    $content .= 'Requested by: <strong>' . htmlspecialchars($requester_name) . '</strong></p>';
    $content .= '</div>';
    
    $content .= '<p><a href="https://mwpd.gov.ph/account_approvals.php" class="button">Review Account Request</a></p>';
    $content .= '<p>Please log in to the system to approve or reject this request.</p>';
    
    $html = formatEmailContent($subject, $content);
    return sendEmail($to, $subject, $html, 'new_account_request');
}

/**
 * Sends password reset email with temporary password
 */
function sendPasswordResetEmail($to, $full_name, $username, $temp_password) {
    $subject = "MWPD System - Password Reset";
    
    $content = '<p>Dear ' . htmlspecialchars($full_name) . ',</p>';
    $content .= '<p>Your password for the MWPD Filing System has been reset.</p>';
    
    $content .= '<div class="alert alert-info">';
    $content .= '<p><strong>Your temporary login credentials:</strong></p>';
    $content .= '<p>Username: <strong>' . htmlspecialchars($username) . '</strong><br>';
    $content .= 'Temporary Password: <strong>' . htmlspecialchars($temp_password) . '</strong></p>';
    $content .= '</div>';
    
    $content .= '<div class="alert alert-warning">';
    $content .= '<p><strong>Important:</strong> For security reasons, you will be required to change your password after logging in with this temporary password.</p>';
    $content .= '</div>';
    
    $content .= '<p><a href="https://mwpd.gov.ph/login" class="button">Log In to Your Account</a></p>';
    $content .= '<p>If you did not request this password reset, please contact our support team immediately.</p>';
    
    $html = formatEmailContent($subject, $content);
    return sendEmail($to, $subject, $html, 'password_reset');
}

/**
 * Sends notification when record is submitted for approval
 */
function sendRecordSubmissionEmail($to, $record_type, $record_id, $record_name, $submitter_name) {
    $subject = "MWPD System - New Record Awaiting Approval";
    
    $content = '<p>Dear Regional Director,</p>';
    $content .= '<p>A new ' . htmlspecialchars($record_type) . ' record has been submitted in the MWPD Filing System and requires your approval.</p>';
    
    $content .= '<div class="alert alert-info">';
    $content .= '<p><strong>Record Details:</strong></p>';
    $content .= '<p>Record ID: <strong>' . htmlspecialchars($record_id) . '</strong><br>';
    $content .= 'Record Name: <strong>' . htmlspecialchars($record_name) . '</strong><br>';
    $content .= 'Submitted by: <strong>' . htmlspecialchars($submitter_name) . '</strong></p>';
    $content .= '</div>';
    
    $content .= '<p><a href="https://mwpd.gov.ph/approval_view_simple.php" class="button">Review Approval Request</a></p>';
    $content .= '<p>Please log in to the system to approve or reject this record.</p>';
    
    $html = formatEmailContent($subject, $content);
    return sendEmail($to, $subject, $html, 'record_submission');
}

/**
 * Sends notification when a record approval status changes
 */
function sendRecordApprovalStatusEmail($to, $full_name, $record_type, $record_id, $record_name, $status, $comments = null) {
    $subject = "MWPD System - Record " . ($status == 'approved' ? 'Approved' : 'Rejected');
    
    $content = '<p>Dear ' . htmlspecialchars($full_name) . ',</p>';
    
    if ($status == 'approved') {
        $content .= '<p>Your ' . htmlspecialchars($record_type) . ' record has been approved in the MWPD Filing System.</p>';
    } else {
        $content .= '<p>Your ' . htmlspecialchars($record_type) . ' record has been rejected in the MWPD Filing System.</p>';
    }
    
    $content .= '<div class="alert alert-info">';
    $content .= '<p><strong>Record Details:</strong></p>';
    $content .= '<p>Record ID: <strong>' . htmlspecialchars($record_id) . '</strong><br>';
    $content .= 'Record Name: <strong>' . htmlspecialchars($record_name) . '</strong><br>';
    $content .= 'Status: <strong>' . htmlspecialchars(ucfirst($status)) . '</strong></p>';
    
    if (!empty($comments)) {
        $content .= '<p><strong>Comments:</strong> ' . htmlspecialchars($comments) . '</p>';
    }
    
    $content .= '</div>';
    
    $content .= '<p><a href="https://mwpd.gov.ph/dashboard.php" class="button">Go to Dashboard</a></p>';
    
    $html = formatEmailContent($subject, $content);
    return sendEmail($to, $subject, $html, 'record_status_change');
}

// Helper function to test emails
function testEmailNotification() {
    $result = sendAccountApprovalEmail(
        'test@example.com',
        'Test User',
        'testuser',
        'TemporaryPass123'
    );
    
    return $result ? 'Test email sent successfully.' : 'Failed to send test email.';
}
?>
