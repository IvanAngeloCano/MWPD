<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'session.php';
require_once 'connection.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

// Function to log debugging information
function logDebug($message) {
    file_put_contents('clearance_generation_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Clearance generation script started");

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

    // Create the clearance document with template
    $templateFile = 'Directhireclearance.docx';
    
    if (!file_exists($templateFile)) {
        logDebug("Template file not found: $templateFile");
        die("Error: Template file not found.");
    }
    
    logDebug("Using template file: $templateFile");
    $template = new TemplateProcessor($templateFile);
    
    // Format dates properly
    $evaluated_formatted = !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : 'Not set';
    $for_confirmation_formatted = !empty($record['for_confirmation']) ? date('F j, Y', strtotime($record['for_confirmation'])) : 'Not set';
    $emailed_to_dhad_formatted = !empty($record['emailed_to_dhad']) ? date('F j, Y', strtotime($record['emailed_to_dhad'])) : 'Not set';
    $received_from_dhad_formatted = !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : 'Not set';
    
    // Replace placeholders with actual data
    // Basic information
    $template->setValue('control_no', $record['control_no']);
    $template->setValue('name', $record['name']);
    $template->setValue('jobsite', $record['jobsite']);
    $template->setValue('type', ucfirst($record['type']));
    $template->setValue('status', ucfirst($record['status']));
    $template->setValue('evaluator', $record['evaluator'] ?? 'Not assigned');
    
    // Set date values
    $template->setValue('evaluated', $evaluated_formatted);
    $template->setValue('for_confirmation', $for_confirmation_formatted);
    $template->setValue('emailed_to_dhad', $emailed_to_dhad_formatted);
    $template->setValue('received_from_dhad', $received_from_dhad_formatted);
    
    // Set current date
    $template->setValue('current_date', date('F j, Y'));
    
    // Add comments or note if available
    $template->setValue('comments', !empty($record['note']) ? $record['note'] : 'No additional comments.');
    
    // If there's an approver, add their information
    if (!empty($record['approved_by'])) {
        // Get approver details
        $approver_name = 'Regional Director';
        $approver_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $approver_stmt->execute([$record['approved_by']]);
        $approver = $approver_stmt->fetch(PDO::FETCH_ASSOC);
        if ($approver) {
            $approver_name = $approver['full_name'];
        }
        
        $template->setValue('approved_by', $approver_name);
        $template->setValue('approved_date', !empty($record['approved_at']) ? date('F j, Y', strtotime($record['approved_at'])) : date('F j, Y'));
    } else {
        $template->setValue('approved_by', 'Regional Director');
        $template->setValue('approved_date', date('F j, Y'));
    }
    
    // Create temp directory if it doesn't exist
    $tempDir = 'temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Generate unique filename for the document
    $docName = 'Clearance_' . $record['control_no'] . '_' . date('Ymd_His');
    $docxFile = $docName . '.docx';
    $tempDocxPath = $tempDir . '/' . $docxFile;

    // Handle signature before saving template
    if ($isApproved) {
        // For approved status, try to insert the signature image using the correct placeholder format
        logDebug("Status is approved - adding signature image");
        
        $signatureFile = 'signatures/Signature.png';
        if (file_exists($signatureFile)) {
            try {
                // Use the suggested placeholder format
                $template->setImageValue('signature1_image', [
                    'path' => realpath($signatureFile),
                    'width' => 150,
                    'height' => 75,
                    'ratio' => false
                ]);
                logDebug("Added signature image using signature1_image placeholder");
            } catch (Exception $e) {
                logDebug("Error adding signature image: " . $e->getMessage());
                $template->setValue('signature1_image', '✓ APPROVED');
            }
        } else {
            logDebug("Signature file not found: $signatureFile");
            $template->setValue('signature1_image', '✓ APPROVED');
        }
    } else {
        // For non-approved, leave empty
        $template->setValue('signature1_image', '');
        logDebug("Status is not approved - no signature");
    }

    // Save the document with filled template
    $template->saveAs($tempDocxPath);
    logDebug("Document saved to: $tempDocxPath");

    // Make sure uploads directory exists
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // Convert DOCX to PDF using LibreOffice (if available)
    $libreOfficeCommand = 'soffice --headless --convert-to pdf --outdir uploads ' . $tempDocxPath;
    
    // Check if we're on Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Try to find LibreOffice on Windows
        $libreOfficePaths = [
            'C:\Program Files\LibreOffice\program\soffice.exe',
            'C:\Program Files (x86)\LibreOffice\program\soffice.exe'
        ];
        
        $libreOfficeExe = null;
        foreach ($libreOfficePaths as $path) {
            if (file_exists($path)) {
                $libreOfficeExe = '"' . $path . '"';
                break;
            }
        }
        
        if ($libreOfficeExe) {
            $libreOfficeCommand = $libreOfficeExe . ' --headless --convert-to pdf --outdir uploads ' . $tempDocxPath;
        }
    }
    
    // Try to execute the command
    $output = [];
    $returnVar = 0;
    exec($libreOfficeCommand . ' 2>&1', $output, $returnVar);
    
    if ($returnVar !== 0) {
        logDebug("LibreOffice conversion failed: " . implode("\n", $output));
        logDebug("Falling back to direct DOCX display");
        
        // If conversion fails, just serve the DOCX file
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: inline; filename="' . $docName . '.docx"');
        header('Content-Length: ' . filesize($tempDocxPath));
        readfile($tempDocxPath);
    } else {
        logDebug("PDF conversion successful");
        
        // If the conversion was successful, the PDF will be in the uploads directory
        $generatedPdfPath = 'uploads/' . pathinfo($docxFile, PATHINFO_FILENAME) . '.pdf';
        
        if (file_exists($generatedPdfPath)) {
            // Serve the PDF file
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $docName . '.pdf"');
            header('Content-Length: ' . filesize($generatedPdfPath));
            readfile($generatedPdfPath);
            
            logDebug("Document served without storing in database as requested");
        } else {
            logDebug("Generated PDF not found: $generatedPdfPath");
            
            // If PDF not found, serve the DOCX file
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: inline; filename="' . $docName . '.docx"');
            header('Content-Length: ' . filesize($tempDocxPath));
            readfile($tempDocxPath);
        }
    }
    
    // Clean up the temporary DOCX file
    if (file_exists($tempDocxPath)) {
        unlink($tempDocxPath);
        logDebug("Cleaned up temporary DOCX file: $tempDocxPath");
    }
    
    // Register a shutdown function to clean up the generated PDF file after it's served
    register_shutdown_function(function() use ($generatedPdfPath) {
        if (file_exists($generatedPdfPath)) {
            unlink($generatedPdfPath);
            // Can't log here as the script has already completed
        }
    });
    
} catch (Exception $e) {
    logDebug("Error: " . $e->getMessage());
    die("Error generating document: " . $e->getMessage());
}
?>
