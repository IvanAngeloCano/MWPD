<?php
// Using the original template approach with fixed image handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'session.php';
require_once 'connection.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Function to log debugging information
function logDebug($message) {
    file_put_contents('clearance_generation_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Template-based clearance generation script started");

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
    $template->setValue('control_no', $record['control_no'] ?? '');
    $template->setValue('name', $record['name'] ?? '');
    $template->setValue('jobsite', $record['jobsite'] ?? '');
    $template->setValue('type', ucfirst($record['type'] ?? ''));
    $template->setValue('status', ucfirst($record['status'] ?? ''));
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
        
        $template->setValue("approved_by", $approver_name);
        $template->setValue("approved_date", !empty($record['approved_at']) ? date('F j, Y', strtotime($record['approved_at'])) : date('F j, Y'));
    } else {
        $template->setValue("approved_by", 'Regional Director');
        $template->setValue("approved_date", date('F j, Y'));
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

    // We want to directly serve the DOCX file without converting to PDF
    logDebug("Serving DOCX file directly without PDF conversion");
    
    // Now handle the applicant image insertion
    logDebug("Processing applicant image");
    
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
        
        // Check if ZipArchive is available for template manipulation
        if (!class_exists('ZipArchive')) {
            logDebug("ZipArchive class is not available. Using fallback approach for image insertion");
            
            // Create a new PHPWord document that copies the template data and adds the image
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();
            
            // Add basic template data in a nice format
            $section->addText("DIRECT HIRE CLEARANCE DOCUMENT", ['bold' => true, 'size' => 16], ['alignment' => 'center']);
            $section->addTextBreak(1);
            
            // Create a table for basic info
            $table = $section->addTable(['borderSize' => 1, 'borderColor' => '000000']);
            
            // Helper function to add a row to the table
            function addTableRow($table, $label, $value) {
                $row = $table->addRow();
                $row->addCell(2000)->addText($label, ['bold' => true]);
                $row->addCell(6000)->addText($value);
            }
            
            // Add the same data from template
            addTableRow($table, 'Control Number:', $record['control_no'] ?? '');
            addTableRow($table, 'Name:', $record['name'] ?? '');
            addTableRow($table, 'Jobsite:', $record['jobsite'] ?? '');
            addTableRow($table, 'Type:', ucfirst($record['type'] ?? ''));
            addTableRow($table, 'Status:', ucfirst($record['status'] ?? ''));
            addTableRow($table, 'Evaluator:', $record['evaluator'] ?? 'Not assigned');
            
            // Add dates
            $section->addTextBreak(1);
            $section->addText('Important Dates:', ['bold' => true]);
            $dateTable = $section->addTable(['borderSize' => 1, 'borderColor' => '000000']);
            addTableRow($dateTable, 'Evaluated:', $evaluated_formatted);
            addTableRow($dateTable, 'For Confirmation:', $for_confirmation_formatted);
            addTableRow($dateTable, 'Emailed to DHAD:', $emailed_to_dhad_formatted);
            addTableRow($dateTable, 'Received from DHAD:', $received_from_dhad_formatted);
            
            // Add comments
            $section->addTextBreak(1);
            $section->addText('Comments:', ['bold' => true]);
            $section->addText(!empty($record['note']) ? $record['note'] : 'No additional comments.');
            
            // Add approval info if applicable
            $section->addTextBreak(1);
            $section->addText('Approval:', ['bold' => true]);
            
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
                
                // Add signature if available
                $signatureFile = 'signatures/Signature.png';
                if (file_exists($signatureFile)) {
                    $section->addImage($signatureFile, ['width' => 150, 'height' => 75]);
                    logDebug("Added signature image");
                }
            } else {
                $section->addText('□ PENDING APPROVAL', ['bold' => true, 'color' => 'AA0000']);
            }
            
            // Add the applicant image
            $section->addTextBreak(1);
            $section->addText('Applicant Photo:', ['bold' => true]);
            $section->addImage($temp_image, ['width' => 200, 'height' => 200]);
            logDebug("Added applicant photo using direct PHPWord approach");
            
            // Save the new document
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempDocxPath);
            logDebug("Saved document with direct PHPWord approach");
        } else {
            // ZipArchive is available, continue with template approach
            // Create a new template processor for the saved file
            $template = new TemplateProcessor($tempDocxPath);
            
            // Define known image placeholders to try
            $placeholders = [
                'image',
                'applicant_photo',
                'photo',
                'applicant_image',
                'person_photo'
            ];
            
            $success = false;
            
            foreach ($placeholders as $placeholder) {
                try {
                    // First try with exactly the same format that works for signatures
                    $template->setImageValue($placeholder, [
                        'path' => realpath($temp_image),
                        'width' => 150, 
                        'height' => 200,
                        'ratio' => true
                    ]);
                    
                    logDebug("Successfully added image to placeholder: $placeholder");
                    $success = true;
                    break;
                } catch (Exception $e) {
                    // If this approach fails, try simpler method
                    try {
                        $template->setImageValue($placeholder, $temp_image);
                        logDebug("Successfully added image to placeholder: $placeholder (simple method)");
                        $success = true;
                        break;
                    } catch (Exception $innerEx) {
                        logDebug("Failed to add image to placeholder '$placeholder': " . $innerEx->getMessage());
                    }
                }
            }
            
            if ($success) {
                // Save the document with the image
                $template->saveAs($tempDocxPath);
                logDebug("Saved document with applicant image using template approach");
            } else {
                logDebug("Could not add applicant image to any placeholder. Make sure your template has one of these placeholders: " . implode(', ', $placeholders));
            }
        }
        
        if ($success) {
            // Save the document with the image
            $template->saveAs($tempDocxPath);
            logDebug("Saved document with applicant image");
        } else {
            logDebug("Could not add applicant image to any placeholder. Make sure your template has one of these placeholders: " . implode(', ', $placeholders));
        }
        
        // Clean up temp image
        register_shutdown_function(function() use ($temp_image) {
            if (file_exists($temp_image)) {
                unlink($temp_image);
                logDebug("Removed temporary image file");
            }
        });
    } else {
        logDebug("No image found for this record");
    }

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
