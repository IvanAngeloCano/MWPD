<?php
include 'session.php';
require_once 'connection.php';

echo "<h1>Debug Notifications</h1>";

// Check for session flash messages
echo "<h2>Session Notifications</h2>";
echo "<pre>";
if (isset($_SESSION)) {
    if (isset($_SESSION['success_message'])) {
        echo "SUCCESS MESSAGE: " . htmlspecialchars($_SESSION['success_message']) . "\n";
    }
    
    if (isset($_SESSION['error_message'])) {
        echo "ERROR MESSAGE: " . htmlspecialchars($_SESSION['error_message']) . "\n";
    }
    
    if (isset($_SESSION['notification'])) {
        echo "NOTIFICATION: " . htmlspecialchars(print_r($_SESSION['notification'], true)) . "\n";
    }
}
echo "</pre>";

// Get the included files
echo "<h2>Notification Files</h2>";
echo "<ul>";
$included_files = get_included_files();
foreach ($included_files as $file) {
    if (strpos($file, 'notification') !== false || strpos($file, 'alert') !== false) {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
}
echo "</ul>";

// Check for GET success parameter that might trigger notifications
echo "<h2>GET Parameters</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

// Provide a fix for the duplicate modals
echo "<h2>Fix for Duplicate Modals</h2>";
echo "<p>The duplicate modals are appearing because:</p>";
echo "<ol>";
echo "<li>A custom modal is being created in JavaScript in the gov_to_gov.php file</li>";
echo "<li>AND the page is redirected with a success parameter which triggers another notification</li>";
echo "</ol>";

echo "<p><strong>To fix this:</strong></p>";
echo "<ol>";
echo "<li>Create a modified version of the _header.php file that checks if a custom modal was already shown</li>";
echo "<li>OR remove the redirect with success parameter and only use the custom modal</li>";
echo "</ol>";

echo "<a href='fix_duplicate_modals.php' class='btn btn-primary'>Fix Duplicate Modals</a>";

?>
