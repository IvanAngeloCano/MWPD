<?php
include 'session.php';
require_once 'connection.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logDebug($message) {
    file_put_contents('process_clearance_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Process clearance approval script started");

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
    header('Location: clearance_approvals.php');
    exit();
}

// Get form data
$record_id = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;
$approval_id = isset($_POST['approval_id']) ? (int)$_POST['approval_id'] : 0;
$decision = isset($_POST['decision']) ? $_POST['decision'] : '';
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

logDebug("Form data: record_id=$record_id, approval_id=$approval_id, decision=$decision");

// Validate data
if ($record_id <= 0) {
    $_SESSION['error_message'] = "Invalid record ID.";
    logDebug("Invalid record ID");
    header('Location: clearance_approvals.php');
    exit();
}

if ($decision !== 'approved' && $decision !== 'denied') {
    $_SESSION['error_message'] = "Invalid decision. Please select either Approve or Decline.";
    logDebug("Invalid decision");
    header("Location: clearance_approval_view.php?id=$record_id&approval_id=$approval_id");
    exit();
}

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
    
    // Update record status in direct_hire table
    $update_stmt = $pdo->prepare("UPDATE direct_hire SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
    $update_stmt->execute([
        $decision,
        $_SESSION['user_id'] ?? null,
        $record_id
    ]);
    logDebug("Updated direct_hire record status to $decision");
    
    // Update approval record if it exists
    if ($approval_id > 0) {
        try {
            $approval_update = $pdo->prepare("UPDATE direct_hire_clearance_approvals SET status = ?, approved_by = ?, comments = ?, updated_at = NOW() WHERE id = ?");
            $approval_update->execute([
                $decision,
                $_SESSION['user_id'] ?? null,
                $comments,
                $approval_id
            ]);
            logDebug("Updated approval record (ID: $approval_id) status to $decision");
        } catch (PDOException $e) {
            logDebug("Error updating approval record: " . $e->getMessage());
            // Continue even if this fails
        }
    }
    
    // If approved, generate a final clearance with e-signature
    if ($decision === 'approved') {
        logDebug("Generating approved clearance document");
        
        // Create the clearance document with e-signature
        $template = new TemplateProcessor('Directhireclearance.docx');
        
        // Map all database fields to their corresponding placeholders
        // Basic information
        $template->setValue('control_no', $record['control_no']);
        $template->setValue('name', $record['name']);
        $template->setValue('jobsite', $record['jobsite']);
        $template->setValue('type', ucfirst($record['type']));
        $template->setValue('status', ucfirst($decision));
        $template->setValue('evaluator', $record['evaluator'] ?? 'Not assigned');
        
        // Format dates properly
        $evaluated_formatted = !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : 'Not set';
        $for_confirmation_formatted = !empty($record['for_confirmation']) ? date('F j, Y', strtotime($record['for_confirmation'])) : 'Not set';
        $emailed_to_dhad_formatted = !empty($record['emailed_to_dhad']) ? date('F j, Y', strtotime($record['emailed_to_dhad'])) : 'Not set';
        $received_from_dhad_formatted = !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : 'Not set';
        
        // Set date values
        $template->setValue('evaluated', $evaluated_formatted);
        $template->setValue('for_confirmation', $for_confirmation_formatted);
        $template->setValue('emailed_to_dhad', $emailed_to_dhad_formatted);
        $template->setValue('received_from_dhad', $received_from_dhad_formatted);
        
        // Set current date
        $template->setValue('current_date', date('F j, Y'));
        
        // Add comments if provided
        $template->setValue('comments', !empty($comments) ? $comments : 'No additional comments.');
        
        // Add approver information
        $template->setValue('approved_by', $_SESSION['name'] ?? 'Regional Director');
        $template->setValue('approved_date', date('F j, Y'));
        
        // Add e-signature image if available
        $signatureFile = 'signatures/Signature.png';
        if (file_exists($signatureFile)) {
            $template->setImageValue('signature', array(
                'path' => $signatureFile,
                'width' => 100,
                'height' => 50,
                'ratio' => false
            ));
        } else {
            $template->setValue('signature', '[E-Signature]');
        }
        
        // Save the document
        $outputDir = 'uploads/direct_hire_clearance';
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        
        $docxFile = 'approved_clearance_' . $record_id . '_' . date('Ymd_His') . '.docx';
        $finalDocxFile = $outputDir . '/' . $docxFile;
        $template->saveAs($finalDocxFile);
        logDebug("Saved approved document to $finalDocxFile");
        
        // Save the document reference to the database
        $stmt = $pdo->prepare("INSERT INTO direct_hire_documents (direct_hire_id, filename, original_filename, file_type, file_size, is_approved) 
                             VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $record_id,
            $docxFile,
            'Approved Clearance - ' . $record['name'] . '.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            filesize($finalDocxFile)
        ]);
        logDebug("Saved document reference to database");
    }
    
    // Commit transaction
    $pdo->commit();
    logDebug("Transaction committed successfully");
    
    // Set success message
    if ($decision === 'approved') {
        $_SESSION['success_message'] = "Clearance has been approved and document with e-signature has been generated.";
    } else {
        $_SESSION['success_message'] = "Clearance has been declined.";
    }
    
    // Redirect to pending approvals
    header('Location: clearance_approvals.php');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    logDebug("Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: clearance_approval_view.php?id=$record_id&approval_id=$approval_id");
    exit();
}
