<?php
include 'session.php';
require_once 'connection.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signatory_id = isset($_POST['signatory_id']) ? (int)$_POST['signatory_id'] : 0;
    
    // Validate signatory ID
    if ($signatory_id <= 0) {
        header('Location: manage_signatories.php?error=Invalid signatory ID');
        exit();
    }
    
    // Check if file is uploaded
    if (!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
        header('Location: manage_signatories.php?error=File upload failed: ' . $_FILES['signature']['error']);
        exit();
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['signature']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        header('Location: manage_signatories.php?error=Invalid file type. Only JPG, PNG, and GIF are allowed');
        exit();
    }
    
    // Create signatures directory if it doesn't exist
    $upload_dir = 'signatures';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate a unique filename
    $file_extension = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
    $new_filename = 'signature_' . $signatory_id . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . '/' . $new_filename;
    
    // Move the uploaded file
    if (move_uploaded_file($_FILES['signature']['tmp_name'], $target_file)) {
        try {
            // Update the signatory record with the new signature file
            $stmt = $pdo->prepare("UPDATE signatories SET signature_file = ? WHERE id = ?");
            $stmt->execute([$new_filename, $signatory_id]);
            
            header('Location: manage_signatories.php?success=Signature uploaded successfully');
            exit();
        } catch (PDOException $e) {
            // Delete the uploaded file if database update fails
            unlink($target_file);
            header('Location: manage_signatories.php?error=' . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header('Location: manage_signatories.php?error=Failed to move uploaded file');
        exit();
    }
} else {
    // If not a POST request, redirect to the manage signatories page
    header('Location: manage_signatories.php');
    exit();
}
