<?php
/**
 * MWPD Notification System - Final Version
 * Simple, reliable database notifications
 */

// Table name for notifications
$NOTIFICATION_TABLE = "mwpd_notifications_20250429";

/**
 * Add a notification for a user
 * 
 * @param int $user_id The user ID to notify
 * @param string $message The notification message
 * @param int|null $record_id Related record ID (optional)
 * @param string|null $record_type Type of record (e.g., "direct_hire")
 * @param string|null $link URL to link the notification to
 * @return int|false Notification ID if successful, false otherwise
 */
function addNotification($user_id, $message, $record_id = null, $record_type = null, $link = null) {
    global $pdo, $NOTIFICATION_TABLE;
    
    if (!$user_id || !is_numeric($user_id)) {
        error_log("Invalid user ID for notification: " . print_r($user_id, true));
        return false;
    }
    
    try {
        // Clean up old notifications if more than 10
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM $NOTIFICATION_TABLE WHERE user_id = ?");
        $countStmt->execute([$user_id]);
        $count = $countStmt->fetchColumn();
        
        if ($count >= 10) {
            // Delete oldest notifications
            $deleteStmt = $pdo->prepare("DELETE FROM $NOTIFICATION_TABLE 
                WHERE id IN (
                    SELECT id FROM (
                        SELECT id FROM $NOTIFICATION_TABLE 
                        WHERE user_id = ? 
                        ORDER BY created_at ASC 
                        LIMIT 5
                    ) as oldest
                )");
            $deleteStmt->execute([$user_id]);
        }
        
        // Add new notification
        $stmt = $pdo->prepare("INSERT INTO $NOTIFICATION_TABLE 
                              (user_id, message, record_id, record_type, link) 
                              VALUES (?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([$user_id, $message, $record_id, $record_type, $link]);
        
        if ($result) {
            error_log("Added notification for user $user_id: $message");
            return $pdo->lastInsertId();
        } else {
            error_log("Failed to add notification: " . implode(", ", $stmt->errorInfo()));
            return false;
        }
    } catch (PDOException $e) {
        error_log("Error adding notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notifications for a user
 * 
 * @param int $user_id The user ID
 * @param bool $include_read Whether to include read notifications
 * @return array Array of notification objects
 */
function getUserNotifications($user_id, $include_read = false) {
    global $pdo, $NOTIFICATION_TABLE;
    
    try {
        $sql = "SELECT * FROM $NOTIFICATION_TABLE WHERE user_id = ?";
        
        if (!$include_read) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark a notification as read
 * 
 * @param int $notification_id The notification ID
 * @param int $user_id The user ID (for security)
 * @return bool True if successful, false otherwise
 */
function markNotificationRead($notification_id, $user_id) {
    global $pdo, $NOTIFICATION_TABLE;
    
    try {
        $stmt = $pdo->prepare("UPDATE $NOTIFICATION_TABLE SET is_read = 1 
                               WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $user_id The user ID
 * @return bool True if successful, false otherwise
 */
function markAllNotificationsRead($user_id) {
    global $pdo, $NOTIFICATION_TABLE;
    
    try {
        $stmt = $pdo->prepare("UPDATE $NOTIFICATION_TABLE SET is_read = 1 
                               WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Count unread notifications for a user
 * 
 * @param int $user_id The user ID
 * @return int Number of unread notifications
 */
function countUnreadNotifications($user_id) {
    global $pdo, $NOTIFICATION_TABLE;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $NOTIFICATION_TABLE 
                               WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error counting notifications: " . $e->getMessage());
        return 0;
    }
}
?>