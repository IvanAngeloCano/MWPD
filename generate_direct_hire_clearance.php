<?php
require_once 'vendor/autoload.php'; 
require_once 'connection.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Function to log debug information
function logDebug($message) {
    file_put_contents('clearance_generation_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Handle direct download of a previously generated file
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = 'uploads/direct_hire_clearance/' . $filename;
    
    if (file_exists($filepath) && is_file($filepath)) {
        // Log the download request
        logDebug("Serving existing document: $filepath");
        
        // Serve the file
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        logDebug("File not found: $filepath");
        die('File not found');
    }
}

// Handle both GET and POST requests for generating new documents
if ((($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['record_id'])) || 
    ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])))) {
    
    // Get record ID from either GET or POST
    $record_id = ($_SERVER['REQUEST_METHOD'] === 'GET') ? (int)$_GET['record_id'] : (int)$_POST['record_id'];
    
    if ($record_id <= 0) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid record ID']);
            exit;
        } else {
            die('Invalid record ID');
        }
    }
    
    try {
        // Get record details
        $stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
        $stmt->execute([$record_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'message' => 'Record not found']);
                exit;
            } else {
                die('Record not found');
            }
        }
        
        $template = new TemplateProcessor('Directhireclearance.docx');
        $template->setValue('control_no', $record['control_no']);
        $template->setValue('name', $record['name']);
        $template->setValue('jobsite', $record['jobsite']);
        $template->setValue('type', ucfirst($record['type']));
        $template->setValue('status', ucfirst($record['status']));
        $template->setValue('evaluator', $record['evaluator'] ?? 'Not assigned');
        $template->setValue('evaluated', !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : 'Not set');
        $template->setValue('for_confirmation', !empty($record['for_confirmation']) ? date('F j, Y', strtotime($record['for_confirmation'])) : 'Not set');
        $template->setValue('emailed_to_dhad', !empty($record['emailed_to_dhad']) ? date('F j, Y', strtotime($record['emailed_to_dhad'])) : 'Not set');
        $template->setValue('received_from_dhad', !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : 'Not set');
        $template->setValue('current_date', date('F j, Y'));
        $template->setValue('comments', !empty($record['note']) ? $record['note'] : 'No additional comments.');
        
        $isApproved = ($record['status'] === 'approved');
        if ($isApproved) {
            $signatureFile = 'signatures/Signature.png';
            if (file_exists($signatureFile)) {
                // Clear any existing value
                $template->setValue('signature1_image', '');
                
                // Use absolute path for better compatibility
                $signaturePath = realpath($signatureFile);
                
                try {
                    $template->setImageValue('signature1_image', [
                        'path' => $signaturePath,
                        'width' => 150,
                        'height' => 75,
                        'ratio' => false
                    ]);
                } catch (Exception $e) {
                    // Fallback if image insertion fails
                    $template->setValue('signature1_image', '✓ APPROVED');
                    error_log('Error inserting signature: ' . $e->getMessage());
                }
            } else {
                $template->setValue('signature1_image', '✓ APPROVED');
                error_log('Signature file not found: ' . realpath(dirname(__FILE__)) . '/' . $signatureFile);
            }
        } else {
            $template->setValue('signature1_image', '');
        }
        
        // Generate unique file name for DOCX
        $docName = 'clearance_' . $record['control_no'] . '_' . date('Ymd_His');
        
        // Different handling based on request type
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // For AJAX requests, save to a directory and return URL
            $outputDir = 'uploads/direct_hire_clearance';
            
            // Create directory if it doesn't exist
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0777, true);
            }
            
            $docFilename = $docName . '.docx';
            $docPath = $outputDir . '/' . $docFilename;
            $template->saveAs($docPath);
            
            logDebug("Document saved to: $docPath");
            
            // Return JSON response with document URL that includes the download parameter
            $docUrl = 'generate_direct_hire_clearance.php?download=' . $docFilename;
            echo json_encode([
                'success' => true, 
                'message' => 'Document generated successfully',
                'document_url' => $docUrl
            ]);
            
            logDebug("Redirecting to: $docUrl");
            exit;
            
        } else {
            // For direct GET requests, serve file for download
            $tempDocx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $docName . '.docx';
            $template->saveAs($tempDocx);
            
            // Serve DOCX file directly
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . $docName . '.docx"');
            header('Content-Length: ' . filesize($tempDocx));
            readfile($tempDocx);
            
            // Clean up temporary file
            if (file_exists($tempDocx)) unlink($tempDocx);
            exit;
        }
        
    } catch (Exception $e) {
        $errorMsg = 'Error: ' . $e->getMessage();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        } else {
            die($errorMsg);
        }
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request. Record ID is required.']);
    } else {
        die('Invalid request. Record ID is required.');
    }
}
?>
