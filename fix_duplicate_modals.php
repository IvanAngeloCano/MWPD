<?php
include 'session.php';
require_once 'connection.php';

// Function to safely apply the fix
function applyFix() {
    $filepath = 'gov_to_gov.php';
    $backup_filepath = 'gov_to_gov.php.bak';
    
    // Create a backup first
    if (!file_exists($backup_filepath)) {
        copy($filepath, $backup_filepath);
    }
    
    // Read the file content
    $content = file_get_contents($filepath);
    
    // Pattern 1: Find the code that creates the success notification AND redirects
    $pattern1 = '/document\.body\.appendChild\(successNotification\);\s*\/\/ Auto-redirect after \d+ seconds\s*setTimeout\(function\(\) {\s*window\.location\.href = \'gov_to_gov\.php\?success=/';
    
    // Replacement 1: Remove the success notification and keep only the redirect
    $replacement1 = '// Skip creating a custom notification and just redirect with the success parameter
              setTimeout(function() {
                window.location.href = \'gov_to_gov.php?success=';
    
    // Apply the first replacement
    $modified_content = preg_replace($pattern1, $replacement1, $content);
    
    // Only write if there were changes
    if ($modified_content !== $content) {
        file_put_contents($filepath, $modified_content);
        return true;
    }
    
    return false;
}

// Apply the fix
$result = applyFix();

// Redirect back with a message
if ($result) {
    header('Location: gov_to_gov.php?success=' . urlencode('Duplicate modals fixed successfully'));
} else {
    header('Location: gov_to_gov.php?error=' . urlencode('Could not apply the fix automatically'));
}
?>
