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

    $template = new TemplateProcessor('CRITICAL_SKILLS_TEMPLATE.docx');

    $template->setValue('Name of worker', $data['last_name'] . ', ' . $data['given_name'] . ' ' . $data['middle_name']);
    $template->setValue('Position', $data['position']);
    $template->setValue('Salary', $data['salary']);
    $template->setValue('Destination', $data['destination']);
    $template->setValue('Name of the new principal', $data['nameofthenewprincipal']);
    //$template->setValue('Employment duration', $data['employmentduration'])
    $template->setValue('Employment duration', date('F j, Y', strtotime($data['employmentdurationstart'])) . ' to ' . date('F j, Y', strtotime($data['employmentdurationend'])));
    $template->setValue('datearrival',  date('F j, Y', strtotime($data['datearrival'])));
    $template->setValue('datedeparture',  date('F j, Y', strtotime($data['datedeparture'])));
    $template->setValue('DATE', date('F j, Y'));
    $template->setValue('Control Number', 'NVEC-' . date('Y-m-d') . '-' . str_pad($bmid, 4, '0', STR_PAD_LEFT));
    $template->setValue('Remarks', 'CHANGED EMPLOYER');
    $template->setValue('employmentdurationstart', date('F j, Y', strtotime($data['employmentdurationstart'])));
    $template->setValue('employmentdurationend', date('F j, Y', strtotime($data['employmentdurationend'])));

    // Make sure the folder exists
    if (!is_dir(__DIR__ . '/generated_files')) {
        mkdir(__DIR__ . '/generated_files', 0777, true);
    }

    // Check if a file already exists for the bmid
    $stmtCheck = $pdo->prepare("SELECT * FROM bm_cs_files WHERE bmid = ?");
    $stmtCheck->execute([$bmid]);
    $existingFile = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existingFile) {
        // If the file exists, overwrite it
        $filename = $existingFile['filename'];
        $savePath = $existingFile['filepath'];

        // Save to a temporary file first
        $tempFile = __DIR__ . '/generated_files/temp_' . uniqid() . '.docx';
        $template->saveAs($tempFile);

        // Delete old file
        if (file_exists($savePath) && is_writable($savePath)) {
            unlink($savePath);
        }

        // Move temp file to final file
        rename($tempFile, $savePath);

        // Update filepath just in case (optional)
        $stmtUpdate = $pdo->prepare("UPDATE bm_cs_files SET filepath = ? WHERE bmid = ?");
        $stmtUpdate->execute([$savePath, $bmid]);

    } else {
        // If no file exists, create a new one
        $filename = $data['last_name'] ."_CRITICAL_SKILLS" .  ".docx";
        $savePath = __DIR__ . '/generated_files/' . $filename;

        $template->saveAs($savePath);

        $stmtInsert = $pdo->prepare("INSERT INTO bm_cs_files (bmid, filename, filepath) VALUES (?, ?, ?)");
        $stmtInsert->execute([$bmid, $filename, $savePath]);
    }

    // Open the file in a new tab
    $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath($savePath));
    $relativePath = str_replace('\\', '/', $relativePath); // fix slashes on Windows

    echo "<script>window.open('$relativePath', '_blank');</script>";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
