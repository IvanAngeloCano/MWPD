<?php
include 'session.php';
require_once 'connection.php';
require_once 'notifications.php';

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
$notification_id = isset($_POST['id']) ? (int)$_POST['id'] : -1;

if ($notification_id < 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID'
    ]);
    exit;
}

// Mark notification as read
$success = markNotificationRead($notification_id, $user_id);

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Notification marked as read' : 'Failed to mark notification as read',
    'unread_count' => countUnreadNotifications($user_id)
]);
?>