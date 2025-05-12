<?php
// Direct clearance document generator without using templates or ZipArchive
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'session.php';
require_once 'connection.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

// Function to log debugging information
function logDebug($message) {
    file_put_contents('clearance_generation_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Direct clearance generation started (no template/ZipArchive)");

// Check if direct hire ID is provided
if (!isset($_GET['id'])) {
    logDebug("No direct hire ID provided");
    die("Error: No direct hire ID specified.");
}

$direct_hire_id = (int)$_GET['id'];
logDebug("Direct hire ID: $direct_hire_id");

try {
    // Get direct hire record details
    $stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
    $stmt->execute([$direct_hire_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        logDebug("Record not found");
        die("Error: Direct hire record not found.");
    }
    
    logDebug("Record found: " . json_encode($record));
    
    // Check if record is approved
    $isApproved = ($record['status'] === 'approved');
    logDebug("Record approval status: " . ($isApproved ? 'Approved' : 'Not approved'));

    // Format dates properly
    $evaluated_formatted = !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : 'Not set';
    $for_confirmation_formatted = !empty($record['for_confirmation']) ? date('F j, Y', strtotime($record['for_confirmation'])) : 'Not set';
    $emailed_to_dhad_formatted = !empty($record['emailed_to_dhad']) ? date('F j, Y', strtotime($record['emailed_to_dhad'])) : 'Not set';
    $received_from_dhad_formatted = !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : 'Not set';
    
    // Create new document from scratch
    $phpWord = new PhpWord();
    
    // Define styles
    $titleStyle = ['bold' => true, 'size' => 18, 'name' => 'Arial'];
    $headerStyle = ['bold' => true, 'size' => 14, 'name' => 'Arial'];
    $subheaderStyle = ['bold' => true, 'size' => 12, 'name' => 'Arial'];
    $normalStyle = ['size' => 11, 'name' => 'Arial'];
    $paragraphStyle = ['alignment' => 'center', 'spaceAfter' => 240];
    
    // Add sections and content
    $section = $phpWord->addSection();
    
    // Title
    $section->addText('DIRECT HIRE CLEARANCE DOCUMENT', $titleStyle, ['alignment' => 'center']);
    $section->addTextBreak(1);
    
    // Basic Information
    $section->addText('BASIC INFORMATION', $headerStyle, ['alignment' => 'left']);
    
    // Add content in a table for better formatting
    $table = $section->addTable(['borderSize' => 1, 'borderColor' => '000000', 'width' => 100, 'unit' => 'pct']);
    
    // Helper function to add a row
    function addTableRow($table, $label, $value) {
        $row = $table->addRow();
        $row->addCell(2500)->addText($label, ['bold' => true]);
        $row->addCell(7500)->addText($value);
    }
    
    // Add rows with data
    addTableRow($table, 'Control Number:', $record['control_no'] ?? '');
    addTableRow($table, 'Name:', $record['name'] ?? '');
    addTableRow($table, 'Jobsite:', $record['jobsite'] ?? '');
    addTableRow($table, 'Type:', ucfirst($record['type'] ?? ''));
    addTableRow($table, 'Status:', ucfirst($record['status'] ?? ''));
    addTableRow($table, 'Evaluator:', $record['evaluator'] ?? 'Not assigned');
    
    // Dates section
    $section->addTextBreak(1);
    $section->addText('IMPORTANT DATES', $headerStyle, ['alignment' => 'left']);
    
    $dateTable = $section->addTable(['borderSize' => 1, 'borderColor' => '000000', 'width' => 100, 'unit' => 'pct']);
    addTableRow($dateTable, 'Evaluated:', $evaluated_formatted);
    addTableRow($dateTable, 'For Confirmation:', $for_confirmation_formatted);
    addTableRow($dateTable, 'Emailed to DHAD:', $emailed_to_dhad_formatted);
    addTableRow($dateTable, 'Received from DHAD:', $received_from_dhad_formatted);
    
    // Comments section
    $section->addTextBreak(1);
    $section->addText('COMMENTS', $headerStyle, ['alignment' => 'left']);
    $section->addText(!empty($record['note']) ? $record['note'] : 'No additional comments.');
    
    // Approval section
    $section->addTextBreak(1);
    $section->addText('APPROVAL', $headerStyle, ['alignment' => 'left']);
    
    if ($isApproved) {
        $approver_name = 'Regional Director';
        if (!empty($record['approved_by'])) {
            $approver_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $approver_stmt->execute([$record['approved_by']]);
            $approver = $approver_stmt->fetch(PDO::FETCH_ASSOC);
            if ($approver) {
                $approver_name = $approver['full_name'];
            }
        }
        
        $approvalDate = !empty($record['approved_at']) ? date('F j, Y', strtotime($record['approved_at'])) : date('F j, Y');
        
        $section->addText('✓ APPROVED', ['bold' => true, 'color' => '008800']);
        $section->addText('Approved by: ' . $approver_name);
        $section->addText('Date: ' . $approvalDate);
        
        // Add signature image if available
        $signatureFile = 'signatures/Signature.png';
        if (file_exists($signatureFile)) {
            try {
                $section->addImage($signatureFile, ['width' => 150, 'height' => 75]);
                logDebug("Added signature image");
            } catch (Exception $e) {
                logDebug("Error adding signature image: " . $e->getMessage());
                $section->addText('[Signature]', ['italic' => true]);
            }
        }
    } else {
        $section->addText('□ PENDING APPROVAL', ['bold' => true, 'color' => 'AA0000']);
    }
    
    // Applicant Photo section
    $section->addTextBreak(1);
    $section->addText('APPLICANT PHOTO', $headerStyle, ['alignment' => 'left']);
    
    // Get image from database
    $image_query = "SELECT * FROM direct_hire_documents WHERE direct_hire_id = ? AND file_type LIKE 'image/%' LIMIT 1";
    $image_stmt = $pdo->prepare($image_query);
    $image_stmt->execute([$direct_hire_id]);
    $image_data = $image_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image_data && !empty($image_data['file_content'])) {
        // Create a temporary file for the image
        $temp_image = sys_get_temp_dir() . '/' . uniqid('img_') . '.jpg';
        file_put_contents($temp_image, $image_data['file_content']);
        
        logDebug("Saved image to temporary file: $temp_image");
        
        // Add the image to the document
        try {
            $section->addImage($temp_image, ['width' => 200, 'height' => 200]);
            logDebug("Added applicant photo");
            
            // Clean up temp image after document generation
            register_shutdown_function(function() use ($temp_image) {
                if (file_exists($temp_image)) {
                    unlink($temp_image);
                    logDebug("Removed temporary image file");
                }
            });
        } catch (Exception $e) {
            logDebug("Error adding applicant image: " . $e->getMessage());
            $section->addText('[Applicant Photo - Error loading image]', ['italic' => true]);
        }
    } else {
        $section->addText('[No photo available for this record]', ['italic' => true]);
        logDebug("No image found for this record");
    }
    
    // Footer
    $footer = $section->addFooter();
    $footer->addText('Generated on: ' . date('F j, Y') . ' | MWPD Clearance System', 
                   ['size' => 8], ['alignment' => 'center']);
    
    // Create temp directory if it doesn't exist
    $tempDir = 'temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Generate unique filename for the document
    $docName = 'Clearance_' . ($record['control_no'] ?? 'new') . '_' . date('Ymd_His');
    $docxFile = $docName . '.docx';
    $tempDocxPath = $tempDir . '/' . $docxFile;
    
    // Save the document
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($tempDocxPath);
    
    logDebug("Document saved to: $tempDocxPath");
    
    // Make sure the document is properly saved
    if (file_exists($tempDocxPath)) {
        // Create download_docx.php URL for proper handling
        $web_path = str_replace('\\', '/', $tempDocxPath);
        $download_link = "download_docx.php?file=" . urlencode($web_path);
        
        // Output HTML with JavaScript to automatically open the document
        echo "<!DOCTYPE html>\n";
        echo "<html><head><title>Generating Document...</title></head>\n";
        echo "<body>\n";
        echo "<div style='text-align:center; margin-top:50px;'>\n";
        echo "<h2>Clearance Document Generated Successfully</h2>\n";
        echo "<p>Your document has been generated and should open automatically.</p>\n";
        echo "<p>If it doesn't open, <a href='{$download_link}' target='_blank'>click here</a> to download it.</p>\n";
        echo "<p><a href='direct_hire_view.php?id={$direct_hire_id}'>Back to Record</a></p>\n";
        echo "</div>\n";
        
        // JavaScript to automatically open the file
        echo "<script>\n";
        echo "window.location.href = '{$download_link}';\n";
        echo "</script>\n";
        echo "</body></html>\n";
        
        // Clean up the temporary DOCX file after serving it
        register_shutdown_function(function() use ($tempDocxPath) {
            if (file_exists($tempDocxPath)) {
                unlink($tempDocxPath);
                // Can't log here as the script has already completed
            }
        });
    } else {
        logDebug("Error: DOCX file not found at $tempDocxPath");
        die("Error: Could not generate document.");
    }
    
} catch (Exception $e) {
    logDebug("Error: " . $e->getMessage());
    echo "Error generating document: " . $e->getMessage();
    echo "<br><a href='direct_hire_view.php?id={$direct_hire_id}'>Back to Record</a>";
}
?>
