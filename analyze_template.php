<?php
// Template Analyzer Script
require_once 'vendor/autoload.php';

// Function to log information
function log_info($message) {
    echo $message . "<br>";
    file_put_contents('template_analysis.log', date('Y-m-d H:i:s') . ': ' . $message . PHP_EOL, FILE_APPEND);
}

$template_file = 'Directhireclearance.docx';
log_info("Analyzing template: $template_file");

// Create temporary directory for extraction
$temp_dir = 'temp_analyze_' . uniqid();
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Try to analyze the DOCX using ZipArchive directly 
// (We want to avoid TemplateProcessor as it has issues)
$zip = new ZipArchive();
if ($zip->open($template_file) === TRUE) {
    log_info("Successfully opened template as ZIP archive");
    
    // Extract to temporary directory
    $zip->extractTo($temp_dir);
    $zip->close();
    
    log_info("Extracted template files to temporary directory");

    // Look for Word document files that might contain placeholders
    $xml_files = [
        'word/document.xml',  // Main document
        'word/header1.xml',   // Header
        'word/header2.xml',   // Header
        'word/header3.xml',   // Header
        'word/footer1.xml',   // Footer
        'word/footer2.xml',   // Footer
        'word/footer3.xml'    // Footer
    ];
    
    $placeholders = [];
    $image_placeholders = [];
    
    // Search each XML file for placeholders
    foreach ($xml_files as $xml_file) {
        $xml_path = $temp_dir . '/' . $xml_file;
        
        if (file_exists($xml_path)) {
            log_info("Checking file: $xml_file");
            $content = file_get_contents($xml_path);
            
            // Look for standard text placeholders ${...}
            preg_match_all('/\$\{([^}]+)\}/', $content, $matches);
            if (!empty($matches[1])) {
                log_info("Found " . count($matches[1]) . " text placeholders in $xml_file");
                foreach ($matches[1] as $placeholder) {
                    $placeholders[] = $placeholder;
                    log_info("- Text placeholder: $placeholder");
                }
            }
            
            // Look for picture/image tags
            if (preg_match_all('/<w:drawing>.*?<\/w:drawing>/s', $content, $drawings)) {
                log_info("Found " . count($drawings[0]) . " image drawings in $xml_file");
                
                foreach ($drawings[0] as $drawing) {
                    // Extract docPr names which could be image placeholders
                    if (preg_match('/<wp:docPr id="(\d+)" name="([^"]+)"/', $drawing, $docPr)) {
                        $image_id = $docPr[1];
                        $image_name = $docPr[2];
                        $image_placeholders[] = $image_name;
                        log_info("- Found image with name: $image_name (ID: $image_id)");
                    }
                }
            }
            
            // Also look for potential image placeholders in content controls
            if (preg_match_all('/<w:sdtPr>.*?<\/w:sdtPr>/s', $content, $sdts)) {
                log_info("Found " . count($sdts[0]) . " content controls in $xml_file");
                
                foreach ($sdts[0] as $sdt) {
                    if (preg_match('/<w:alias w:val="([^"]+)"/', $sdt, $alias)) {
                        $control_name = $alias[1];
                        log_info("- Found content control with alias: $control_name");
                        // If it contains words suggesting images
                        if (stripos($control_name, 'image') !== false || 
                            stripos($control_name, 'photo') !== false || 
                            stripos($control_name, 'picture') !== false) {
                            $image_placeholders[] = $control_name;
                            log_info("  - Likely an image placeholder: $control_name");
                        }
                    }
                }
            }
        }
    }
    
    // Show summary
    log_info("=== ANALYSIS SUMMARY ===");
    log_info("Total text placeholders found: " . count(array_unique($placeholders)));
    log_info("Found placeholders: " . implode(", ", array_unique($placeholders)));
    
    log_info("Total potential image placeholders found: " . count(array_unique($image_placeholders)));
    log_info("Potential image placeholders: " . implode(", ", array_unique($image_placeholders)));
    
    // Recommend correct placeholder names based on findings
    log_info("=== RECOMMENDED IMAGE PLACEHOLDERS ===");
    if (!empty($image_placeholders)) {
        foreach (array_unique($image_placeholders) as $img_placeholder) {
            log_info("Try using: '" . $img_placeholder . "' for your image");
        }
    } else {
        log_info("No clear image placeholders found. Try the docx-templates approach instead.");
    }
    
} else {
    log_info("Failed to open template as ZIP archive - trying alternative approach");
    
    // Alternative approach using PHPWord
    try {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($template_file);
        log_info("Successfully loaded template with PHPWord");
        
        $sections = $phpWord->getSections();
        log_info("Found " . count($sections) . " sections in the document");
        
        // Since we can't easily get placeholders from PHPWord, offer general advice
        log_info("Cannot directly extract image placeholders with PHPWord.");
        log_info("Try common placeholder names like: ${image}, ${photo}, ${picture}");
    } catch (Exception $e) {
        log_info("Error analyzing template with PHPWord: " . $e->getMessage());
    }
}

// Clean up the temporary directory recursively
function removeDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

removeDirectory($temp_dir);
log_info("Template analysis complete");
?>
