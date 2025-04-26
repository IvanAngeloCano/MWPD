<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: direct_hire.php?error=No document ID specified');
    exit();
}

$document_id = (int)$_GET['id'];
$record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;

try {
    // Get document details before deletion
    $stmt = $pdo->prepare("SELECT * FROM direct_hire_documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        throw new Exception("Document not found");
    }
    
    // If record_id not provided, get it from the document
    if (!$record_id) {
        $record_id = $document['direct_hire_id'];
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete file from server
    $upload_dir = 'uploads/direct_hire/';
    $file_path = $upload_dir . $document['filename'];
    
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            throw new Exception("Failed to delete file from server");
        }
    }
    
    // Delete record from database
    $stmt = $pdo->prepare("DELETE FROM direct_hire_documents WHERE id = ?");
    $result = $stmt->execute([$document_id]);
    
    if (!$result) {
        throw new Exception("Failed to delete document record");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect based on context
    if ($record_id) {
        // If we know which record this document belongs to, redirect to edit page
        header("Location: direct_hire_edit.php?id=$record_id&success=Document deleted successfully");
    } else {
        // Otherwise redirect to main listing
        header("Location: direct_hire.php?success=Document deleted successfully");
    }
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Redirect with error
    if ($record_id) {
        header("Location: direct_hire_edit.php?id=$record_id&error=" . urlencode($e->getMessage()));
    } else {
        header("Location: direct_hire.php?error=" . urlencode($e->getMessage()));
    }
    exit();
}
?> 