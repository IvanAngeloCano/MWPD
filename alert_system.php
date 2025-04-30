<?php
/**
 * Simple alert system that doesn't rely on database storage
 * Uses session storage for alerts and notifications
 */

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Add an alert message that will be displayed on the next page load
 * 
 * @param string $message The alert message
 * @param string $type The alert type (success, info, warning, danger)
 * @param string $link Optional link to redirect to after viewing
 */
function addAlert($message, $type = 'info', $link = '') {
    if (!isset($_SESSION['alerts'])) {
        $_SESSION['alerts'] = [];
    }
    
    $_SESSION['alerts'][] = [
        'message' => $message,
        'type' => $type,
        'link' => $link,
        'time' => time()
    ];
}

/**
 * Add a notification for a specific user
 * 
 * @param int $userId The user ID
 * @param string $message The notification message
 * @param string $type The notification type (success, info, warning, danger)
 * @param string $link Optional link to redirect to after viewing
 */
function addUserNotification($userId, $message, $type = 'info', $link = '') {
    if (!isset($_SESSION['user_notifications'])) {
        $_SESSION['user_notifications'] = [];
    }
    
    // Add to the user's notifications
    if (!isset($_SESSION['user_notifications'][$userId])) {
        $_SESSION['user_notifications'][$userId] = [];
    }
    
    // Limit to 5 notifications per user
    if (count($_SESSION['user_notifications'][$userId]) >= 5) {
        array_shift($_SESSION['user_notifications'][$userId]);
    }
    
    $_SESSION['user_notifications'][$userId][] = [
        'message' => $message,
        'type' => $type,
        'link' => $link,
        'time' => time(),
        'read' => false
    ];
}

/**
 * Get all alerts for the current session and clear them
 * 
 * @return array Array of alert messages
 */
function getAlerts() {
    $alerts = isset($_SESSION['alerts']) ? $_SESSION['alerts'] : [];
    $_SESSION['alerts'] = [];
    return $alerts;
}

/**
 * Get all session notifications for a specific user
 * 
 * @param int $userId The user ID
 * @return array Array of notifications
 */
function getSessionNotifications($userId) {
    if (!isset($_SESSION['user_notifications']) || !isset($_SESSION['user_notifications'][$userId])) {
        return [];
    }
    
    return $_SESSION['user_notifications'][$userId];
}

/**
 * Count unread session notifications for a user
 * 
 * @param int $userId The user ID
 * @return int Number of unread notifications
 */
function countUnreadSessionNotifications($userId) {
    $notifications = getSessionNotifications($userId);
    $count = 0;
    
    foreach ($notifications as $notification) {
        if (!$notification['read']) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $userId The user ID
 */
function markAllNotificationsAsRead($userId) {
    if (!isset($_SESSION['user_notifications']) || !isset($_SESSION['user_notifications'][$userId])) {
        return;
    }
    
    foreach ($_SESSION['user_notifications'][$userId] as $key => $notification) {
        $_SESSION['user_notifications'][$userId][$key]['read'] = true;
    }
}

/**
 * Mark a specific notification as read
 * 
 * @param int $userId The user ID
 * @param int $index The notification index
 */
function markNotificationAsRead($userId, $index) {
    if (!isset($_SESSION['user_notifications']) || 
        !isset($_SESSION['user_notifications'][$userId]) || 
        !isset($_SESSION['user_notifications'][$userId][$index])) {
        return;
    }
    
    $_SESSION['user_notifications'][$userId][$index]['read'] = true;
}

/**
 * Display alerts HTML
 */
function displayAlerts() {
    $alerts = getAlerts();
    
    if (empty($alerts)) {
        return;
    }
    
    echo '<div class="alerts-container">';
    
    foreach ($alerts as $alert) {
        $type = isset($alert['type']) ? $alert['type'] : 'info';
        $message = isset($alert['message']) ? $alert['message'] : '';
        $link = isset($alert['link']) ? $alert['link'] : '';
        
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        
        if (!empty($link)) {
            echo ' <a href="' . htmlspecialchars($link) . '" class="alert-link">View</a>';
        }
        
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    
    echo '</div>';
}
?>
