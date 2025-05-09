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

// Ensure the notifications table exists
ensureNotificationsTableExists();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'mark_read':
            // Mark notifications as read
            if (isset($_POST['ids'])) {
                $ids_string = $_POST['ids'];
                $ids = explode(',', $ids_string);
                $success = true;
                
                // Filter out non-numeric IDs
                $ids = array_filter($ids, function($id) {
                    return is_numeric($id) && $id > 0;
                });
                
                if (!empty($ids)) {
                    // Log the IDs being marked as read
                    error_log("Marking notifications as read: " . implode(', ', $ids) . " for user $user_id");
                    
                    foreach ($ids as $id) {
                        if (!markNotificationRead($id, $user_id)) {
                            $success = false;
                            error_log("Failed to mark notification $id as read for user $user_id");
                        }
                    }
                }
                
                $response['success'] = $success;
                $response['unread_count'] = countUnreadNotifications($user_id);
                $response['message'] = $success ? 'Notifications marked as read' : 'Failed to mark some notifications as read';
            }
            break;
            
        case 'delete':
            // Delete a notification
            if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                $id = (int) $_POST['id'];
                error_log("Deleting notification $id for user $user_id");
                
                $response['success'] = deleteNotification($id, $user_id);
                $response['unread_count'] = countUnreadNotifications($user_id);
                $response['message'] = $response['success'] ? 'Notification deleted' : 'Failed to delete notification';
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
