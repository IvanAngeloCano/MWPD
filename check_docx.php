<?php
// Simple script to check what placeholders are in the DOCX template
// This doesn't require ZipArchive

// Function to log information
function log_info($message) {
    echo $message . "<br>";
    file_put_contents('docx_check_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

echo "<h2>DOCX Template Checker</h2>";

// Check if template exists
$templateFile = 'Directhireclearance.docx';
if (!file_exists($templateFile)) {
    log_info("Error: Template file not found: $templateFile");
    die("Template file not found!");
}

log_info("Template file exists: $templateFile");
log_info("File size: " . filesize($templateFile) . " bytes");

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    log_info("WARNING: ZipArchive class is not available in your PHP installation");
    log_info("You need to enable the ZIP extension in php.ini");
    log_info("Find and uncomment the line: extension=zip");
    log_info("Then restart your Apache/PHP server");
    
    // Try a basic check without ZipArchive
    $content = file_get_contents($templateFile);
    $imagePattern = '/\{\{(image|applicant_photo|photo|signature|applicant_signature)\}\}/';
    
    if (preg_match($imagePattern, $content)) {
        log_info("Found possible image placeholders in template (basic check)");
    } else {
        log_info("No image placeholders detected in basic check");
    }
} else {
    // Use ZipArchive to check the template content
    log_info("ZipArchive is available, checking template contents...");
    
    try {
        $zip = new ZipArchive();
        if ($zip->open($templateFile) === TRUE) {
            // DOCX files store content in word/document.xml
            $content = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if ($content) {
                log_info("Successfully read document content");
                
                // Check for image placeholders
                $imagePattern = '/\{\{(image|applicant_photo|photo|signature|applicant_signature)\}\}/';
                if (preg_match_all($imagePattern, $content, $matches)) {
                    log_info("Found image placeholders: " . implode(', ', $matches[0]));
                } else {
                    log_info("No image placeholders found in document");
                    log_info("The template should contain placeholders like {{image}} or {{applicant_photo}}");
                }
                
                // Check for text placeholders
                log_info("Checking for text placeholders...");
                $textPattern = '/\{\{([^}]+)\}\}/';
                if (preg_match_all($textPattern, $content, $matches)) {
                    log_info("Found text placeholders: " . implode(', ', array_unique($matches[1])));
                } else {
                    log_info("No text placeholders found in document");
                }
            } else {
                log_info("Could not read document content");
            }
        } else {
            log_info("Failed to open DOCX file as ZIP archive");
        }
    } catch (Exception $e) {
        log_info("Error processing DOCX: " . $e->getMessage());
    }
}

// Instructions for fixing the issue
echo "<h3>How to Fix Missing ZipArchive</h3>";
echo "<ol>";
echo "<li>Open XAMPP Control Panel</li>";
echo "<li>Click on 'Config' button for Apache</li>";
echo "<li>Select 'PHP (php.ini)' to edit the configuration file</li>";
echo "<li>Find the line <code>extension=zip</code> and make sure it's uncommented (no semicolon in front)</li>";
echo "<li>Save the file</li>";
echo "<li>Restart Apache using the XAMPP Control Panel</li>";
echo "</ol>";

echo "<p>After enabling the ZIP extension, your clearance documents should work with images!</p>";
?>
