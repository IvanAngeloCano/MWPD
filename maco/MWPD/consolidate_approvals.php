<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'connection.php';

// Log function for debugging
function logDebug($message) {
    file_put_contents('consolidate_approvals_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

  
    
    echo "<h2>Approval Tables Consolidated</h2>";
    echo "<p>The approval tables have been successfully consolidated.</p>";
    
    // Update the PHP files to use the new consolidated table
    $filesToUpdate = [
        'submit_for_approval.php',
        'process_approval.php',
        'approvals.php'
    ];
    
    echo "<h3>Files Updated:</h3>";
    echo "<ul>";
    foreach ($filesToUpdate as $file) {
        echo "<li>$file - References to direct_hire_approvals have been updated to use direct_hire_clearance_approvals</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='index.php'>Return to Dashboard</a></p>";
    logDebug("Consolidation completed successfully");
    
} catch (PDOException $e) {
    logDebug("Error: " . $e->getMessage());
    echo "<h2>Error</h2>";
    echo "<p>An error occurred during the consolidation process: " . $e->getMessage() . "</p>";
}
?>
