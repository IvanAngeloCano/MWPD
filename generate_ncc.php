<?php
require 'vendor/autoload.php'; // PHPWord via Composer
require_once 'connection.php'; // Your database connection

use PhpOffice\PhpWord\TemplateProcessor;

if (!isset($_GET['bmid'])) {
    die("Missing ID");
}

$bmid = intval($_GET['bmid']);

try {
    $stmt = $pdo->prepare("SELECT * FROM BM WHERE bmid = ?");
    $stmt->execute([$bmid]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("No record found.");
    }

    $template = new TemplateProcessor('NON_COMPLIANT_COUNTRY_CLEARANCE_TEMPLATE.docx');

    $template->setValue('Name of worker', $data['last_name'] . ', ' . $data['given_name'] . ' ' . $data['middle_name']);
    $template->setValue('Position', $data['position']);
    $template->setValue('Salary', $data['salary']);
    $template->setValue('Destination', $data['destination']);
    $template->setValue('Name of the new principal', $data['nameofthenewprincipal']);
    $template->setValue('Employment duration', date('F j, Y', strtotime($data['employmentdurationstart'])) . ' to ' . date('F j, Y', strtotime($data['employmentdurationend'])));
    $template->setValue('datearrival',  date('F j, Y', strtotime($data['datearrival'])));
    $template->setValue('datedeparture',  date('F j, Y', strtotime($data['datedeparture'])));
    $template->setValue('DATE', date('F j, Y'));
    $template->setValue('Control Number', 'NVEC-' . date('Y-m-d') . '-' . str_pad($bmid, 4, '0', STR_PAD_LEFT));
    $template->setValue('Remarks', 'CHANGED EMPLOYER');
    $template->setValue('employmentdurationstart', date('F j, Y', strtotime($data['employmentdurationstart'])));
    $template->setValue('employmentdurationend', date('F j, Y', strtotime($data['employmentdurationend'])));

    // Ensure the folder exists
    $generatedFilesDir = __DIR__ . '/generated_files';
    if (!is_dir($generatedFilesDir)) {
        mkdir($generatedFilesDir, 0777, true);
    }

    // Check if a file already exists for the bmid
    $stmtCheck = $pdo->prepare("SELECT * FROM bm_ncc_files WHERE bmid = ?");
    $stmtCheck->execute([$bmid]);
    $existingFile = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingFile) {
        // If the file exists, overwrite it
        $filename = $existingFile['filename'];
        $savePath = $existingFile['filepath'];

        // Save to a temporary file first in the generated_files folder
        $tempFile = $generatedFilesDir . '/temp_' . uniqid() . '.docx';
        $template->saveAs($tempFile);

        // Delete old file
        if (file_exists($savePath) && is_writable($savePath)) {
            unlink($savePath);
        }

        // Move temp file to final file
        rename($tempFile, $savePath);

        // Update filepath just in case (optional)
        $stmtUpdate = $pdo->prepare("UPDATE bm_ncc_files SET filepath = ? WHERE bmid = ?");
        $stmtUpdate->execute([$savePath, $bmid]);

    } else {
        // If no file exists, create a new one in the generated_files folder
        $filename = $data['last_name'] . "_NON_COMPLIANT_COUNTRY_CLEARANCE" . ".docx";
        $savePath = $generatedFilesDir . '/' . $filename;

        $template->saveAs($savePath);

        $stmtInsert = $pdo->prepare("INSERT INTO bm_ncc_files (bmid, filename, filepath) VALUES (?, ?, ?)");
        $stmtInsert->execute([$bmid, $filename, $savePath]);
    }

    // Convert DOCX to PDF using LibreOffice
    $sofficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe'; // Update the path as needed
    $outputDir = $generatedFilesDir; // Ensure PDF is saved in generated_files folder
    $command = "\"$sofficePath\" --headless --convert-to pdf --outdir \"$outputDir\" \"$savePath\"";

    // Execute the command to convert DOCX to PDF
    exec($command, $output, $return_var);

    if ($return_var === 0) {
        // PDF conversion was successful, get the PDF file path
        $pdfFile = str_replace('.docx', '.pdf', $filename);

        // Send PDF file directly to the browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $pdfFile . '"');
        header('Content-Length: ' . filesize($outputDir . '\\' . $pdfFile));

        // Output the PDF file content
        readfile($outputDir . '\\' . $pdfFile);

        // Clean up files after sending
        register_shutdown_function(function() use ($savePath, $pdfFile) {
            // Wait a moment to ensure the file has been sent
            sleep(1);
            if (file_exists($savePath)) {
                unlink($savePath); // Delete DOCX file
            }
            if (file_exists($pdfFile)) {
                unlink($pdfFile); // Delete PDF file
            }
        });

        exit;
    } else {
        // If conversion failed, provide the DOCX file as a fallback
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($savePath));
        readfile($savePath);

        // Clean up the DOCX file after sending
        register_shutdown_function(function() use ($savePath) {
            if (file_exists($savePath)) {
                unlink($savePath);
            }
        });

        exit;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
