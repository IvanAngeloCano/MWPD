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

// Mark all notifications as read
$success = markAllNotificationsRead($user_id);

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $success ? 'All notifications marked as read' : 'Failed to mark notifications as read',
    'unread_count' => countUnreadNotifications($user_id)
]);
?>