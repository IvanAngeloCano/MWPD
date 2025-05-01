<?php
require 'vendor/autoload.php'; // PHPWord via Composer
require_once 'connection.php'; // Your database connection

use PhpOffice\PhpWord\TemplateProcessor;

if (!isset($_GET['bmid'])) {
    die("Missing ID");
}

$bmid = intval($_GET['bmid']);

try {
    // Check if the table exists
    $tableExists = false;
    $tables = $pdo->query("SHOW TABLES LIKE 'bm_ac_files'")->fetchAll();
    if (count($tables) > 0) {
        $tableExists = true;
    }
    
    // If table doesn't exist, create it with both old and new column names
    if (!$tableExists) {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `bm_ac_files` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `bmid` varchar(50) NOT NULL,
            `filename` varchar(255) NOT NULL,
            `filepath` varchar(255) NOT NULL,
            `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $pdo->exec($createTableSQL);
    } else {
        // Table exists, check for columns
        // Check if filename column exists
        $filenameExists = false;
        $checkColumn = $pdo->query("SHOW COLUMNS FROM bm_ac_files LIKE 'filename'")->fetchAll();
        if (count($checkColumn) > 0) {
            $filenameExists = true;
        }
        
        // Check if filepath column exists
        $filepathExists = false;
        $checkColumn = $pdo->query("SHOW COLUMNS FROM bm_ac_files LIKE 'filepath'")->fetchAll();
        if (count($checkColumn) > 0) {
            $filepathExists = true;
        }
        
        // Add missing columns if needed
        if (!$filenameExists) {
            $pdo->exec("ALTER TABLE bm_ac_files ADD COLUMN filename VARCHAR(255)");
        }
        
        if (!$filepathExists) {
            $pdo->exec("ALTER TABLE bm_ac_files ADD COLUMN filepath VARCHAR(255)");
        }
    }
    
    $stmt = $pdo->prepare("SELECT * FROM BM WHERE bmid = ?");
    $stmt->execute([$bmid]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        die("No record found.");
    }

    $template = new TemplateProcessor('FOR_ASSESMENTCOUNTRY_TEMPLATE.docx');

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
    $stmtCheck = $pdo->prepare("SELECT * FROM bm_ac_files WHERE bmid = ?");
    $stmtCheck->execute([$bmid]);
    $existingFile = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingFile) {
        // If the file exists, overwrite it
        // Use either filename/filepath or file_name/file_path depending on what exists
        $filename = isset($existingFile['filename']) ? $existingFile['filename'] : 
                   (isset($existingFile['file_name']) ? $existingFile['file_name'] : $data['last_name'] . "_FOR_ASSESSMENT_COUNTRY.docx");
        
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

        // Update only filepath for compatibility
        $stmtUpdate = $pdo->prepare("UPDATE bm_ac_files SET filepath = ? WHERE bmid = ?");
        $stmtUpdate->execute([$savePath, $bmid]);

    } else {
        // If no file exists, create a new one in the generated_files folder
        $filename = $data['last_name'] . "_FOR_ASSESSMENT_COUNTRY" . ".docx";
        $savePath = $generatedFilesDir . '/' . $filename;

        $template->saveAs($savePath);

        $stmtInsert = $pdo->prepare("INSERT INTO bm_ac_files (bmid, filename, filepath) VALUES (?, ?, ?)");
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
