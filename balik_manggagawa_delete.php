<?php
include 'session.php';
require_once 'connection.php';

// Check if BMID is provided
if (!isset($_GET['bmid']) || empty($_GET['bmid'])) {
    header('Location: balik_manggagawa.php?error=No record ID specified');
    exit();
}

$bmid = $_GET['bmid'];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete record
    $stmt = $pdo->prepare("DELETE FROM BM WHERE bmid = ?");
    $result = $stmt->execute([$bmid]);
    
    if (!$result) {
        throw new Exception("Failed to delete record");
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect with success message and reload parameter
    header("Location: balik_manggagawa.php?success=Record deleted successfully&reload=true");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Redirect with error message
    header('Location: balik_manggagawa.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>