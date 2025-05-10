<?php
require_once 'vendor/autoload.php'; 
require_once 'connection.php';

use PhpOffice\PhpWord\TemplateProcessor;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employer = $_POST['employer'];
    $memoDate = $_POST['memo_date'];

    // Get selected IDs from checkboxes
    $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    if (empty($selected_ids)) {
        header('HTTP/1.1 400 Bad Request');
        echo "No applicants selected for memo generation.";
        exit;
    }
    
    // Debug output to see what's being received
    error_log("Selected IDs: " . print_r($selected_ids, true));
    
    // Prepare placeholders for PDO
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

    // Determine the source table based on the referring page
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $isGovToGov = strpos($referer, 'gov_to_gov.php') !== false;
    
    // Add a more reliable way to determine the source
    if (isset($_POST['source']) && $_POST['source'] === 'gov_to_gov') {
        $isGovToGov = true;
    } elseif (isset($_POST['source']) && $_POST['source'] === 'direct_hire') {
        $isGovToGov = false;
    }
    
    // Debug output
    error_log("Source determination: Referer = $referer, isGovToGov = " . ($isGovToGov ? 'true' : 'false'));
    
    // Select the appropriate template and query based on the source
    if ($isGovToGov) {
        // Gov to Gov memo
        $template = new TemplateProcessor('GOVERNMENT_TO_GOVERNMENT_MEMORANDUM_TEMPLATE.docx');
        
        // Fill in the memo header placeholders
        $template->setValue('employer', htmlspecialchars($employer));
        $template->setValue('date', date('F j, Y', strtotime($memoDate)));

        // Fetch only applicants with selected IDs
        $stmt = $pdo->prepare("SELECT g2g, last_name, first_name, middle_name, passport_number FROM gov_to_gov WHERE g2g IN ($placeholders)");
        $stmt->execute($selected_ids);
        $applicants = $stmt->fetchAll();
    } else {
        // Direct hire memo (original code)
        $template = new TemplateProcessor('DIRECT_HIRE_MEMORANDUM_TEMPLATE.docx'); // Update template name
        
        // Fill in the memo header placeholders
        $template->setValue('employer', htmlspecialchars($employer));
        $template->setValue('date', date('F j, Y', strtotime($memoDate)));

        // Fetch only applicants with selected IDs - using only columns that exist in the direct_hire table
        $stmt = $pdo->prepare("SELECT id, name, control_no FROM direct_hire WHERE id IN ($placeholders)");
        $stmt->execute($selected_ids);
        $applicants = $stmt->fetchAll();
    }
    
    $count = count($applicants);

    if ($count === 0) {
        header('HTTP/1.1 400 Bad Request');
        echo "No applicants found for the selected IDs.";
        exit;
    }

    // Clone template rows
    $template->cloneRow('no', $count);

    foreach ($applicants as $index => $applicant) {
        $row = $index + 1;
        $template->setValue("no#$row", $row);
        
        if ($isGovToGov) {
            // Gov to Gov applicant fields
            $template->setValue("last_name#$row", $applicant['last_name']);
            $template->setValue("first_name#$row", $applicant['first_name']);
            $template->setValue("middle_name#$row", $applicant['middle_name']);
            $template->setValue("passport_number#$row", $applicant['passport_number']);
        } else {
            // Direct hire applicant fields - use the full name field
            // Split the name field if possible
            $nameParts = explode(' ', $applicant['name']);
            $lastName = end($nameParts); // Assume last word is last name
            $firstName = $nameParts[0]; // Assume first word is first name
            $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : ''; // Everything in between
            
            $template->setValue("last_name#$row", $lastName);
            $template->setValue("first_name#$row", $firstName);
            $template->setValue("middle_name#$row", $middleName);
            $template->setValue("passport_number#$row", ''); // Direct hire doesn't have passport number
            $template->setValue("control_no#$row", $applicant['control_no'] ?? '');
        }
    }

    // Generate a unique filename with timestamp
    $timestamp = date('YmdHis');
    $docxFile = 'Memo_' . $timestamp . '.docx';
    $template->saveAs($docxFile);

    // Full path to soffice.exe (this is your LibreOffice executable)
    $sofficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';  // Update the path as needed

    // Command to convert DOCX to PDF using LibreOffice
    $outputDir = dirname(__FILE__);  // Current directory where the PHP script is
    $command = "\"$sofficePath\" --headless --convert-to pdf --outdir \"$outputDir\" \"$docxFile\"";

    // Execute the command to convert DOCX to PDF
    exec($command, $output, $return_var);

    // Check if the conversion was successful
    if ($return_var === 0) {
        // Convert DOCX to PDF file
        $pdfFile = str_replace('.docx', '.pdf', $docxFile);
        
        if (file_exists("$outputDir\\$pdfFile")) {
            // Send PDF file directly to the browser
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $pdfFile . '"');
            header('Content-Length: ' . filesize("$outputDir\\$pdfFile"));
            
            // Output the PDF file content
            readfile("$outputDir\\$pdfFile");
            
            // Clean up files after sending
            // We'll use a delayed cleanup approach to ensure the file is fully sent
            register_shutdown_function(function() use ($docxFile, $pdfFile) {
                // Wait a moment to ensure the file has been sent
                sleep(1);
                if (file_exists($docxFile)) {
                    unlink($docxFile);
                }
                if (file_exists($pdfFile)) {
                    unlink($pdfFile);
                }
            });
            
            exit;
        } else {
            header('HTTP/1.1 500 Internal Server Error');
            echo "PDF file not found after conversion.";
            
            // Clean up the DOCX file
            if (file_exists($docxFile)) {
                unlink($docxFile);
            }
            
            exit;
        }
    } else {
        // If conversion failed, provide the DOCX file as a fallback
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $docxFile . '"');
        header('Content-Length: ' . filesize("$outputDir\\$docxFile"));
        readfile("$outputDir\\$docxFile");
        
        // Clean up the DOCX file after sending
        register_shutdown_function(function() use ($docxFile) {
            if (file_exists($docxFile)) {
                unlink($docxFile);
            }
        });
        
        // Log the error
        error_log("PDF conversion failed. Command: $command, Return var: $return_var, Output: " . print_r($output, true));
        
        exit;
    }
} else {
    // If accessed directly without POST data
    header('HTTP/1.1 400 Bad Request');
    echo "This page should be accessed via POST request.";
    exit;
}
?>
