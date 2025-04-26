<?php
include 'session.php';
require_once 'connection.php';

// Check if document ID is provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['direct_hire_id']) || empty($_GET['direct_hire_id'])) {
    header('Location: direct_hire.php?error=Missing document or record ID');
    exit();
}

$document_id = (int)$_GET['id'];
$direct_hire_id = (int)$_GET['direct_hire_id'];

try {
    // Get document details first to delete the file
    $stmt = $pdo->prepare("SELECT filename FROM direct_hire_documents WHERE id = ? AND direct_hire_id = ?");
    $stmt->execute([$document_id, $direct_hire_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        throw new Exception("Document not found");
    }
    
    // Delete the physical file
    $file_path = 'uploads/direct_hire_clearance/' . $document['filename'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete the database record
    $delete_stmt = $pdo->prepare("DELETE FROM direct_hire_documents WHERE id = ? AND direct_hire_id = ?");
    $delete_stmt->execute([$document_id, $direct_hire_id]);
    
    // Redirect back to the record view with success message
    header("Location: direct_hire_view.php?id={$direct_hire_id}&success=Document removed successfully");
    exit();
    
} catch (Exception $e) {
    header("Location: direct_hire_view.php?id={$direct_hire_id}&error=" . urlencode($e->getMessage()));
    exit();
}
