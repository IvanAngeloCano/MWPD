<?php
// Start session if not already started
session_start();

// Default response 
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'unread_count' => 0
];

// Check if index is provided
if (isset($_POST['index']) && is_numeric($_POST['index'])) {
    $index = (int) $_POST['index'];
    
    // Check if notifications exist in session
    if (isset($_SESSION['notifications']) && is_array($_SESSION['notifications'])) {
        // Remove the notification at specified index
        if (isset($_SESSION['notifications'][$index])) {
            unset($_SESSION['notifications'][$index]);
            // Re-index the array
            $_SESSION['notifications'] = array_values($_SESSION['notifications']);
            
            // Count unread notifications
            $unread_count = 0;
            foreach ($_SESSION['notifications'] as $notification) {
                if (!isset($notification['read']) || !$notification['read']) {
                    $unread_count++;
                }
            }
            
            $response = [
                'success' => true,
                'message' => 'Notification dismissed',
                'unread_count' => $unread_count
            ];
        } else {
            $response['message'] = 'Notification not found';
        }
    } else {
        $response['message'] = 'No notifications found';
    }
} else {
    $response['message'] = 'Invalid notification index';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
