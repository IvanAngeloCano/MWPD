<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: gov_to_gov.php?error=No record ID specified');
    exit();
}

$record_id = (int)$_GET['id'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete record
    $stmt = $pdo->prepare("DELETE FROM gov_to_gov WHERE id = ?");
    $result = $stmt->execute([$record_id]);
    
    if (!$result) {
        throw new Exception("Failed to delete record");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect with success message and script to reload the table
    header("Location: gov_to_gov.php?success=Record deleted successfully&reload=true");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Redirect with error message
    header('Location: gov_to_gov.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>
