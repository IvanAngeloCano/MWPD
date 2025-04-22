<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: direct_hire.php?error=No document ID specified');
    exit();
}

$document_id = (int)$_GET['id'];

try {
    // Get document details
    $stmt = $pdo->prepare("
        SELECT d.*, dh.name as record_name 
        FROM direct_hire_documents d
        JOIN direct_hire dh ON d.direct_hire_id = dh.id
        WHERE d.id = ?
    ");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        throw new Exception("Document not found");
    }
    
    // Define the uploads directory
    $upload_dir = 'uploads/direct_hire/';
    $file_path = $upload_dir . $document['filename'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        throw new Exception("File not found on server");
    }
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $document['file_type']);
    header('Content-Disposition: attachment; filename="' . $document['original_filename'] . '"');
    header('Content-Length: ' . $document['file_size']);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Read file and output to browser
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    // Redirect with error
    header('Location: direct_hire.php?error=' . urlencode($e->getMessage()));
    exit();
}
?> 