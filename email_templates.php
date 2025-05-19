<?php
/**
 * MWPD Email Templates
 * 
 * Professional email templates for the MWPD Filing System
 */

// Include email configuration if needed
if (!isset($GLOBALS['email_config']) && file_exists('email_config.php')) {
    include_once 'email_config.php';
}

/**
 * Generate HTML email with professional template
 * 
 * @param string $subject Email subject
 * @param string $content Main content of the email (HTML)
 * @param array $options Additional options for the template
 * @return string Fully formatted HTML email
 */
function format_email_template($subject, $content, $options = []) {
    // Default options
    $defaults = [
        'logo_url' => 'https://mwpd.gov.ph/wp-content/uploads/2022/10/DMW-Logo.png',
        'header_color' => '#1a5276',
        'footer_text' => 'This is an automated message from the MWPD Filing System. Please do not reply to this email.',
        'include_signature' => true,
        'signature' => isset($GLOBALS['email_config']['signature']) ? $GLOBALS['email_config']['signature'] : '<p>Regards,<br>MWPD Administration</p>',
        'action_url' => '',
        'action_text' => '',
        'year' => date('Y')
    ];
    
    // Merge options
    $opts = array_merge($defaults, $options);
    
    // Start building HTML
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>' . htmlspecialchars($subject) . '</title>
    <style>
        @media only screen and (max-width: 620px) {
            table.body h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
            }
            table.body p,
            table.body ul,
            table.body ol,
            table.body td,
            table.body span,
            table.body a {
                font-size: 16px !important;
            }
            table.body .wrapper,
            table.body .article {
                padding: 10px !important;
            }
            table.body .content {
                padding: 0 !important;
            }
            table.body .container {
                padding: 0 !important;
                width: 100% !important;
            }
            table.body .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }
            table.body .btn table {
                width: 100% !important;
            }
            table.body .btn a {
                width: 100% !important;
            }
            table.body .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }
        }
        
        @media all {
            .ExternalClass {
                width: 100%;
            }
            .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
                line-height: 100%;
            }
            .apple-link a {
                color: inherit !important;
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                text-decoration: none !important;
            }
            #MessageViewBody a {
                color: inherit;
                text-decoration: none;
                font-size: inherit;
                font-family: inherit;
                font-weight: inherit;
                line-height: inherit;
            }
            .btn-primary table td:hover {
                background-color: #34495e !important;
            }
            .btn-primary a:hover {
                background-color: #34495e !important;
                border-color: #34495e !important;
            }
        }
    </style>
</head>
<body style="background-color: #f6f6f6; font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; margin: 0; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%;">
    <span class="preheader" style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">' . substr(strip_tags($content), 0, 100) . '...</span>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" width="100%" bgcolor="#f6f6f6">
        <tr>
            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto;" width="580" valign="top">
                <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                    <!-- START HEADER -->
                    <div class="header" style="clear: both; margin-bottom: 10px; text-align: center; width: 100%;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
                            <tr>
                                <td class="logo-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding-bottom: 10px; padding-top: 10px; text-align: center;" valign="top" align="center">
                                    <img src="' . $opts['logo_url'] . '" height="80" alt="MWPD Logo" style="border: none; -ms-interpolation-mode: bicubic; max-width: 100%;">
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- END HEADER -->
                    
                    <!-- START MAIN CONTENT AREA -->
                    <table role="presentation" class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #ffffff; border-radius: 3px; width: 100%;" width="100%">
                        <!-- START MAIN CONTENT AREA -->
                        <tr>
                            <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;" valign="top">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
                                    <tr>
                                        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
                                            <div style="background-color: ' . $opts['header_color'] . '; color: white; padding: 15px; border-radius: 3px 3px 0 0; margin-bottom: 20px;">
                                                <h2 style="color: white; font-family: sans-serif; font-weight: 400; line-height: 1.4; margin: 0; margin-bottom: 0px;">' . htmlspecialchars($subject) . '</h2>
                                            </div>
                                            <div style="padding: 0 15px;">
                                                ' . $content . '
                                            </div>';
    
    // Add action button if provided
    if (!empty($opts['action_url']) && !empty($opts['action_text'])) {
        $html .= '
                                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; box-sizing: border-box; width: 100%;" width="100%">
                                                <tbody>
                                                    <tr>
                                                        <td align="center" style="font-family: sans-serif; font-size: 14px; vertical-align: top; padding-bottom: 15px;" valign="top">
                                                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto;">
                                                                <tbody>
                                                                    <tr>
                                                                        <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-radius: 5px; text-align: center; background-color: ' . $opts['header_color'] . ';" valign="top" align="center" bgcolor="' . $opts['header_color'] . '">
                                                                            <a href="' . $opts['action_url'] . '" target="_blank" style="border: solid 1px ' . $opts['header_color'] . '; border-radius: 5px; box-sizing: border-box; cursor: pointer; display: inline-block; font-size: 14px; font-weight: bold; margin: 0; padding: 12px 25px; text-decoration: none; text-transform: capitalize; background-color: ' . $opts['header_color'] . '; border-color: ' . $opts['header_color'] . '; color: #ffffff;">' . htmlspecialchars($opts['action_text']) . '</a>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>';
    }
    
    // Add signature if requested
    if ($opts['include_signature']) {
        $html .= '
                                            <div style="border-top: 1px solid #dddddd; margin-top: 20px; padding-top: 15px;">
                                                ' . $opts['signature'] . '
                                            </div>';
    }
    
    // Finish up the HTML template
    $html .= '
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <!-- END MAIN CONTENT AREA -->
                    
                    <!-- START FOOTER -->
                    <div class="footer" style="clear: both; margin-top: 10px; text-align: center; width: 100%;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
                            <tr>
                                <td class="content-block" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; color: #999999; font-size: 12px; text-align: center;" valign="top" align="center">
                                    <span class="apple-link" style="color: #999999; font-size: 12px; text-align: center;">Migrant Workers Protection Division (MWPD)</span>
                                    <br>
                                    ' . htmlspecialchars($opts['footer_text']) . '
                                </td>
                            </tr>
                            <tr>
                                <td class="content-block powered-by" style="font-family: sans-serif; vertical-align: top; padding-bottom: 10px; padding-top: 10px; color: #999999; font-size: 12px; text-align: center;" valign="top" align="center">
                                    &copy; ' . $opts['year'] . ' MWPD Filing System
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- END FOOTER -->
                </div>
            </td>
            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
        </tr>
    </table>
