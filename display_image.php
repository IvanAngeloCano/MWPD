<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('No image ID specified');
}

$image_id = (int)$_GET['id'];

try {
    // Get image data from database
    $stmt = $pdo->prepare("SELECT file_content, file_type FROM direct_hire_documents WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image || empty($image['file_content'])) {
        header('HTTP/1.0 404 Not Found');
        exit('Image not found or empty');
    }
    
    // Set the content type header based on the image type
    header('Content-Type: ' . $image['file_type']);
    
    // Disable caching for development (can be adjusted for production)
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the image binary data
    echo $image['file_content'];
    
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Error retrieving image: ' . $e->getMessage());
}
?>
