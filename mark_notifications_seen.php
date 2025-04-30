<?php
include 'session.php';
require_once 'connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Mark all notifications as seen for this user
    $stmt = $pdo->prepare("UPDATE notifications SET is_seen = 1 WHERE user_id = ? AND is_seen = 0");
    $stmt->execute([$user_id]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Notifications marked as seen'
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
