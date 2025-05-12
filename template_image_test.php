<?php
// Simple test script to insert an image into the template
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Function to log debugging information
function log_debug($message) {
    file_put_contents('template_test_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

log_debug("Template image test started");

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    die("Error: ZipArchive extension is not available. Please enable the ZIP extension in php.ini.");
}

try {
    // Step 1: Get a test image
    $direct_hire_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if ($direct_hire_id) {
        // Get image from database
        $image_query = "SELECT * FROM direct_hire_documents WHERE direct_hire_id = ? AND file_type LIKE 'image/%' LIMIT 1";
        $image_stmt = $pdo->prepare($image_query);
        $image_stmt->execute([$direct_hire_id]);
        $image_data = $image_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$image_data || empty($image_data['file_content'])) {
            die("No image found for this record. Please upload an image first.");
        }
        
        // Create a temporary file for the image
        $temp_image = sys_get_temp_dir() . '/' . uniqid('template_test_') . '.jpg';
        file_put_contents($temp_image, $image_data['file_content']);
        log_debug("Created temporary image file: $temp_image");
    } else {
        // Use a test image
        $temp_image = 'test_image.jpg';
        if (!file_exists($temp_image)) {
            die("Test image not found. Please create a test_image.jpg file in the root directory.");
        }
    }
    
    // Step 2: Make a copy of the template to work with
    $templateFile = 'Directhireclearance.docx';
    $testFile = 'test_template_output.docx';
    copy($templateFile, $testFile);
    log_debug("Copied template to: $testFile");
    
    // Step 3: Open the template
    $template = new TemplateProcessor($testFile);
    log_debug("Opened template successfully");
    
    // Step 4: Try different methods to insert the image
    
    // Method 1: Basic setImageValue with string
    try {
        $template->setImageValue('image', $temp_image);
        log_debug("Method 1 (basic string) successful");
    } catch (Exception $e) {
        log_debug("Method 1 failed: " . $e->getMessage());
    }
    
    // Method 2: setImageValue with array
    try {
        $template->setImageValue('applicant_photo', [
            'path' => $temp_image,
            'width' => 150,
            'height' => 150,
            'ratio' => true
        ]);
        log_debug("Method 2 (array format) successful");
    } catch (Exception $e) {
        log_debug("Method 2 failed: " . $e->getMessage());
    }
    
    // Method 3: Try with realpath
    try {
        $template->setImageValue('photo', [
            'path' => realpath($temp_image),
            'width' => 150,
            'height' => 150,
            'ratio' => true
        ]);
        log_debug("Method 3 (realpath array) successful");
    } catch (Exception $e) {
        log_debug("Method 3 failed: " . $e->getMessage());
    }
    
    // Method 4: Try with direct binary data
    try {
        $imageData = file_get_contents($temp_image);
        
        // This method isn't directly supported by PhpWord, so we're not implementing it
        // but keeping it as a placeholder for potential future implementation
        log_debug("Method 4 (binary data) not implemented in this test");
    } catch (Exception $e) {
        log_debug("Method 4 failed: " . $e->getMessage());
    }
    
    // Step 5: Save the template
    $template->saveAs($testFile);
    log_debug("Saved template with image");
    
    // Step 6: Output download link
    echo "<h1>Template Image Test</h1>";
    echo "<p>Template with image saved as: <a href='$testFile' download>$testFile</a></p>";
    echo "<p>Check the template_test_debug.txt file for detailed logs.</p>";
    
    // Step 7: Check which placeholders actually exist in the template
    echo "<h2>Placeholders in Your Template</h2>";
    
    // Open the template as a ZIP archive to examine content
    $zip = new ZipArchive();
    if ($zip->open($templateFile) === true) {
        $content = $zip->getFromName('word/document.xml');
        $zip->close();
        
        if ($content) {
            // Look for placeholders in format ${placeholder} or {{placeholder}}
            $phpWordPattern = '/\$\{([^}]+)\}/';
            $doublePattern = '/\{\{([^}]+)\}\}/';
            $matches1 = [];
            $matches2 = [];
            
            preg_match_all($phpWordPattern, $content, $matches1);
            preg_match_all($doublePattern, $content, $matches2);
            
            $phpWordVars = array_unique($matches1[1]);
            $doubleVars = array_unique($matches2[1]);
            
            echo "<h3>PhpWord Variables (${placeholder})</h3>";
            if (count($phpWordVars) > 0) {
                echo "<ul>";
                foreach ($phpWordVars as $var) {
                    echo "<li>$var</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No PhpWord variables found.</p>";
            }
            
            echo "<h3>Double-Bracket Variables ({{placeholder}})</h3>";
            if (count($doubleVars) > 0) {
                echo "<ul>";
                foreach ($doubleVars as $var) {
                    echo "<li>$var</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>No double-bracket variables found.</p>";
            }
        } else {
            echo "<p>Could not read document content.</p>";
        }
    } else {
        echo "<p>Failed to open template as ZIP archive.</p>";
    }
    
    echo "<h2>Image Placeholders You Need</h2>";
    echo "<p>For the image to work, your template needs to have at least one of these placeholders:</p>";
    echo "<ul>";
    echo "<li>\${image} - This is the standard image placeholder format</li>";
    echo "<li>\${applicant_photo} - Another common format</li>";
    echo "</ul>";
    
    echo "<p>If none of these placeholders are found in your template, you'll need to modify your template to include them.</p>";
    
} catch (Exception $e) {
    log_debug("Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}
?>
