<?php
// Notification helper functions
require_once 'connection.php';

/**
 * Add a notification for a user
 * 
 * @param int $user_id The ID of the user to notify
 * @param string $message The notification message
 * @param int|null $record_id Related record ID (optional)
 * @param string|null $record_type Type of record (e.g., 'direct_hire', 'clearance')
 * @param string|null $link URL to link the notification to
 * @return bool True if notification was added, false otherwise
 */
function addNotification($user_id, $message, $record_id = null, $record_type = null, $link = null) {
    global $pdo;
    
    try {
        // First check if user has 5+ notifications and delete oldest if needed
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
        $countStmt->execute([$user_id]);
        $count = $countStmt->fetchColumn();
        
        if ($count >= 5) {
            // Delete the oldest notification for this user
            $deleteStmt = $pdo->prepare("DELETE FROM notifications 
                WHERE id = (
                    SELECT id FROM (
                        SELECT id FROM notifications 
                        WHERE user_id = ? 
                        ORDER BY created_at ASC 
                        LIMIT 1
                    ) as oldest
                )");
            $deleteStmt->execute([$user_id]);
        }
        
        // Add the new notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, record_id, record_type, link) 
                               VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $message, $record_id, $record_type, $link]);
    } catch (PDOException $e) {
        error_log("Failed to add notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all unread notifications for a user
 * 
 * @param int $user_id The user ID
 * @param bool $include_read Whether to include read notifications
 * @param int $limit Maximum number of notifications to return
 * @return array Array of notification objects
 */
function getUserNotifications($user_id, $include_read = false, $limit = 5) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        
        if (!$include_read) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT " . intval($limit);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to get notifications: " . $e->getMessage());
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
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 
                               WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Failed to mark notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a notification
 * 
 * @param int $notification_id The notification ID
 * @param int $user_id The user ID (for security)
 * @return bool True if successful, false otherwise
 */
function deleteNotification($notification_id, $user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications 
                               WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Failed to delete notification: " . $e->getMessage());
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
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
                               WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Failed to count notifications: " . $e->getMessage());
        return 0;
    }
}

/**
 * Add a notification for approval submission (to notify Regional Director)
 * 
 * @param int $direct_hire_id Direct hire record ID
 * @param int $approval_id Approval record ID
 * @param int $submitted_by User ID who submitted the record
 * @param string $name Name from the direct hire record
 * @return bool
 */
function notifyApprovalSubmitted($direct_hire_id, $approval_id, $submitted_by, $name) {
    global $pdo;
    
    try {
        // Find all users with Regional Director role
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role LIKE '%regional director%'");
        $stmt->execute();
        $rd_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($rd_users)) {
            return false;
        }
        
        // Get submitter name
        $nameStmt = $pdo->prepare("SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?");
        $nameStmt->execute([$submitted_by]);
        $submitter = $nameStmt->fetchColumn();
        
        // Add notification for each Regional Director
        $message = "New approval request for $name submitted by $submitter";
        $link = "approval_detail_view.php?id=" . $approval_id;
        
        foreach ($rd_users as $rd_user_id) {
            addNotification($rd_user_id, $message, $direct_hire_id, 'direct_hire', $link);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to notify about approval: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify the submitter about approval decision
 * 
 * @param int $direct_hire_id Direct hire record ID
 * @param int $approval_id Approval record ID
 * @param int $submitted_by User ID who submitted the record
 * @param string $name Name from the direct hire record
 * @param string $status 'approved' or 'denied'
 * @param string $comments Any comments from the approver
 * @return bool
 */
function notifyApprovalDecision($direct_hire_id, $approval_id, $submitted_by, $name, $status, $comments = '') {
    global $pdo;
    
    try {
        // Debug log for notification attempt
        error_log("Starting notification creation for submitted_by: $submitted_by, name: $name, status: $status");
        
        // Get approver name
        $nameStmt = $pdo->prepare("SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?");
        $nameStmt->execute([$_SESSION['user_id']]);
        $approver = $nameStmt->fetchColumn();
        
        if (!$approver) {
            $approver = "Regional Director"; // Fallback if approver name not found
            error_log("Approver name not found for user ID: " . $_SESSION['user_id']);
        }
        
        $statusText = ($status == 'approved') ? 'approved' : 'denied';
        $message = "Your request for $name has been $statusText by $approver";
        
        if (!empty($comments)) {
            $message .= ". Comment: " . substr($comments, 0, 50) . (strlen($comments) > 50 ? '...' : '');
        }
        
        $link = "direct_hire_view.php?id=" . $direct_hire_id;
        
        // Direct insert using explicit fields to match existing table structure
        try {
            // Check the structure of notifications table
            $columnsStmt = $pdo->query("SHOW COLUMNS FROM notifications");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Log available columns
            error_log("Available columns in notifications table: " . implode(", ", $columns));
            
            // Basic insert with minimum required fields
            $sql = "INSERT INTO notifications (user_id, message";
            $params = [$submitted_by, $message];
            
            // Add optional fields if they exist in the table
            if (in_array('record_id', $columns)) {
                $sql .= ", record_id";
                $params[] = $direct_hire_id;
            }
            
            if (in_array('record_type', $columns)) {
                $sql .= ", record_type";
                $params[] = 'direct_hire';
            }
            
            if (in_array('link', $columns)) {
                $sql .= ", link";
                $params[] = $link;
            }
            
            // Add created_at field if it exists
            if (in_array('created_at', $columns)) {
                $sql .= ", created_at";
                $params[] = date('Y-m-d H:i:s');
            }
            
            $sql .= ") VALUES (" . implode(", ", array_fill(0, count($params), "?")) . ")";
            
            // Log the SQL query for debugging
            error_log("Notification insert SQL: $sql");
            
            $insertStmt = $pdo->prepare($sql);
            $result = $insertStmt->execute($params);
            
            error_log("Notification created successfully for user $submitted_by: " . ($result ? 'Success' : 'Failed'));
            return $result;
            
        } catch (PDOException $e) {
            error_log("SQL Error creating notification: " . $e->getMessage());
            
            // Fallback to original method
            return addNotification($submitted_by, $message, $direct_hire_id, 'direct_hire', $link);
        }
    } catch (PDOException $e) {
        error_log("Failed to notify about approval decision: " . $e->getMessage());
        return false;
    }
}
?>
