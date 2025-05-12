<?php
// Simple template analyzer (without database connection)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to log debug information
function log_message($message) {
    echo $message . "<br>";
}

// Template file path
$templateFile = 'Directhireclearance.docx';

echo "<h1>Template Structure Analyzer</h1>";

if (!file_exists($templateFile)) {
    echo "<p style='color:red'>Template file not found: $templateFile</p>";
    exit;
}

echo "<p>Template file exists: $templateFile (Size: " . filesize($templateFile) . " bytes)</p>";

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    echo "<p style='color:red'>ZipArchive class is not available. Your PHP installation is missing the ZIP extension.</p>";
    exit;
}

// Open the template as a ZIP archive
try {
    $zip = new ZipArchive();
    if ($zip->open($templateFile) !== true) {
        echo "<p style='color:red'>Failed to open DOCX file as ZIP archive.</p>";
        exit;
    }
    
    // Try to read the main document content
    $content = $zip->getFromName('word/document.xml');
    $zip->close();
    
    if (!$content) {
        echo "<p style='color:red'>Could not read document content.</p>";
        exit;
    }
    
    // Look for various placeholder formats
    $phpWordPattern = '/\$\{([^}]+)\}/'; // ${placeholder}
    $doublePattern = '/\{\{([^}]+)\}\}/'; // {{placeholder}}
    $matches1 = [];
    $matches2 = [];
    
    preg_match_all($phpWordPattern, $content, $matches1);
    preg_match_all($doublePattern, $content, $matches2);
    
    $phpWordVars = array_unique($matches1[1]);
    $doubleVars = array_unique($matches2[1]);
    
    // Display PHP Word variables
    echo "<h2>PhpWord Variables (${placeholder})</h2>";
    if (count($phpWordVars) > 0) {
        echo "<ul>";
        foreach ($phpWordVars as $var) {
            // Check if this is an image placeholder
            $isImagePlaceholder = false;
            $imagePlaceholders = ['image', 'applicant_photo', 'photo', 'signature', 'applicant_image', 'applicant_signature'];
            if (in_array($var, $imagePlaceholders)) {
                $isImagePlaceholder = true;
            }
            
            if ($isImagePlaceholder) {
                echo "<li style='color:green;font-weight:bold'>" . htmlspecialchars("\${$var}") . " (IMAGE PLACEHOLDER)</li>";
            } else {
                echo "<li>" . htmlspecialchars("\${$var}") . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>No PhpWord variables found.</p>";
    }
    
    // Display double-bracket variables
    echo "<h2>Double-Bracket Variables ({{placeholder}})</h2>";
    if (count($doubleVars) > 0) {
        echo "<ul>";
        foreach ($doubleVars as $var) {
            // Check if this is an image placeholder
            $isImagePlaceholder = false;
            $imagePlaceholders = ['image', 'applicant_photo', 'photo', 'signature', 'applicant_image', 'applicant_signature'];
            if (in_array($var, $imagePlaceholders)) {
                $isImagePlaceholder = true;
            }
            
            if ($isImagePlaceholder) {
                echo "<li style='color:green;font-weight:bold'>" . htmlspecialchars("{{$var}}") . " (IMAGE PLACEHOLDER)</li>";
            } else {
                echo "<li>" . htmlspecialchars("{{$var}}") . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>No double-bracket variables found.</p>";
    }
    
    // Check for image placeholders
    $foundImagePlaceholders = [];
    $imagePlaceholders = ['image', 'applicant_photo', 'photo', 'signature', 'applicant_image', 'applicant_signature'];
    
    foreach ($phpWordVars as $var) {
        if (in_array($var, $imagePlaceholders)) {
            $foundImagePlaceholders[] = "${$var}";
        }
    }
    
    foreach ($doubleVars as $var) {
        if (in_array($var, $imagePlaceholders)) {
            $foundImagePlaceholders[] = "{{$var}}";
        }
    }
    
    echo "<h2>Image Placeholder Analysis</h2>";
    if (count($foundImagePlaceholders) > 0) {
        echo "<p style='color:green'>Found image placeholder(s) in your template:</p>";
        echo "<ul>";
        foreach ($foundImagePlaceholders as $placeholder) {
            echo "<li>" . htmlspecialchars($placeholder) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red'>No image placeholders found in your template. Your template needs to have at least one of these:</p>";
        echo "<ul>";
        echo "<li>\${image}</li>";
        echo "<li>\${applicant_photo}</li>";
        echo "<li>\${photo}</li>";
        echo "<li>\${applicant_image}</li>";
        echo "</ul>";
        echo "<p>You'll need to edit your template file to add at least one of these placeholders.</p>";
    }
    
    // Provide recommendations
    echo "<h2>Recommendations</h2>";
    if (count($foundImagePlaceholders) > 0) {
        echo "<p>You can use the template_clearance.php script with your current template. It should work with these image placeholders.</p>";
    } else {
        echo "<p>You need to modify your template to include image placeholders. Follow these steps:</p>";
        echo "<ol>";
        echo "<li>Open your Directhireclearance.docx file in Microsoft Word</li>";
        echo "<li>Add the text <strong>\${image}</strong> where you want the applicant's photo to appear</li>";
        echo "<li>Save the template and try again</li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
