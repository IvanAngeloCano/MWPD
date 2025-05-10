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

// Get unread count and notifications
$unread_count = countUnreadNotifications($user_id);
$notifications = getUserNotifications($user_id, false, 5);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);
