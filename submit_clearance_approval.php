<?php
include 'session.php';
require_once 'connection.php';

// Check if document ID and record ID are provided
if (!isset($_GET['doc_id']) || !isset($_GET['record_id'])) {
    $_SESSION['error_message'] = "Missing document or record information.";
    header('Location: direct_hire.php');
    exit();
}

$document_id = (int)$_GET['doc_id'];
$record_id = (int)$_GET['record_id'];

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Verify the document exists and belongs to this record
    $check_stmt = $pdo->prepare("SELECT * FROM direct_hire_documents WHERE id = ? AND direct_hire_id = ?");
    $check_stmt->execute([$document_id, $record_id]);
    $document = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        throw new Exception("Document not found or does not belong to this record.");
    }

    // Check if this document has already been submitted for approval
    $check_approval_stmt = $pdo->prepare("SELECT * FROM direct_hire_clearance_approvals WHERE document_id = ?");
    $check_approval_stmt->execute([$document_id]);
    $existing_approval = $check_approval_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_approval) {
        if ($existing_approval['status'] === 'pending') {
            $_SESSION['info_message'] = "This document has already been submitted for approval and is pending review by the Regional Director.";
        } elseif ($existing_approval['status'] === 'approved') {
            $_SESSION['success_message'] = "This document has already been approved by the Regional Director.";
        } elseif ($existing_approval['status'] === 'denied') {
            // Allow resubmission by updating the record
            $update_stmt = $pdo->prepare("UPDATE direct_hire_clearance_approvals SET status = 'pending', comments = NULL, submitted_by = ?, approved_by = NULL, updated_at = NOW() WHERE id = ?");
            $update_stmt->execute([$_SESSION['user_id'] ?? null, $existing_approval['id']]);
            $_SESSION['success_message'] = "Clearance document has been resubmitted for approval to the Regional Director.";
        }
    } else {
        // No previous submission, insert new
        $submit_stmt = $pdo->prepare("INSERT INTO direct_hire_clearance_approvals (direct_hire_id, document_id, submitted_by, status) VALUES (?, ?, ?, 'pending')");
        $submit_stmt->execute([
            $record_id,
            $document_id,
            $_SESSION['user_id'] ?? null
        ]);
        $_SESSION['success_message'] = "Clearance document has been submitted for approval to the Regional Director.";
    }

    header("Location: direct_hire_view.php?id=$record_id");
    exit();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: direct_hire_view.php?id=$record_id");
    exit();
}
