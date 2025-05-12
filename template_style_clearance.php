<?php
/**
 * Direct hire clearance document generator that:
 * 1. Creates a document that looks like your template
 * 2. Doesn't rely on ZipArchive
 * 3. Still includes images
 */
require_once 'vendor/autoload.php';
require_once 'connection.php';
include 'session.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\JcTable;

// Function to log debug information
function logDebug($message) {
    file_put_contents('clearance_generation_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Template-style clearance generation started");

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

    // Create a new PHPWord document - styled to match your template
    $phpWord = new PhpWord();
    
    // Define document styles
    $sectionStyle = [
        'orientation' => 'portrait',
        'marginTop' => 720, // 0.5 inch
        'marginRight' => 720, // 0.5 inch
        'marginBottom' => 720, // 0.5 inch
        'marginLeft' => 720, // 0.5 inch
        'headerHeight' => 720,
        'footerHeight' => 720,
    ];
    
    // Define text styles matching your template
    $fontStyleHeading = ['name' => 'Times New Roman', 'size' => 16, 'bold' => true, 'allCaps' => true];
    $fontStyleSubheading = ['name' => 'Times New Roman', 'size' => 14, 'bold' => true];
    $fontStyleNormal = ['name' => 'Times New Roman', 'size' => 12];
    $fontStyleSmall = ['name' => 'Times New Roman', 'size' => 10];
    $fontStyleBold = ['name' => 'Times New Roman', 'size' => 12, 'bold' => true];
    
    $paragraphCenter = ['alignment' => 'center', 'spaceAfter' => 0];
    $paragraphLeft = ['alignment' => 'left', 'spaceAfter' => 0];
    
    // Create document section
    $section = $phpWord->addSection($sectionStyle);
    
    // Add header - exactly as in your template
    $header = $section->addHeader();
    $table = $header->addTable();
    $table->addRow();
    $cell = $table->addCell(9000);
    $cell->addText('DEPARTMENT OF MIGRANT WORKERS', $fontStyleHeading, $paragraphCenter);
    $cell->addText('Republic of the Philippines', $fontStyleSubheading, $paragraphCenter);
    $cell->addText('MIGRANT WORKERS PROTECTION DIVISION', $fontStyleSubheading, $paragraphCenter);
    
    // Main document
    $section->addTextBreak(1);
    $section->addText('DIRECT HIRE CLEARANCE', $fontStyleHeading, $paragraphCenter);
    $section->addTextBreak(1);
    
    // Date and info
    $currentDate = date('F j, Y');
    $section->addText("Control Number: " . $record['control_no'], $fontStyleNormal, $paragraphLeft);
    $section->addText("Date: $currentDate", $fontStyleNormal, $paragraphLeft);
    $section->addTextBreak(1);
    
    // Details table - recreating your template's look
    $tableStyle = [
        'borderSize' => 6, 
        'borderColor' => '000000',
        'cellMargin' => 80,
        'alignment' => 'center',
        'width' => 100 * 50 // 100% of page width
    ];
    
    $tableCellStyle = ['valign' => 'center'];
    $tableBoldCellStyle = ['valign' => 'center', 'bgColor' => 'EEEEEE'];
    
    // Applicant Information table
    $table = $section->addTable($tableStyle);
    
    // Header row
    $table->addRow();
    $cell = $table->addCell(9000, $tableBoldCellStyle);
    $cell->addText('APPLICANT INFORMATION', $fontStyleBold, $paragraphCenter);
    
    // Name row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Name:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText($record['name'] ?? 'Not specified', $fontStyleNormal);
    
    // Job site row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Job Site:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText($record['jobsite'] ?? 'Not specified', $fontStyleNormal);
    
    // Type row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Type:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText(ucfirst($record['type'] ?? 'Not specified'), $fontStyleNormal);
    
    // Status row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Status:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText(ucfirst($record['status'] ?? 'Not specified'), $fontStyleNormal);
    
    // Evaluator row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Evaluator:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText($record['evaluator'] ?? 'Not assigned', $fontStyleNormal);
    
    $section->addTextBreak(1);
    
    // Processing Information table
    $table = $section->addTable($tableStyle);
    
    // Header row
    $table->addRow();
    $cell = $table->addCell(9000, $tableBoldCellStyle);
    $cell->addText('PROCESSING INFORMATION', $fontStyleBold, $paragraphCenter);
    
    // Format dates properly
    $evaluated_formatted = !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : 'Not set';
    $for_confirmation_formatted = !empty($record['for_confirmation']) ? date('F j, Y', strtotime($record['for_confirmation'])) : 'Not set';
    $emailed_to_dhad_formatted = !empty($record['emailed_to_dhad']) ? date('F j, Y', strtotime($record['emailed_to_dhad'])) : 'Not set';
    $received_from_dhad_formatted = !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : 'Not set';
    
    // Evaluated row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Evaluated:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText($evaluated_formatted, $fontStyleNormal);
    
    // For Confirmation row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('For Confirmation:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText($for_confirmation_formatted, $fontStyleNormal);
    
    // Emailed to DHAD row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Emailed to DHAD:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText($emailed_to_dhad_formatted, $fontStyleNormal);
    
    // Received from DHAD row
    $table->addRow();
    $cell = $table->addCell(1800, $tableBoldCellStyle);
    $cell->addText('Received from DHAD:', $fontStyleBold);
    $cell = $table->addCell(7200);
    $cell->addText($received_from_dhad_formatted, $fontStyleNormal);
    
    $section->addTextBreak(1);
    
    // Comments/Notes
    if (!empty($record['note'])) {
        $table = $section->addTable($tableStyle);
        
        // Header row
        $table->addRow();
        $cell = $table->addCell(9000, $tableBoldCellStyle);
        $cell->addText('NOTES', $fontStyleBold, $paragraphCenter);
        
        // Notes row
        $table->addRow();
        $cell = $table->addCell(9000);
        $cell->addText($record['note'], $fontStyleNormal);
        
        $section->addTextBreak(1);
    }
    
    // Image and Signature Layout Table
    $table = $section->addTable();
    $table->addRow();
    
    // Left cell for applicant image
    $leftCell = $table->addCell(4500);
    $leftCell->addText('APPLICANT PHOTO:', $fontStyleBold, $paragraphCenter);
    
    // Right cell for signature
    $rightCell = $table->addCell(4500);
    $rightCell->addText('APPROVAL:', $fontStyleBold, $paragraphCenter);
    
    // Now add the image and signature to their cells
    // First, the applicant image
    logDebug("Attempting to add applicant image");
    
    // Get image from database
    $image_query = "SELECT * FROM direct_hire_documents WHERE direct_hire_id = ? AND file_type LIKE 'image/%' LIMIT 1";
    $image_stmt = $pdo->prepare($image_query);
    $image_stmt->execute([$direct_hire_id]);
    $image_data = $image_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image_data && !empty($image_data['file_content'])) {
        logDebug("Found image in database with ID: " . $image_data['id']);
        
        // Create a temporary file for the image
        $temp_image = sys_get_temp_dir() . '/' . uniqid('img_') . '.jpg';
        file_put_contents($temp_image, $image_data['file_content']);
        
        logDebug("Saved image to temporary file: $temp_image");
        
        // Add the image to the left cell
        try {
            $leftCell->addImage(
                $temp_image,
                [
                    'width' => 150,
                    'height' => 200,
                    'alignment' => 'center'
                ]
            );
            logDebug("Successfully added applicant image to document");
        } catch (Exception $e) {
            logDebug("Error adding image: " . $e->getMessage());
            $leftCell->addText('Image could not be displayed', $fontStyleSmall, $paragraphCenter);
        }
        
        // Clean up temp image when script completes
        register_shutdown_function(function() use ($temp_image) {
            if (file_exists($temp_image)) {
                unlink($temp_image);
            }
        });
    } else {
        logDebug("No image found for this record or image content is empty");
        $leftCell->addText('No applicant photo available', $fontStyleSmall, $paragraphCenter);
    }
    
    // Now the signature section
    if ($isApproved) {
        // Try to add signature image
        $signatureFile = 'signatures/Signature.png';
        if (file_exists($signatureFile)) {
            try {
                $rightCell->addImage(
                    $signatureFile,
                    [
                        'width' => 150,
                        'height' => 75,
                        'alignment' => 'center'
                    ]
                );
                logDebug("Added signature image");
            } catch (Exception $e) {
                logDebug("Error adding signature: " . $e->getMessage());
                $rightCell->addText('✓ APPROVED', ['bold' => true, 'color' => '00AA00'], $paragraphCenter);
            }
        } else {
            logDebug("Signature file not found: $signatureFile");
            $rightCell->addText('✓ APPROVED', ['bold' => true, 'color' => '00AA00'], $paragraphCenter);
        }
        
        // Add approval details
        if (!empty($record['approved_by'])) {
            $approver_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $approver_stmt->execute([$record['approved_by']]);
            $approver = $approver_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($approver) {
                $rightCell->addText('Regional Director: ' . $approver['full_name'], $fontStyleNormal, $paragraphCenter);
            } else {
                $rightCell->addText('Regional Director', $fontStyleNormal, $paragraphCenter);
            }
        } else {
            $rightCell->addText('Regional Director', $fontStyleNormal, $paragraphCenter);
        }
        
        $rightCell->addText('Date: ' . (!empty($record['approved_at']) ? date('F j, Y', strtotime($record['approved_at'])) : date('F j, Y')), $fontStyleNormal, $paragraphCenter);
    } else {
        $rightCell->addText('PENDING APPROVAL', ['bold' => true, 'color' => 'AA0000'], $paragraphCenter);
    }
    
    // Add a footer
    $footer = $section->addFooter();
    $footer->addText('Generated on: ' . date('F j, Y \a\t g:i A'), $fontStyleSmall, ['alignment' => 'right']);
    $footer->addText('MWPD Clearance Document', $fontStyleSmall, ['alignment' => 'center']);
    $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', $fontStyleSmall, ['alignment' => 'center']);
    
    // Generate unique file name for DOCX
    $docName = 'clearance_' . $record['control_no'] . '_' . date('Ymd_His');
    
    // Save document
    $tempDir = 'temp';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    $outputPath = $tempDir . '/' . $docName . '.docx';
    logDebug("Saving document to: $outputPath");
    
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($outputPath);
    
    logDebug("Document created successfully");
    
    // Serve the file directly to the browser
    if (file_exists($outputPath)) {
        logDebug("Document exists and will be served directly");
        
        // Set headers for document download
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . basename($outputPath) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($outputPath));
        
        // Clean output buffer
        ob_clean();
        flush();
        
        // Read file content directly to output
        readfile($outputPath);
        
        // Clean up the temporary DOCX file after serving it
        register_shutdown_function(function() use ($outputPath) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        });
        
        exit();
    } else {
        logDebug("Error: DOCX file not found at $outputPath");
        die("Error: Could not generate document.");
    }
    
} catch (Exception $e) {
    logDebug("Error: " . $e->getMessage());
    echo "Error generating document: " . $e->getMessage();
    echo "<br><a href='direct_hire_view.php?id={$direct_hire_id}'>Back to Record</a>";
}
?>
