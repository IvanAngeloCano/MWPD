<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Database connection using PDO
$host = 'localhost';
$db   = 'MWPD';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$month = isset($_GET['month']) ? (int) $_GET['month'] : date('n'); // default to current month
$year = isset($_GET['year']) ? (int) $_GET['year'] : date('Y');     // default to current year


$startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$endDate = date("Y-m-t", strtotime($startDate));

// Fetch records from database
$query = "
    SELECT 
        DATE(ofw_record_released) AS release_date,
        gender,
        purpose,
        worker_category,
        requested_record,
        number_of_records_retrieved_printed
    FROM info_sheet
    WHERE ofw_record_released BETWEEN :startDate AND :endDate
";
$stmt = $pdo->prepare($query);
$stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$data = $stmt->fetchAll();

// Group data by release date
$grouped = [];
foreach ($data as $row) {
    $date = $row['release_date'];
    if (!isset($grouped[$date])) {
        $grouped[$date] = [];
    }
    $grouped[$date][] = $row;
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title
$sheet->setCellValue('A1', 'SUMMARY OF RECORDS');
$sheet->setCellValue('A2', 'FOR THE MONTH OF ' . date('F Y', strtotime($startDate)));

// Generate header row with days
$daysInMonth = date('t', strtotime($startDate));
$col = 'B';
for ($day = 1; $day <= $daysInMonth; $day++) {
    $formatted = str_pad($day, 2, '0', STR_PAD_LEFT) . '-' . date('M', strtotime($startDate));
    $sheet->setCellValue($col . '2', $formatted);
    $col++;
}

// 🧠 Function to write data rows
function writeCategoryRow($sheet, $grouped, $rowLabel, $rowNumber, $columnKey, $filterValue = null, $year, $month) {
    $sheet->setCellValue('A' . $rowNumber, $rowLabel);
    $col = 'B';
    $daysInMonth = date('t', strtotime("$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01"));

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        $count = 0;

        if (isset($grouped[$dateStr])) {
            foreach ($grouped[$dateStr] as $entry) {
                if ($filterValue === null || ($columnKey !== null && $entry[$columnKey] == $filterValue)) {
                    $count++;
                }
            }
        }

        $sheet->setCellValue($col . $rowNumber, $count); 
        $col++;
    }
}

// ✅ Write rows
$row = 3;
writeCategoryRow($sheet, $grouped, "TOTAL REQUEST", $row++, null, null, $year, $month);
$row += 1; // Empty row
writeCategoryRow($sheet, $grouped, "MALE", $row++, "gender", "MALE", $year, $month);
writeCategoryRow($sheet, $grouped, "FEMALE", $row++, "gender", "FEMALE", $year, $month);
$row += 1; // Empty row

$sheet->setCellValue('A' . $row, 'PURPOSE');
$row++;
$purposes = ['EMPLOYMENT','OWWA','LEGAL','LOAN','VISA','BM','RTT','PHILHEALTH','OTHERS'];
foreach ($purposes as $purpose) {
    writeCategoryRow($sheet, $grouped, $purpose, $row++, "purpose", $purpose, $year, $month);
}
$row += 1; // Empty row

$sheet->setCellValue('A' . $row, 'WORKER CATEGORY');
$row++;
$categories = ['LAND BASED', 'REHIRE', 'SEAFARER'];
foreach ($categories as $cat) {
    writeCategoryRow($sheet, $grouped, $cat, $row++, "worker_category", $cat, $year, $month);
}
$row += 1; // Empty row
$sheet->setCellValue('A' . $row, 'REQUESTED RECORDS');
$row++;
$records = ['INFO SHEET', 'OEC', 'CONTRACT'];
foreach ($records as $rec) {
    writeCategoryRow($sheet, $grouped, $rec, $row++, "requested_record", $rec, $year, $month);
}
$row += 1; // Empty row

// 📦 Printed/Retrieved total
$sheet->setCellValue('A' . $row, "PRINTED/RETRIEVED");
$col = 'B';
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateStr = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
    $sum = 0;

    if (isset($grouped[$dateStr])) {
        foreach ($grouped[$dateStr] as $entry) {
            $sum += (int) ($entry['number_of_records_retrieved_printed'] ?? 0);
        }
    }

    $sheet->setCellValue($col . $row, $sum); // Use 0 instead of "N/A"
    $col++;
}

// Save Excel file to specified directory
$savePath = "C:/Xampp/htdocs/MWPD/generated_files"; // ✅ Use forward slashes for Windows compatibility
$filename = "monthly_summary_{$year}_{$month}.xlsx";
$fullPath = $savePath . "/" . $filename;

$writer = new Xlsx($spreadsheet);
$writer->save($fullPath);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add success message to session
$_SESSION['excel_success'] = true;
$_SESSION['excel_filename'] = $filename;
$_SESSION['excel_path'] = $fullPath;

// Redirect back to information_sheet.php
header('Location: information_sheet.php?excel_generated=1');
exit;
?>
