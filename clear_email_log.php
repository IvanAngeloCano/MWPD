<?php
// Simple script to clear the email log file to prevent confusion
$log_file = 'email_log.txt';

// Clear the file
file_put_contents($log_file, '');

// Set a success message
session_start();
$_SESSION['success_message'] = "Email log has been cleared. The system now uses Account Approval Logs.";

// Redirect back to the dashboard
header('Location: account_dashboard.php');
exit();
?>
