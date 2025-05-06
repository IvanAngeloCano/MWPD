<?php
// Notification helper functions
require_once 'connection.php';

// Ensure notifications table exists
function ensureNotificationsTableExists() {
    global $pdo;
    
    try {
        // Check if table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount();
        
        if ($tableCheck == 0) {
            // Create the notifications table
            $createTableSQL = "CREATE TABLE notifications (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                record_id INT NULL,
                record_type VARCHAR(50) NULL,
                link VARCHAR(255) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                is_seen TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (is_read)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $pdo->exec($createTableSQL);
            error_log("Created notifications table");
            return true;
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error checking/creating notifications table: " . $e->getMessage());
        return false;
    }
}

// Call this function to ensure the table exists
ensureNotificationsTableExists();

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
    
    // Debug log
    error_log("=== START addNotification ===");
    error_log("Adding notification - User ID: $user_id, Message: $message, Record ID: " . ($record_id ?? 'null') . ", Type: " . ($record_type ?? 'null'));
    
    // Make sure the user ID is valid
    if (!$user_id || !is_numeric($user_id) || $user_id <= 0) {
        error_log("Invalid user ID for notification: $user_id");
        return false;
    }
    
    // Make sure the notifications table exists
    if (!ensureNotificationsTableExists()) {
        error_log("Could not ensure notifications table exists");
        return false;
    }
    
    try {
        // First check if user has 5+ notifications and delete oldest if needed
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
        $countStmt->execute([$user_id]);
        $count = $countStmt->fetchColumn();
        error_log("User has $count existing notifications");
        
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
            error_log("Deleted oldest notification for user $user_id");
        }
        
        // Prepare parameters for the insert
        $params = [$user_id, $message];
        
        // Handle null values properly
        if ($record_id === null) {
            $record_id = null;
        }
        $params[] = $record_id;
        
        if ($record_type === null) {
            $record_type = null;
        }
        $params[] = $record_type;
        
        if ($link === null) {
            $link = null;
        }
        $params[] = $link;
        
        // Log what we're trying to insert
        error_log("SQL params: " . json_encode($params));
        
        // Add the new notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, record_id, record_type, link) 
                               VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute($params);
        
        if ($result) {
            $newId = $pdo->lastInsertId();
            error_log("Successfully added notification ID: $newId");
            
            // Verify the notification was actually inserted
            $checkStmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ?");
            $checkStmt->execute([$newId]);
            if ($checkStmt->rowCount() > 0) {
                error_log("Verified notification ID $newId exists in database");
            } else {
                error_log("WARNING: Could not verify notification ID $newId exists in database");
            }
            
            return true;
        } else {
            error_log("Failed to add notification, SQL error: " . implode(', ', $stmt->errorInfo()));
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database error adding notification: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("General error adding notification: " . $e->getMessage());
        return false;
    } finally {
        error_log("=== END addNotification ===");
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
        // Debug log for notification attempt
        error_log("Starting notification to Regional Directors for approval submission: direct_hire_id=$direct_hire_id, approval_id=$approval_id, submitted_by=$submitted_by");
        
        // Find all users with Regional Director role (case insensitive)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(role) LIKE '%regional director%'");
        $stmt->execute();
        $rd_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($rd_users)) {
            error_log("No Regional Directors found in the system");
            return false;
        }
        
        error_log("Found " . count($rd_users) . " Regional Directors to notify");
        
        // Get submitter name
        $nameStmt = $pdo->prepare("SELECT CONCAT(firstname, ' ', lastname) as fullname FROM users WHERE id = ?");
        $nameStmt->execute([$submitted_by]);
        $submitter = $nameStmt->fetchColumn();
        
        if (!$submitter) {
            $submitter = "User #$submitted_by";
        }
        
        // Add notification for each Regional Director
        $message = "New approval request for $name submitted by $submitter";
        $link = "approval_view_simple.php?id=" . $approval_id;
        
        $success = true;
        foreach ($rd_users as $rd_user_id) {
            error_log("Sending notification to Regional Director (ID: $rd_user_id)");
            if (!addNotification($rd_user_id, $message, $direct_hire_id, 'direct_hire', $link)) {
                error_log("Failed to send notification to Regional Director (ID: $rd_user_id)");
                $success = false;
            }
        }
        
        return $success;
    } catch (PDOException $e) {
        error_log("Failed to notify Regional Directors about approval submission: " . $e->getMessage());
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
        
        // Validate user ID
        if (!$submitted_by || !is_numeric($submitted_by) || $submitted_by <= 0) {
            error_log("Invalid submitted_by user ID: $submitted_by");
            return false;
        }
        
        // Get approver name
        $approver = "Regional Director"; // Default fallback
        if (isset($_SESSION['user_id'])) {
            try {
                $nameStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                $nameStmt->execute([$_SESSION['user_id']]);
                $approverName = $nameStmt->fetchColumn();
                if ($approverName) {
                    $approver = $approverName;
                }
            } catch (PDOException $e) {
                error_log("Error getting approver name: " . $e->getMessage());
                // Continue with default name
            }
        }
        
        $statusText = ($status == 'approved') ? 'approved' : 'denied';
        $message = "Your request for $name has been $statusText by $approver";
        
        if (!empty($comments)) {
            $message .= ". Comment: " . substr($comments, 0, 50) . (strlen($comments) > 50 ? '...' : '');
        }
        
        $link = "direct_hire_view.php?id=" . $direct_hire_id;
        
        // Ensure the notifications table exists
        ensureNotificationsTableExists();
        
        // Simple direct insert with fixed fields
        $sql = "INSERT INTO notifications (user_id, message, record_id, record_type, link, is_read, is_seen, created_at) 
                VALUES (?, ?, ?, ?, ?, 0, 0, NOW())";
        
        $insertStmt = $pdo->prepare($sql);
        $result = $insertStmt->execute([
            $submitted_by,
            $message,
            $direct_hire_id,
            'direct_hire',
            $link
        ]);
        
        if ($result) {
            error_log("Notification successfully saved to database for user $submitted_by");
            return true;
        } else {
            error_log("Failed to save notification: " . implode(', ', $insertStmt->errorInfo()));
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database error in notifyApprovalDecision: " . $e->getMessage());
        return false;
    }
}

// Notify Regional Directors about new user account requests
function notifyNewUserRequest($username, $full_name) {
    global $pdo;
    ensureNotificationsTableExists();
    
    try {
        // Find all Regional Directors
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'Regional Director' OR role = 'regional director'");
        $stmt->execute();
        $directors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $message = "New user account request: {$full_name} ({$username}) awaiting approval";
        $link = "account_approvals.php";
        
        // Add notification for each Regional Director
        foreach ($directors as $director) {
            addNotification($director['id'], $message, null, 'user_request', $link);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error notifying about new user request: " . $e->getMessage());
        return false;
    }
}

// Notify user about account approval or rejection
function notifyAccountDecision($user_id, $username, $full_name, $decision, $rejection_reason = null) {
    global $pdo;
    ensureNotificationsTableExists();
    
    try {
        $message = $decision === 'approved' 
            ? "User account for {$full_name} ({$username}) has been approved" 
            : "User account for {$full_name} ({$username}) has been rejected" . ($rejection_reason ? ": {$rejection_reason}" : "");
        
        $link = "accounts.php";
        
        // Add notification for the submitter
        addNotification($user_id, $message, null, 'user_account', $link);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error notifying about account decision: " . $e->getMessage());
        return false;
    }
}
?>
