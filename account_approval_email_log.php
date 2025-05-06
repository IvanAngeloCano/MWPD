<?php
/**
 * Account Approval Email Log
 * 
 * This file simulates email sending by logging to a file that administrators
 * can check to see what emails would have been sent.
 */

/**
 * Log an account approval notification
 * 
 * @param string $to_email Email address of the recipient
 * @param string $full_name Full name of the approved user
 * @param string $username Username of the approved user
 * @param string $password Generated password for the user
 * @return bool Always returns true as this is just logging
 */
function logAccountApprovalEmail($to_email, $full_name, $username, $password) {
    $log_file = dirname(__FILE__) . '/account_approvals_log.json';
    $timestamp = date('Y-m-d H:i:s');
    
    // Create the log entry as an array
    $log_entry = [
        'type' => 'approval',
        'timestamp' => $timestamp,
        'to_email' => $to_email,
        'full_name' => $full_name,
        'username' => $username,
        'password' => $password,
        'subject' => 'MWPD Filing System - Your Account Has Been Approved'
    ];
    
    // Load existing logs
    $logs = [];
    if (file_exists($log_file) && filesize($log_file) > 0) {
        $logs = json_decode(file_get_contents($log_file), true);
        if (!is_array($logs)) {
            $logs = [];
        }
    }
    
    // Add new log
    $logs[] = $log_entry;
    
    // Save logs
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));
    
    return true;
}

/**
 * Log a submitter notification email
 * 
 * @param string $to_email Email address of the recipient
 * @param string $submitter_name Name of the submitter
 * @param string $username Username of the approved/rejected user
 * @param string $user_full_name Full name of the approved/rejected user
 * @param string $status Status of the approval (approved/rejected)
 * @return bool Always returns true as this is just logging
 */
function logSubmitterNotificationEmail($to_email, $submitter_name, $username, $user_full_name, $status) {
    $log_file = dirname(__FILE__) . '/account_approvals_log.json';
    $timestamp = date('Y-m-d H:i:s');
    
    // Create the log entry as an array
    $log_entry = [
        'type' => 'notification',
        'timestamp' => $timestamp,
        'to_email' => $to_email,
        'submitter_name' => $submitter_name,
        'username' => $username,
        'user_full_name' => $user_full_name,
        'status' => $status,
        'subject' => "MWPD Filing System - Account Request " . ucfirst($status)
    ];
    
    // Load existing logs
    $logs = [];
    if (file_exists($log_file) && filesize($log_file) > 0) {
        $logs = json_decode(file_get_contents($log_file), true);
        if (!is_array($logs)) {
            $logs = [];
        }
    }
    
    // Add new log
    $logs[] = $log_entry;
    
    // Save logs
    file_put_contents($log_file, json_encode($logs, JSON_PRETTY_PRINT));
    
    return true;
}
?>
