<?php
// Start with a clean session
session_start();

// Save important session variables
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$full_name = $_SESSION['full_name'] ?? null;
$role = $_SESSION['role'] ?? null;

// Log current sessions for debugging
error_log("BEFORE SESSION CLEANUP: " . print_r($_SESSION, true));

// Clear all session notifications
if (isset($_SESSION['notifications'])) {
    unset($_SESSION['notifications']);
}

// Clear all alerts
if (isset($_SESSION['alerts'])) {
    unset($_SESSION['alerts']);
}

// Clear any other temporary notification variables
$notification_keys = [];
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'notif') !== false || strpos($key, 'alert') !== false) {
        $notification_keys[] = $key;
    }
}

// Remove identified notification keys
foreach ($notification_keys as $key) {
    unset($_SESSION[$key]);
}

// Reconnect database and fix notifications table
require_once 'connection.php';

// Ensure user has a user_id for the database operations
if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'No user ID found in session. Please log in again.'
    ]);
    exit;
}

// Check and fix any corruption in the notifications table
try {
    // Make sure table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table if it doesn't exist
        $createSQL = "CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
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
        )";
        $pdo->exec($createSQL);
    } else {
        // Delete any corrupted notifications (null user_id, etc.)
        $pdo->exec("DELETE FROM notifications WHERE user_id IS NULL OR user_id = 0");
        
        // Reset user's notifications that might be causing issues
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
    
    // Restoration of session core values
    $_SESSION['user_id'] = $user_id;
    if ($username) $_SESSION['username'] = $username;
    if ($full_name) $_SESSION['full_name'] = $full_name;
    if ($role) $_SESSION['role'] = $role;
    
    // Log the result
    error_log("AFTER SESSION CLEANUP: " . print_r($_SESSION, true));
    
    echo json_encode([
        'success' => true,
        'message' => 'Session conflicts have been resolved. Please refresh the page.',
        'user_id' => $user_id
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    error_log("Session fix error: " . $e->getMessage());
}
?>