</body>
</html>';
    
    return $html;
}

/**
 * Create a password reset email
 * 
 * @param string $full_name Recipient's full name
 * @param string $username Username for login
 * @param string $temp_password New temporary password
 * @param string $login_url Optional login URL
 * @return string Formatted HTML email
 */
function create_password_reset_email($full_name, $username, $temp_password, $login_url = '') {
    // Main content
    $content = '
        <p>Dear ' . htmlspecialchars($full_name) . ',</p>
        <p>Your password has been reset by an administrator.</p>
        
        <div style="background-color: #f8f9fa; border-left: 4px solid #1a5276; padding: 15px; margin: 20px 0;">
            <p style="margin-top: 0;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
            <p style="margin-bottom: 0;"><strong>New Password:</strong> ' . htmlspecialchars($temp_password) . '</p>
        </div>
        
        <p>For security reasons, please log in and change your password immediately.</p>
        <p>If you did not request this password reset, please contact your system administrator immediately.</p>
    ';
    
    // Options for template
    $options = [
        'header_color' => '#1a5276',
        'action_text' => '',
        'action_url' => ''
    ];
    
    // Add login button if URL provided
    if (!empty($login_url)) {
        $options['action_text'] = 'Login Now';
        $options['action_url'] = $login_url;
    }
    
    // Return formatted email
    return format_email_template('Password Reset Notification', $content, $options);
}

/**
 * Create an account approval email
 * 
 * @param string $full_name Recipient's full name
 * @param string $username Username for login
 * @param string $temp_password New temporary password
 * @param string $login_url Optional login URL
 * @return string Formatted HTML email
 */
function create_account_approval_email($full_name, $username, $temp_password, $login_url = '') {
    // Main content
    $content = '
        <p>Dear ' . htmlspecialchars($full_name) . ',</p>
        <p>Your account for the MWPD Filing System has been approved.</p>
        
        <div style="background-color: #f8f9fa; border-left: 4px solid #1a5276; padding: 15px; margin: 20px 0;">
            <p style="margin-top: 0;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
            <p style="margin-bottom: 0;"><strong>Password:</strong> ' . htmlspecialchars($temp_password) . '</p>
        </div>
        
        <p>For security reasons, please log in and change your password immediately.</p>
        <p>If you have any questions, please contact your system administrator.</p>
    ';
    
    // Options for template
    $options = [
        'header_color' => '#2e7d32', // Green for approval
        'action_text' => '',
        'action_url' => ''
    ];
    
    // Add login button if URL provided
    if (!empty($login_url)) {
        $options['action_text'] = 'Login Now';
        $options['action_url'] = $login_url;
    }
    
    // Return formatted email
    return format_email_template('Account Approved', $content, $options);
}

/**
 * Create an account rejection email
 * 
 * @param string $full_name Recipient's full name
 * @param string $username Username that was requested
 * @param string $rejection_reason Reason for rejection
 * @return string Formatted HTML email
 */
function create_account_rejection_email($full_name, $username, $rejection_reason = '') {
    // Main content
    $content = '
        <p>Dear ' . htmlspecialchars($full_name) . ',</p>
        <p>We regret to inform you that your account request for the MWPD Filing System has not been approved at this time.</p>
        
        <div style="background-color: #f8f9fa; border-left: 4px solid #c62828; padding: 15px; margin: 20px 0;">
            <p style="margin-top: 0;"><strong>Username Requested:</strong> ' . htmlspecialchars($username) . '</p>';
    
    if (!empty($rejection_reason)) {
        $content .= '
            <p style="margin-bottom: 0;"><strong>Reason:</strong> ' . htmlspecialchars($rejection_reason) . '</p>';
    }
    
    $content .= '
        </div>
        
        <p>If you believe this is in error or would like to discuss your application further, please contact your system administrator.</p>
    ';
    
    // Options for template
    $options = [
        'header_color' => '#c62828', // Red for rejection
    ];
    
    // Return formatted email
    return format_email_template('Account Request Status', $content, $options);
}

