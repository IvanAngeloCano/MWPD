<?php
// This is an include file to be added to pages that might be linked from notifications
// It checks for a notification ID in the URL and marks it as read

// Only process if we have a mark_read parameter
if (isset($_GET['mark_read']) && !empty($_GET['mark_read'])) {
    // Include required files
    require_once 'connection.php';
    require_once 'notifications.php';
    
    // Get the notification ID and ensure it's an integer
    $notification_id = (int)$_GET['mark_read'];
    
    // Get the user ID from session
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Mark notification as read
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$notification_id, $user_id]);
            
            if ($result) {
                // Successfully marked as read
                error_log("Notification #$notification_id marked as read for user #$user_id from page load");
            }
        } catch (Exception $e) {
            error_log("Error marking notification as read from page load: " . $e->getMessage());
        }
    }
    
    // Clean up the URL by removing the mark_read parameter
    $url = parse_url($_SERVER['REQUEST_URI']);
    $path = $url['path'];
    
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        unset($query['mark_read']);
        
        if (!empty($query)) {
            $cleanUrl = $path . '?' . http_build_query($query);
        } else {
            $cleanUrl = $path;
        }
        
        // We won't actually redirect to clean the URL as it would cause a page reload
        // But we log it for debugging
        error_log("URL cleaned up, would redirect to: $cleanUrl");
    }
}
?>
