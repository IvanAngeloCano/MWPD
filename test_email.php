<?php
/**
 * Email Test Script for MWPD
 * 
 * This simple script tests the email functionality to verify that
 * the email configuration is working correctly.
 */

// Include necessary files
require_once 'email_notifications.php';

// Set up error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MWPD Email System Test</h1>";

// Verify that the foolproof_mailer.php file has been included
if (!function_exists('foolproof_send_email')) {
    die("<p>Error: foolproof_mailer.php has not been included properly.</p>");
}

// Log email configuration for debugging
echo "<h2>Email Configuration</h2>";
echo "<pre>";
echo "SMTP Host: " . $GLOBALS['email_server']['smtp']['host'] . "\n";
echo "SMTP Port: " . $GLOBALS['email_server']['smtp']['port'] . "\n";
echo "SMTP User: " . $GLOBALS['email_server']['smtp']['username'] . "\n";
echo "SMTP Password: " . (empty($GLOBALS['email_server']['smtp']['password']) ? "Not set" : "Set (hidden)") . "\n";
echo "</pre>";

// Function to display test results
function displayResult($success, $message = "") {
    echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; " . 
         "background-color: " . ($success ? "#d4edda" : "#f8d7da") . "; " .
         "color: " . ($success ? "#155724" : "#721c24") . "; " .
         "border: 1px solid " . ($success ? "#c3e6cb" : "#f5c6cb") . ";'>";
    echo "<strong>" . ($success ? "Success" : "Error") . ":</strong> " . $message;
    echo "</div>";
}

// Test basic email sending
echo "<h2>Test 1: Basic Email Sending</h2>";
$test_email = isset($_POST['test_email']) ? $_POST['test_email'] : "";

// Form for email testing
echo '<form method="post" action="test_email.php">';
echo '<p><label>Send test email to: <input type="email" name="test_email" value="' . htmlspecialchars($test_email) . '" required></label></p>';
echo '<p><button type="submit" style="padding: 8px 15px; background-color: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer;">Send Test Email</button></p>';
echo '</form>';

// If form is submitted, send the test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($test_email)) {
    // Simple test email
    $subject = "MWPD Email System Test - " . date('Y-m-d H:i:s');
    $message = '<p>This is a test email from the MWPD Filing System.</p>' .
               '<p>If you are receiving this email, it means the email system is working correctly.</p>' .
               '<p>Time sent: ' . date('Y-m-d H:i:s') . '</p>';
    
    try {
        $result = sendEmail($test_email, $subject, $message, 'test');
        
        if ($result) {
            displayResult(true, "Email sent successfully to $test_email. Check your inbox (and spam folder).");
        } else {
            displayResult(false, "Failed to send email. Check server logs for details.");
        }
    } catch (Exception $e) {
        displayResult(false, "Exception: " . $e->getMessage());
    }
    
    // Display log contents if available
    $log_files = ['email_log.txt', 'email_error_log.txt', 'email_detailed_log.txt'];
    
    foreach ($log_files as $log_file) {
        if (file_exists($log_file)) {
            echo "<h3>Contents of $log_file:</h3>";
            echo "<pre style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow: auto;'>";
            echo htmlspecialchars(file_get_contents($log_file));
            echo "</pre>";
        }
    }
}

// Display troubleshooting information
echo "<h2>Troubleshooting</h2>";
echo "<ul>";
echo "<li>If emails are not being sent, check the following:</li>";
echo "<ul>";
echo "<li>Verify that your SMTP credentials are correct</li>";
echo "<li>Make sure you're using an app password if using Gmail (required since May 2022)</li>";
echo "<li>Check that your email server allows sending from this IP address</li>";
echo "<li>Review any firewall settings that might be blocking outgoing SMTP connections</li>";
echo "</ul>";
echo "</ul>";

// Display PHPMailer version info if available
if (class_exists('PHPMailer')) {
    $reflection = new ReflectionClass('PHPMailer');
    echo "<p>PHPMailer version/path: " . $reflection->getFileName() . "</p>";
}
?>
