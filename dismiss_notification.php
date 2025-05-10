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
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

// Log received parameters for debugging
error_log("dismiss_notification.php called with notification_id: $notification_id for user: $user_id");

if ($notification_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid notification ID'
    ]);
    exit;
}

try {
    // First force-check that the notification exists
    $check_stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
    $check_stmt->execute([$notification_id, $user_id]);
    $notification_exists = $check_stmt->rowCount() > 0;
    
    if ($notification_exists) {
        // Force direct SQL deletion regardless of the function
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user_id]);
        $success = ($stmt->rowCount() > 0);
        error_log("FORCE DELETED notification $notification_id for user $user_id: " . ($success ? 'success' : 'failed deletion'));
        
        // Double-check notification was removed
        $verify_stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ?");
        $verify_stmt->execute([$notification_id]);
        if ($verify_stmt->rowCount() > 0) {
            // Still exists - force a stronger delete
            $pdo->exec("DELETE FROM notifications WHERE id = $notification_id");
            error_log("EMERGENCY DELETION for notification $notification_id");
        }
    } else {
        error_log("Notification $notification_id for user $user_id not found in database.");
        $success = false;
    }
    
    // Get updated unread count
    $unread_count = countUnreadNotifications($user_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count,
        'message' => 'Notification dismissed'
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
