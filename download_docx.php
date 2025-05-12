<?php
/**
 * Download DOCX File
 * 
 * This script handles direct downloads of DOCX files with proper headers
 * to ensure the browser downloads rather than tries to display the file
 */

// Prevent direct access without a file parameter
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('HTTP/1.0 404 Not Found');
    echo 'File not specified';
    exit;
}

// Get the file path from the query string
$file_path = $_GET['file'];

// Basic security check to prevent directory traversal
$file_path = str_replace('..', '', $file_path);

// Check if the file exists
if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    echo 'File not found';
    exit;
}

// Get the filename from the path
$filename = basename($file_path);

// Set the appropriate headers for a DOCX file download
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// Add these headers for improved compatibility with various browsers
header('Access-Control-Allow-Origin: *');
header('X-Content-Type-Options: nosniff');

// Clean any output buffers
ob_clean();
flush();

// Read the file and output it directly to the browser
readfile($file_path);
exit;
