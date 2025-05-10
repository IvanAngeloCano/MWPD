<?php
// Start session if not already started
session_start();

// Default response
$response = [
    'success' => false,
    'message' => 'No notifications found'
];

// Check if notifications exist in session
if (isset($_SESSION['notifications']) && is_array($_SESSION['notifications'])) {
    $count = count($_SESSION['notifications']);
    
    // Mark all notifications as read
    foreach ($_SESSION['notifications'] as &$notification) {
        $notification['read'] = true;
    }
    
    $response = [
        'success' => true,
        'message' => $count . ' notification(s) marked as read',
        'unread_count' => 0
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
