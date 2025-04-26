<?php
require_once 'vendor/autoload.php'; 
require_once 'connection.php';

use PhpOffice\PhpWord\TemplateProcessor;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employer = $_POST['employer'];
    $memoDate = $_POST['memo_date'];

    // Load the Word template
    $template = new TemplateProcessor('GOVERNMENT_TO_GOVERNMENT_MEMORANDUM_TEMPLATE.docx');

    // Fill in the memo header placeholders
    $template->setValue('employer', htmlspecialchars($employer));
    $template->setValue('date', htmlspecialchars($memoDate));

    // Fetch only applicants with remark = 'good' (case-insensitive)
    $stmt = $pdo->prepare("SELECT last_name, first_name, middle_name, passport_number FROM gov_to_gov WHERE LOWER(remarks) = :remark");
    $stmt->execute(['remark' => 'good']);
    $applicants = $stmt->fetchAll();
    $count = count($applicants);

    if ($count === 0) {
        die("No applicants with a 'good' remark found.");
    }

    // Clone template rows
    $template->cloneRow('no', $count);

    foreach ($applicants as $index => $applicant) {
        $row = $index + 1;
        $template->setValue("no#$row", $row);
        $template->setValue("last_name#$row", $applicant['last_name']);
        $template->setValue("first_name#$row", $applicant['first_name']);
        $template->setValue("middle_name#$row", $applicant['middle_name']);
        $template->setValue("passport_number#$row", $applicant['passport_number']);
    }

    // Save DOCX file
    $docxFile = 'Memo_' . date('Ymd_His') . '.docx';
    $template->saveAs($docxFile);

    // Full path to soffice.exe (this is your LibreOffice executable)
    $sofficePath = 'C:\\Program Files\\LibreOffice\\program\\soffice.exe';  // Update the path as needed

    // Command to convert DOCX to PDF using LibreOffice
    $outputDir = dirname(__FILE__);  // Current directory where the PHP script is
    $command = "\"$sofficePath\" --headless --convert-to pdf --outdir \"$outputDir\" \"$docxFile\"";

    // Execute the command to convert DOCX to PDF
    exec($command, $output, $return_var);

    // Check if the conversion was successful
    if ($return_var === 0 && file_exists("$outputDir\\$docxFile")) {
        // Convert DOCX to PDF file
        $pdfFile = str_replace('.docx', '.pdf', $docxFile);

        // Send PDF file directly to the browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $pdfFile . '"');
        header('Content-Length: ' . filesize("$outputDir\\$pdfFile"));

        // Output the PDF file content
        readfile("$outputDir\\$pdfFile");

        // Optionally delete the DOCX and PDF file after serving
        unlink("$outputDir\\$docxFile");
        unlink("$outputDir\\$pdfFile");

        exit;
    } else {
        // If conversion fails
        die("Failed to convert DOCX to PDF. Error: " . implode("\n", $output));
    }
}
?>
