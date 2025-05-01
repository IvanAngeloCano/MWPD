<?php
require 'vendor/autoload.php'; // PHPWord via Composer
require_once 'connection.php'; // Your database connection

use PhpOffice\PhpWord\TemplateProcessor;

if (!isset($_GET['bmid'])) {
    die("Missing ID");
}

$bmid = intval($_GET['bmid']);

try {
    // Create the table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `bm_ncc_files` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `bmid` varchar(50) NOT NULL,
        `file_name` varchar(255) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($createTableSQL);
    
    // Check if the columns filename and filepath exist, if not, add them
    $checkColumnSQL = "SHOW COLUMNS FROM bm_ncc_files LIKE 'filename'";
    $stmt = $pdo->query($checkColumnSQL);
    if ($stmt->rowCount() == 0) {
        // Add the filename column
        $pdo->exec("ALTER TABLE bm_ncc_files ADD COLUMN filename VARCHAR(255) AFTER file_name");
        // Update existing records to copy file_name to filename
        $pdo->exec("UPDATE bm_ncc_files SET filename = file_name WHERE filename IS NULL");
    }
    
    $checkColumnSQL = "SHOW COLUMNS FROM bm_ncc_files LIKE 'filepath'";
    $stmt = $pdo->query($checkColumnSQL);
    if ($stmt->rowCount() == 0) {
        // Add the filepath column
        $pdo->exec("ALTER TABLE bm_ncc_files ADD COLUMN filepath VARCHAR(255) AFTER file_path");
        // Update existing records to copy file_path to filepath
        $pdo->exec("UPDATE bm_ncc_files SET filepath = file_path WHERE filepath IS NULL");
    }
    
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
        // Use either filename/filepath or file_name/file_path depending on what exists
        $filename = isset($existingFile['filename']) ? $existingFile['filename'] : 
                   (isset($existingFile['file_name']) ? $existingFile['file_name'] : $data['last_name'] . "_NON_COMPLIANT_COUNTRY.docx");
        
        $savePath = isset($existingFile['filepath']) ? $existingFile['filepath'] : 
                   (isset($existingFile['file_path']) ? $existingFile['file_path'] : __DIR__ . '/generated_files/' . $filename);

        // Save to a temporary file first in the generated_files folder
        $tempFile = $generatedFilesDir . '/temp_' . uniqid() . '.docx';
        $template->saveAs($tempFile);

        // Delete old file
        if (file_exists($savePath) && is_writable($savePath)) {
            unlink($savePath);
        }

        // Move temp file to final file
        rename($tempFile, $savePath);

        // Update both file_path and filepath for compatibility
        $stmtUpdate = $pdo->prepare("UPDATE bm_ncc_files SET file_path = ?, filepath = ? WHERE bmid = ?");
        $stmtUpdate->execute([$savePath, $savePath, $bmid]);

    } else {
        // If no file exists, create a new one in the generated_files folder
        $filename = $data['last_name'] . "_NON_COMPLIANT_COUNTRY_CLEARANCE" . ".docx";
        $savePath = $generatedFilesDir . '/' . $filename;

        $template->saveAs($savePath);

        $stmtInsert = $pdo->prepare("INSERT INTO bm_ncc_files (bmid, file_name, file_path, filename, filepath) VALUES (?, ?, ?, ?, ?)");
        $stmtInsert->execute([$bmid, $filename, $savePath, $filename, $savePath]);
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
