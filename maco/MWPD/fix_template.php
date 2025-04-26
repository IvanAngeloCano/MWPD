<?php
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

// Create a new document
$phpWord = new PhpWord();

// Add a section
$section = $phpWord->addSection();

// Add content with placeholders
$section->addText('Direct Hire Clearance Document');
$section->addText('Control No: ${control_no}');
$section->addText('Name: ${name}');
$section->addText('Jobsite: ${jobsite}');
$section->addText('Type: ${type}');
$section->addText('Status: ${status}');
$section->addText('Evaluator: ${evaluator}');
$section->addText('Evaluated: ${evaluated}');
$section->addText('For Confirmation: ${for_confirmation}');
$section->addText('Emailed to DHAD: ${emailed_to_dhad}');
$section->addText('Received from DHAD: ${received_from_dhad}');
$section->addText('Note: ${note}');
$section->addText('Current Date: ${current_date}');

// Save the document
$objWriter = IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('Directhireclearance.docx');

echo "Template file created successfully with proper placeholders.\n";
