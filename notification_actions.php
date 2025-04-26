<?php
// Handle notification actions via AJAX
session_start();
require_once 'connection.php';
require_once 'notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'mark_read':
            // Mark notifications as read
            if (isset($_POST['ids'])) {
                $ids = explode(',', $_POST['ids']);
                $success = true;
                
                foreach ($ids as $id) {
                    if (!markNotificationRead($id, $user_id)) {
                        $success = false;
                    }
                }
                
                $response['success'] = $success;
                $response['unread_count'] = countUnreadNotifications($user_id);
            }
            break;
            
        case 'delete':
            // Delete a notification
            if (isset($_POST['id'])) {
                $id = (int) $_POST['id'];
                $response['success'] = deleteNotification($id, $user_id);
                $response['unread_count'] = countUnreadNotifications($user_id);
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
