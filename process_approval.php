<?php
include 'session.php';
require_once 'connection.php';
require_once 'vendor/autoload.php';
require_once 'notifications.php';

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logDebug($message) {
    file_put_contents('approval_debug_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Process approval script started");

// Ensure only regional directors can access this page
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    $_SESSION['error_message'] = "Access denied. Only Regional Directors can process approvals.";
    logDebug("Access denied - not a regional director");
    header('Location: index.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method.";
    logDebug("Invalid request method");
    header('Location: approval_view_simple.php');
    exit();
}

// Get form data
$action = isset($_POST['action']) ? $_POST['action'] : '';
$approval_id = isset($_POST['approval_id']) ? (int)$_POST['approval_id'] : 0;
$record_id = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

logDebug("Form data: action=$action, record_id=$record_id, approval_id=$approval_id");

// Validate data
if ($approval_id <= 0 || $record_id <= 0) {
    $_SESSION['error_message'] = "Invalid record or approval ID.";
    logDebug("Invalid record or approval ID");
    header('Location: approval_view_simple.php');
    exit();
}

if ($action !== 'approve' && $action !== 'deny') {
    $_SESSION['error_message'] = "Invalid action. Please select either Approve or Deny.";
    logDebug("Invalid action: $action");
    header('Location: approval_view_simple.php');
    exit();
}

// Map action to status
$status = ($action === 'approve') ? 'approved' : 'denied';

try {
    // Start transaction
    $pdo->beginTransaction();
    logDebug("Transaction started");
    
    // Get record details
    $stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        logDebug("Record not found");
        throw new Exception("Record not found.");
    }
    
    logDebug("Record found: " . json_encode($record));
    
    // Get approval record to find the submitter
    $approval_stmt = $pdo->prepare("SELECT * FROM direct_hire_clearance_approvals WHERE id = ?");
    $approval_stmt->execute([$approval_id]);
    $approval_record = $approval_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$approval_record) {
        logDebug("Approval record not found");
        throw new Exception("Approval record not found.");
    }
    
    $submitted_by = $approval_record['submitted_by'] ?? 0;
    logDebug("Approval record found, submitted by user ID: $submitted_by");
    
    // Update record status in direct_hire table
    $update_stmt = $pdo->prepare("UPDATE direct_hire SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
    $update_stmt->execute([
        $status,
        $_SESSION['user_id'] ?? null,
        $record_id
    ]);
    logDebug("Updated direct_hire record status to $status");
    
    // Update approval record
    $approval_update = $pdo->prepare("UPDATE direct_hire_clearance_approvals SET status = ?, approved_by = ?, comments = ?, updated_at = NOW() WHERE id = ?");
    $approval_update->execute([
        $status,
        $_SESSION['user_id'] ?? null,
        $comments,
        $approval_id
    ]);
    logDebug("Updated approval record (ID: $approval_id) status to $status");
    
    // If approved, generate a final clearance with e-signature
    if ($status === 'approved') {
        logDebug("Generating approved clearance document with e-signature");
        
        // Create temp directory if it doesn't exist
        $tempDir = 'temp';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        
        // Make sure uploads directory exists
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        // Generate unique filename for the document
        $docName = 'Approved_Clearance_' . $record['control_no'] . '_' . date('Ymd_His');
        $docxFile = $docName . '.docx';
        $pdfFile = $docName . '.pdf';
        $tempDocxPath = $tempDir . '/' . $docxFile;
        
        // Create the clearance document with template
        $template = new TemplateProcessor('Directhireclearance.docx');
        
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
        $template->setValue('status', ucfirst($status));
        $template->setValue('evaluator', $record['evaluator'] ?? 'Not assigned');
        
        // Set date values
        $template->setValue('evaluated', $evaluated_formatted);
        $template->setValue('for_confirmation', $for_confirmation_formatted);
        $template->setValue('emailed_to_dhad', $emailed_to_dhad_formatted);
        $template->setValue('received_from_dhad', $received_from_dhad_formatted);
        
        // Set current date
        $template->setValue('current_date', date('F j, Y'));
        
        // Add comments if provided
        $template->setValue('comments', !empty($comments) ? $comments : 'No additional comments.');
        
        // Get approver details
        $approver_name = $_SESSION['name'] ?? 'Regional Director';
        if (isset($_SESSION['user_id'])) {
            $approver_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $approver_stmt->execute([$_SESSION['user_id']]);
            $approver = $approver_stmt->fetch(PDO::FETCH_ASSOC);
            if ($approver) {
                $approver_name = $approver['full_name'];
            }
        }
        
        // Add approver information
        $template->setValue('approved_by', $approver_name);
        $template->setValue('approved_date', date('F j, Y'));
        
        // Add e-signature image if available
        $signatureFile = 'signatures/Signature.png';
        if (file_exists($signatureFile)) {
            try {
                $template->setImageValue('signature', array(
                    'path' => $signatureFile,
                    'width' => 100,
                    'height' => 50,
                    'ratio' => false
                ));
                logDebug("Added signature image");
            } catch (Exception $e) {
                logDebug("Error adding signature image: " . $e->getMessage());
                $template->setValue('signature', '[E-Signature]');
            }
        } else {
            logDebug("Signature file not found: $signatureFile");
            $template->setValue('signature', '[E-Signature]');
        }
        
        // Save the document with filled template
        $template->saveAs($tempDocxPath);
        logDebug("Document saved to: $tempDocxPath");
        
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
        
        // Determine which file to save in the database
        $finalFilename = '';
        $fileType = '';
        $originalFilename = '';
        
        if ($returnVar === 0) {
            logDebug("PDF conversion successful");
            // If the conversion was successful, the PDF will be in the uploads directory
            $generatedPdfPath = 'uploads/' . pathinfo($docxFile, PATHINFO_FILENAME) . '.pdf';
            
            if (file_exists($generatedPdfPath)) {
                $finalFilename = basename($generatedPdfPath);
                $fileType = 'application/pdf';
                $originalFilename = 'Approved Clearance - ' . $record['name'] . '.pdf';
                $fileSize = filesize($generatedPdfPath);
            } else {
                logDebug("Generated PDF not found: $generatedPdfPath");
                $finalFilename = $docxFile;
                $fileType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                $originalFilename = 'Approved Clearance - ' . $record['name'] . '.docx';
                $fileSize = filesize($tempDocxPath);
                
                // Move the DOCX file to uploads directory
                copy($tempDocxPath, 'uploads/' . $docxFile);
            }
        } else {
            logDebug("LibreOffice conversion failed: " . implode("\n", $output));
            $finalFilename = $docxFile;
            $fileType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $originalFilename = 'Approved Clearance - ' . $record['name'] . '.docx';
            $fileSize = filesize($tempDocxPath);
            
            // Move the DOCX file to uploads directory
            copy($tempDocxPath, 'uploads/' . $docxFile);
        }
        
        // Save the document reference to the database
        $stmt = $pdo->prepare("
            INSERT INTO direct_hire_documents 
            (direct_hire_id, filename, original_filename, file_type, file_size, is_approved) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $record_id,
            $finalFilename,
            $originalFilename,
            $fileType,
            $fileSize
        ]);
        logDebug("Document reference saved to database");
        
        // Clean up the temporary DOCX file
        if (file_exists($tempDocxPath)) {
            unlink($tempDocxPath);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    logDebug("Transaction committed successfully");
    
    // Send notification to the submitter
    if ($submitted_by > 0) {
        $name = $record['name'] ?? 'Applicant';
        try {
            $result = notifyApprovalDecision($record_id, $approval_id, $submitted_by, $name, $status, $comments);
            logDebug("Notification sent to user $submitted_by about $name approval status: $status - Result: " . ($result ? 'Success' : 'Failed'));
        } catch (Exception $e) {
            logDebug("Error sending notification: " . $e->getMessage());
            // Don't throw the exception, just log it - we don't want to fail the whole transaction if notification fails
        }
    } else {
        logDebug("No submitted_by user found for approval ID: $approval_id");
    }
    
    // Set success message
    if ($status === 'approved') {
        $_SESSION['success_message'] = "Record has been approved and clearance document with e-signature has been generated.";
    } else {
        $_SESSION['success_message'] = "Record has been denied.";
    }
    
    // Redirect back to the approval detail view
    header("Location: approval_detail_view.php?id=$approval_id");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    logDebug("Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: approval_detail_view.php?id=$approval_id");
    exit();
}
?>