/**
 * Create a record submission email
 * 
 * @param string $full_name Recipient's full name
 * @param string $record_type Type of record
 * @param string $record_id Record ID
 * @param string $record_name Record name or title
 * @param string $submitter_name Name of the submitter
 * @param string $view_url Optional URL to view the record
 * @return string Formatted HTML email
 */
function create_record_submission_email($full_name, $record_type, $record_id, $record_name, $submitter_name, $view_url = '') {
    // Main content
    $content = '
        <p>Dear ' . htmlspecialchars($full_name) . ',</p>
        <p>A new ' . htmlspecialchars($record_type) . ' record has been submitted and requires your review.</p>
        
        <div style="background-color: #f8f9fa; border-left: 4px solid #1a5276; padding: 15px; margin: 20px 0;">
            <p style="margin-top: 0;"><strong>Record Type:</strong> ' . htmlspecialchars($record_type) . '</p>
            <p><strong>Record ID:</strong> ' . htmlspecialchars($record_id) . '</p>
            <p><strong>Record Name:</strong> ' . htmlspecialchars($record_name) . '</p>
            <p style="margin-bottom: 0;"><strong>Submitted By:</strong> ' . htmlspecialchars($submitter_name) . '</p>
        </div>
        
        <p>Please review this submission at your earliest convenience.</p>
    ';
    
    // Options for template
    $options = [
        'header_color' => '#1a5276',
        'action_text' => '',
        'action_url' => ''
    ];
    
    // Add view button if URL provided
    if (!empty($view_url)) {
        $options['action_text'] = 'View Record';
        $options['action_url'] = $view_url;
    }
    
    // Return formatted email
    return format_email_template('New Record Submission', $content, $options);
}

/**
 * Create a record approval status email
 * 
 * @param string $full_name Recipient's full name
 * @param string $record_type Type of record
 * @param string $record_id Record ID
 * @param string $record_name Record name or title
 * @param string $status Approval status (approved/rejected)
 * @param string $comments Optional comments
 * @param string $view_url Optional URL to view the record
 * @return string Formatted HTML email
 */
function create_record_status_email($full_name, $record_type, $record_id, $record_name, $status, $comments = '', $view_url = '') {
    // Determine color and title based on status
    $header_color = '#1a5276';
    $status_color = '#1a5276';
    $title = 'Record Status Update';
    
    if (strtolower($status) === 'approved') {
        $header_color = '#2e7d32';
        $status_color = '#2e7d32';
        $title = 'Record Approved';
    } else if (strtolower($status) === 'rejected') {
        $header_color = '#c62828';
        $status_color = '#c62828';
        $title = 'Record Not Approved';
    }
    
    // Main content
    $content = '
        <p>Dear ' . htmlspecialchars($full_name) . ',</p>
        <p>The status of your ' . htmlspecialchars($record_type) . ' record has been updated.</p>
        
        <div style="background-color: #f8f9fa; border-left: 4px solid ' . $status_color . '; padding: 15px; margin: 20px 0;">
            <p style="margin-top: 0;"><strong>Record Type:</strong> ' . htmlspecialchars($record_type) . '</p>
            <p><strong>Record ID:</strong> ' . htmlspecialchars($record_id) . '</p>
            <p><strong>Record Name:</strong> ' . htmlspecialchars($record_name) . '</p>
            <p><strong>Status:</strong> <span style="color: ' . $status_color . ';">' . htmlspecialchars($status) . '</span></p>';
    
    if (!empty($comments)) {
        $content .= '
            <p style="margin-bottom: 0;"><strong>Comments:</strong> ' . htmlspecialchars($comments) . '</p>';
    } else {
        $content .= '
            <p style="margin-bottom: 0;"></p>';
    }
    
    $content .= '
        </div>
        
        <p>For any questions regarding this decision, please contact your supervisor.</p>
    ';
    
    // Options for template
    $options = [
        'header_color' => $header_color,
        'action_text' => '',
        'action_url' => ''
    ];
    
    // Add view button if URL provided
    if (!empty($view_url)) {
        $options['action_text'] = 'View Record';
        $options['action_url'] = $view_url;
    }
    
    // Return formatted email
    return format_email_template($title, $content, $options);
}

/**
 * Create a general notification email
 * 
 * @param string $full_name Recipient's full name
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $action_text Optional call-to-action button text
 * @param string $action_url Optional call-to-action URL
 * @return string Formatted HTML email
 */
function create_notification_email($full_name, $subject, $message, $action_text = '', $action_url = '') {
    // Main content
    $content = '
        <p>Dear ' . htmlspecialchars($full_name) . ',</p>
        ' . $message . '
    ';
    
    // Options for template
    $options = [
        'header_color' => '#1a5276',
        'action_text' => $action_text,
        'action_url' => $action_url
    ];
    
    // Return formatted email
    return format_email_template($subject, $content, $options);
}
?>
