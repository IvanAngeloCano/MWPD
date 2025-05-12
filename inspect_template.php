<?php
// This script will examine the DOCX template to find all placeholders

// Load required libraries
require_once 'vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;

// Function to extract and display all variables in a template
function extractTemplateVariables($templatePath) {
    // Open the template as a ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($templatePath) !== true) {
        echo "Error: Cannot open template file as ZIP archive.";
        return false;
    }
    
    // Read main document.xml file
    $content = $zip->getFromName('word/document.xml');
    $zip->close();
    
    if (!$content) {
        echo "Error: Could not read document content.";
        return false;
    }
    
    // Look for placeholders in the format ${placeholder}
    $phpWordPattern = '/\$\{([^}]+)\}/';
    $matches = [];
    preg_match_all($phpWordPattern, $content, $matches);
    
    $phpWordVariables = array_unique($matches[1]);
    
    // Look for placeholders in the format {{placeholder}}
    $doublePattern = '/\{\{([^}]+)\}\}/';
    $matches = [];
    preg_match_all($doublePattern, $content, $matches);
    
    $doubleVariables = array_unique($matches[1]);
    
    return [
        'phpWord' => $phpWordVariables,
        'double' => $doubleVariables
    ];
}

// Create a test with signature to see how it works
function testSignatureImage() {
    try {
        // Create a copy of the template
        $templateFile = 'Directhireclearance.docx';
        $testTemplate = 'test_template.docx';
        copy($templateFile, $testTemplate);
        
        // Create a template processor
        $template = new TemplateProcessor($testTemplate);
        
        // Add a test signature using the method that works
        $signatureFile = 'signatures/Signature.png';
        if (file_exists($signatureFile)) {
            // Try with array format
            $template->setImageValue('signature1_image', [
                'path' => realpath($signatureFile),
                'width' => 150,
                'height' => 75,
                'ratio' => false
            ]);
            echo "Successfully added signature with array format!<br>";
            
            // Try also adding an image with the same format
            $template->setImageValue('image', [
                'path' => realpath($signatureFile),
                'width' => 150,
                'height' => 150,
                'ratio' => true
            ]);
            echo "Successfully added image with array format!<br>";
        }
        
        // Save the test template
        $template->saveAs('test_with_signature.docx');
        echo "Test document saved as 'test_with_signature.docx'<br>";
        
        return true;
    } catch (Exception $e) {
        echo "Error in signature test: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Main execution
echo "<h1>Template Inspector</h1>";

$templateFile = 'Directhireclearance.docx';
echo "<h2>Analyzing template: $templateFile</h2>";

if (!file_exists($templateFile)) {
    echo "Error: Template file not found!";
    exit;
}

// Check ZIP extension
if (!class_exists('ZipArchive')) {
    echo "<div style='color:red; font-weight:bold;'>
        ZIP extension is NOT available! This explains the errors!
        </div>";
} else {
    echo "<div style='color:green; font-weight:bold;'>
        ZIP extension is available.
        </div>";
    
    // Extract variables
    $variables = extractTemplateVariables($templateFile);
    
    if ($variables) {
        // Display PHPWord variables (${placeholder})
        echo "<h3>PHPWord Variables (${placeholder})</h3>";
        if (count($variables['phpWord']) > 0) {
            echo "<ul>";
            foreach ($variables['phpWord'] as $var) {
                echo "<li>$var</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No PHPWord variables found.</p>";
        }
        
        // Display double-bracket variables ({{placeholder}})
        echo "<h3>Double-Bracket Variables ({{placeholder}})</h3>";
        if (count($variables['double']) > 0) {
            echo "<ul>";
            foreach ($variables['double'] as $var) {
                echo "<li>$var</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No double-bracket variables found.</p>";
        }
        
        // Test with signature
        echo "<h3>Testing Image Insertion</h3>";
        testSignatureImage();
    }
}

echo "<h2>Conclusion</h2>";
echo "<p>Check if 'image' or similar variables appear in the lists above. If not, your template doesn't have the necessary placeholders for images.</p>";
echo "<p>If 'signature1_image' is found but 'image' is not, you need to add the image placeholder to your template.</p>";
?>
