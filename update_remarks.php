<?php
session_start();
require_once 'config/database.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the ID and remarks from POST data
        $id = $_POST['id'] ?? null;
        $remarks = $_POST['remarks'] ?? null;

        if (!$id || !$remarks) {
            throw new Exception('ID and remarks are required');
        }

        // Update the remarks in the database
        $stmt = $pdo->prepare("UPDATE gov_to_gov SET remarks = ? WHERE id = ?");
        $stmt->execute([$remarks, $id]);

        // Return success response
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Return error response
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
