<?php
// This file serves images stored in the database as BLOBs

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.0 404 Not Found');
    echo 'Image ID not specified';
    exit;
}

$document_id = (int)$_GET['id'];

try {
    // Fetch the image from the database
    $stmt = $pdo->prepare("SELECT file_type, file_content FROM direct_hire_documents 
                          WHERE id = ? AND file_type LIKE 'image/%'");
    $stmt->execute([$document_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        header('HTTP/1.0 404 Not Found');
        echo 'Image not found or not an image file';
        exit;
    }
    
    // Set appropriate content type header
    header('Content-Type: ' . $image['file_type']);
    
    // Output the image data
    echo $image['file_content'];
    
} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Database error: ' . $e->getMessage();
}
