<?php
// Include database connection
require_once 'connection.php';
include 'session.php';

// Only allow POST method for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Get required parameters
$table = isset($_POST['table']) ? $_POST['table'] : null;
$id = isset($_POST['id']) ? $_POST['id'] : null;
$id_field = isset($_POST['id_field']) ? $_POST['id_field'] : null;

// Validate parameters
if (!$table || !$id || !$id_field) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Whitelist of allowed tables for security
$allowed_tables = ['BM', 'direct_hire', 'gov_to_gov', 'job_fairs', 'users', 'blacklist'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Prepare and execute delete statement
    $sql = "DELETE FROM $table WHERE $id_field = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Record not found or already deleted']);
        exit;
    }
    
    // Activity logging removed - table doesn't exist yet
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log error
    error_log("Error deleting record: " . $e->getMessage());
    
    // Return error response
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
