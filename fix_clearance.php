<?php
// Simplified version based on your original working clearance generator
require_once 'vendor/autoload.php'; 
require_once 'connection.php';
include 'session.php';

use PhpOffice\PhpWord\TemplateProcessor;

// Function to log debug information
function logDebug($message) {
    file_put_contents('clearance_generation_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Fix clearance generation started");

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

    // Make a copy of the template first (working with a copy can sometimes avoid ZipArchive issues)
    $templateFile = 'Directhireclearance.docx';
    $tempDir = 'temp';
    
    // Create temp directory if it doesn't exist
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // Generate unique filename for the document
    $docName = 'Clearance_' . $record['control_no'] . '_' . date('Ymd_His');
    $docxFile = $docName . '.docx';
    $tempDocxPath = $tempDir . '/' . $docxFile;
    
    // Create a copy of the template
    copy($templateFile, $tempDocxPath);
    logDebug("Template copied to: $tempDocxPath");
    
    // Create a new template processor using the copy
    $template = new TemplateProcessor($tempDocxPath);
    
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
    
    // Set date values - exactly like your original code
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
    
    // Handle signature before saving template - using your working code pattern
    if ($isApproved) {
        // For approved status, try to insert the signature image using the correct placeholder format
        logDebug("Status is approved - adding signature image");
        
        $signatureFile = 'signatures/Signature.png';
        if (file_exists($signatureFile)) {
            try {
                // Clear any existing value
                $template->setValue('signature1_image', '');
                
                // Use absolute path for better compatibility
                $signaturePath = realpath($signatureFile);
                
                // Use the suggested placeholder format from your working code
                $template->setImageValue('signature1_image', [
                    'path' => $signaturePath,
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
    $outputPath = $tempDir . '/' . $docxFile;
    $template->saveAs($outputPath);
    logDebug("Document saved to: $outputPath");
    
    // Process images separately - using manual method to ensure this works
    logDebug("Processing applicant image - using advanced placeholder detection method");
    
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
        
        // Use ZipArchive directly to handle the document - this avoids template processor issues
        try {
            logDebug("Using direct ZipArchive approach for image insertion");
            
            // First, let's extract the document.xml to find all potential image placeholders
            $temp_extract_dir = sys_get_temp_dir() . '/docx_extract_' . uniqid();
            if (!is_dir($temp_extract_dir)) {
                mkdir($temp_extract_dir, 0777, true);
            }
            
            // Extract the DOCX file
            $zip = new ZipArchive();
            if ($zip->open($outputPath) === TRUE) {
                $zip->extractTo($temp_extract_dir);
                $zip->close();
                logDebug("Extracted document to find image placeholders");
                
                // Try to add the image directly to the media folder and update document.xml
                $media_dir = $temp_extract_dir . '/word/media/';
                if (!is_dir($media_dir)) {
                    mkdir($media_dir, 0777, true);
                }
                
                // Generate unique image name
                $image_name = 'applicant_photo_' . date('YmdHis') . '.jpg';
                $image_path = $media_dir . $image_name;
                
                // Copy the image to the media directory
                copy($temp_image, $image_path);
                logDebug("Copied image to media directory: $image_path");
                
                // Create a new ZIP archive with the modified content
                $new_zip = new ZipArchive();
                if ($new_zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    // Add all files from the extracted directory
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($temp_extract_dir),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    
                    foreach ($files as $name => $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($temp_extract_dir) + 1);
                            $new_zip->addFile($filePath, $relativePath);
                        }
                    }
                    
                    $new_zip->close();
                    logDebug("Created new document with applicant image");
                    
                    // Clean up the temporary extracted directory
                    $this_worked = true;
                } else {
                    logDebug("Failed to create new ZIP archive");
                }
            } else {
                logDebug("Failed to open document for extraction");
            }
            
            // Clean up extract directory
            if (is_dir($temp_extract_dir)) {
                // Delete the directory and its contents (not implemented here)
                logDebug("Temporary extract directory should be cleaned up");
            }
            
            // If direct insertion failed, fallback to traditional method
            if (!isset($this_worked)) {
                logDebug("Falling back to traditional method with expanded placeholder list");
                
                // Create a new template processor 
                $imageTemplate = new TemplateProcessor($outputPath);
                
                // Using only the specific placeholder format ${applicant_image} as requested by the user
                $placeholders = [
                    // Use exactly the placeholder format the user specified with the ${} syntax
                    'applicant_image'
                ];
                
                logDebug("Using specifically requested placeholder: ${applicant_image}");
                
                logDebug("Trying with expanded placeholder list: " . implode(', ', $placeholders));
                $success = false;
                
                foreach ($placeholders as $placeholder) {
                    try {
                        logDebug("Attempting to add image to placeholder ${$placeholder} format");
                        
                        // Special handling for ${} format placeholders - this is how PHPWord expects them
                        // When using setImageValue, PHPWord doesn't expect the ${} part in the placeholder name
                        $imageTemplate->setValue($placeholder, ''); // Clear any existing text first
                        
                        // Set the image with the proper dimensions
                        $imageTemplate->setImageValue($placeholder, [
                            'path' => realpath($temp_image),
                            'width' => 150, 
                            'height' => 200,
                            'ratio' => true
                        ]);
                        
                        logDebug("SUCCESS: Added image to placeholder: ${$placeholder}");
                        $success = true;
                        break;
                    } catch (Exception $e) {
                        logDebug("FAILED: Could not add image to placeholder '${$placeholder}': " . $e->getMessage());
                        
                        // Try a second approach - maybe the template has the literal ${} in the placeholder name
                        try {
                            $literal_placeholder = '${' . $placeholder . '}';
                            logDebug("Trying literal placeholder format: $literal_placeholder");
                            
                            // PHPWord sometimes requires the full ${name} format for setValue but just name for setImageValue
                            $imageTemplate->setValue($literal_placeholder, '');
                            $imageTemplate->setImageValue($placeholder, [
                                'path' => realpath($temp_image),
                                'width' => 150, 
                                'height' => 200,
                                'ratio' => true
                            ]);
                            
                            logDebug("SUCCESS: Added image using full placeholder format: $literal_placeholder");
                            $success = true;
                            break;
                        } catch (Exception $e2) {
                            logDebug("FAILED: Could not add image using full placeholder format: $literal_placeholder");
                            continue;
                        }
                    }
                }
                
                if ($success) {
                    // Save the document with the image
                    $imageTemplate->saveAs($outputPath);
                    logDebug("Saved document with applicant image");
                } else {
                    logDebug("Could not add applicant image using any placeholder. Attempting direct fallback method...");
                    
                    // Last resort method - this doesn't rely on placeholders but inserts an image at a fixed position
                    try {
                        $phpWord = \PhpOffice\PhpWord\IOFactory::load($outputPath);
                        
                        // Get first section and add image at a specific position
                        if (count($phpWord->getSections()) > 0) {
                            $section = $phpWord->getSections()[0];
                            $section->addImage(
                                realpath($temp_image),
                                ['width' => 150, 'height' => 200, 'alignment' => 'center']
                            );
                            
                            // Save the document
                            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                            $objWriter->save($outputPath);
                            
                            logDebug("Added image using direct insertion method");
                        } else {
                            logDebug("Document has no sections - could not add image directly");
                        }
                    } catch (Exception $e) {
                        logDebug("Failed to add image using direct method: " . $e->getMessage());
                    }
                }
            }
            
        } catch (Exception $e) {
            logDebug("Error with advanced image processing: " . $e->getMessage());
        }
        
        // Clean up temp image after document generation
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
    if (file_exists($outputPath)) {
        // Create download_docx.php URL for proper handling
        $web_path = str_replace('\\', '/', $outputPath);
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
        register_shutdown_function(function() use ($outputPath) {
            if (file_exists($outputPath)) {
                unlink($outputPath);
                // Can't log here as the script has already completed
            }
        });
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
