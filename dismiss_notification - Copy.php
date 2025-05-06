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
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if ($notification_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID'
    ]);
    exit;
}

try {
    // Delete the notification, ensuring it belongs to this user
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Notification dismissed'
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
