<?php
include 'session.php';
include 'connection.php';
require_once 'vendor/autoload.php';  // Include PHPWord

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

// Fetch the necessary data
// Total request count
$sql = "SELECT COUNT(*) as total_rows FROM info_sheet";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$totalRows = $stmt->fetch()['total_rows'];

// Gender data
$sql = "SELECT LOWER(TRIM(gender)) AS gender, COUNT(*) as count 
        FROM info_sheet 
        WHERE gender IS NOT NULL 
        GROUP BY gender";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$genders = [];
while ($row = $stmt->fetch()) {
    $genders[$row['gender']] = $row['count'];
}

// PCT data (highest and lowest)
$lowestStmt = $pdo->query("SELECT total_pct FROM info_sheet WHERE total_pct IS NOT NULL ORDER BY TIME_TO_SEC(total_pct) ASC LIMIT 1");
$highestStmt = $pdo->query("SELECT total_pct FROM info_sheet WHERE total_pct IS NOT NULL ORDER BY TIME_TO_SEC(total_pct) DESC LIMIT 1");

$lowestPct = $lowestStmt->fetch()['total_pct'];
$highestPct = $highestStmt->fetch()['total_pct'];

// Purpose and Work Category data
$purposeStmt = $pdo->query("SELECT TRIM(purpose) AS purpose, COUNT(*) AS count FROM info_sheet WHERE purpose IS NOT NULL GROUP BY purpose");
$workCategoryStmt = $pdo->query("SELECT worker_category, COUNT(*) AS count FROM info_sheet WHERE worker_category IS NOT NULL GROUP BY worker_category");

$purposeData = [];
while ($row = $purposeStmt->fetch()) {
    $purposeData[$row['purpose']] = $row['count'];
}

$workCategoryData = [];
while ($row = $workCategoryStmt->fetch()) {
    $workCategoryData[$row['worker_category']] = $row['count'];
}

// Create a new PHPWord object
$phpWord = new PhpWord();

// Add title
$section = $phpWord->addSection();
$section->addText("SUMMARY OF RECORDS", array('name' => 'Arial', 'size' => 16, 'bold' => true));

// Add date and month
$section->addText("FOR THE MONTH OF ___");
$section->addText("01-Sep");

// Add table for total request
$section->addText("TOTAL REQUEST");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("TOTAL REQUEST");
$table->addCell(2000)->addText("A3");
$table->addCell(2000)->addText("N/A");

// Add Gender table
$section->addText("\nGENDER DISTRIBUTION");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("GENDER");
$table->addCell(2000)->addText("COUNT");

foreach ($genders as $gender => $count) {
    $table->addRow();
    $table->addCell(2000)->addText(strtoupper($gender));
    $table->addCell(2000)->addText($count);
}

// Add PCT data
$section->addText("\nHIGHEST PCT");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("H6");
$table->addCell(2000)->addText($highestPct ?? 'N/A');

$section->addText("LOWEST PCT");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("H7");
$table->addCell(2000)->addText($lowestPct ?? 'N/A');

// Add Purpose table
$section->addText("\nPURPOSE");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("PURPOSE");
$table->addCell(2000)->addText("COUNT");

foreach ($purposeData as $purpose => $count) {
    $table->addRow();
    $table->addCell(2000)->addText(strtoupper($purpose));
    $table->addCell(2000)->addText($count);
}

// Add Work Category table
$section->addText("\nWORKER CATEGORY");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("WORK CATEGORY");
$table->addCell(2000)->addText("COUNT");

foreach ($workCategoryData as $category => $count) {
    $table->addRow();
    $table->addCell(2000)->addText(strtoupper($category));
    $table->addCell(2000)->addText($count);
}

// Add Requested Records table (similar structure to Work Category)
$section->addText("\nREQUESTED RECORDS");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("REQUESTED RECORD");
$table->addCell(2000)->addText("COUNT");

foreach ($workCategoryData as $category => $count) {
    $table->addRow();
    $table->addCell(2000)->addText(strtoupper($category));
    $table->addCell(2000)->addText($count);
}

// Add Printed/Retrieved table
$section->addText("\nPRINTED/RETRIEVED");
$table = $section->addTable();
$table->addRow();
$table->addCell(2000)->addText("J6");
$table->addCell(2000)->addText("N/A");

// Save as .docx file
$filename = 'record_summary.docx';
$phpWord->save($filename, 'Word2007');

// Serve the file for download
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
readfile($filename);
unlink($filename); // Delete the file after download
exit;
?>
