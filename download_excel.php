<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the filename from the query parameter
$filename = isset($_GET['file']) ? $_GET['file'] : '';

// Security check - Make sure the filename is valid and exists
if (empty($filename) || strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    die('Invalid filename');
}

// Define the path where Excel files are stored
$savePath = __DIR__ . '/generated_files';

// Full path to the file
$filePath = $savePath . '/' . $filename;

// Check if the file exists
if (!file_exists($filePath)) {
    die('File does not exist');
}

// Set the appropriate headers for Excel download
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffer
ob_clean();
flush();

// Read the file and output it to the browser
readfile($filePath);
exit;
?>
