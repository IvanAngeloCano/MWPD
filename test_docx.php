<?php
// Simple test script for checking PHPWord functionality

require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

try {
    // Create a new PHPWord document
    $phpWord = new PhpWord();
    
    // Add a section to the document
    $section = $phpWord->addSection();
    
    // Add text to the document
    $section->addText('This is a test document created by PHPWord');
    $section->addText('If you can see this, the PHPWord library is working correctly');
    
    // Save the document
    $outputFile = 'test_document.docx';
    $phpWord->save($outputFile, 'Word2007');
    
    echo "Successfully created test DOCX file: $outputFile<br>";
    echo "File size: " . filesize($outputFile) . " bytes<br>";
    
    // Try to open an existing DOCX file
    echo "<h3>Testing template loading</h3>";
    $template = new \PhpOffice\PhpWord\TemplateProcessor('Directhireclearance.docx');
    echo "Successfully loaded template!<br>";
    
    // Try replacing a simple placeholder
    $template->setValue('test', 'Test value');
    echo "Successfully set text value<br>";
    
    // Save the template
    $testTempFile = 'test_template_output.docx';
    $template->saveAs($testTempFile);
    echo "Successfully saved edited template: $testTempFile<br>";
    
    echo "<h3>All tests completed successfully!</h3>";
    
} catch (Exception $e) {
    echo "<h3>Error occurred:</h3>";
    echo $e->getMessage();
    echo "<pre>";
    print_r($e->getTraceAsString());
    echo "</pre>";
}
?>
