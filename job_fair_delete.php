<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: job_fairs.php?error=No job fair ID specified');
    exit();
}

$job_fair_id = (int)$_GET['id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First check if the job fair exists
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM job_fairs WHERE id = ?");
    $check_stmt->execute([$job_fair_id]);
    
    if ($check_stmt->fetchColumn() == 0) {
        throw new Exception("Job fair not found");
    }
    
    // Delete employer relations (this will cascade to other related tables due to FK constraints)
    $relations_stmt = $pdo->prepare("DELETE FROM job_fair_employers WHERE job_fair_id = ?");
    $relations_stmt->execute([$job_fair_id]);
    
    // Delete the job fair
    $delete_stmt = $pdo->prepare("DELETE FROM job_fairs WHERE id = ?");
    $delete_stmt->execute([$job_fair_id]);
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect with success message
    header('Location: job_fairs.php?success=Job fair deleted successfully');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    header('Location: job_fairs.php?error=' . urlencode($e->getMessage()));
    exit();
}
?> 