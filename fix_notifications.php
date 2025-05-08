<?php
include 'session.php';
require_once 'connection.php';
require_once 'notifications.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'fixed_count' => 0
];

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$fixed_count = 0;
$errors = [];

try {
    // 1. Ensure the notifications table exists and has proper structure
    $pdo->beginTransaction();
    
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'notifications'")->rowCount();
    
    if ($tableCheck == 0) {
        // Create the notifications table if it doesn't exist
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
        $fixed_count++;
        error_log("Created notifications table");
    } else {
        // Check if all required columns exist
        $columns = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
        
        // Required columns and their SQL to add if missing
        $required_columns = [
            'id' => "ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY",
            'user_id' => "ADD COLUMN user_id INT NOT NULL",
            'message' => "ADD COLUMN message TEXT NOT NULL",
            'record_id' => "ADD COLUMN record_id INT NULL",
            'record_type' => "ADD COLUMN record_type VARCHAR(50) NULL",
            'link' => "ADD COLUMN link VARCHAR(255) NULL",
            'is_read' => "ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0",
            'is_seen' => "ADD COLUMN is_seen TINYINT(1) NOT NULL DEFAULT 0",
            'created_at' => "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ];
        
        foreach ($required_columns as $column => $alter_sql) {
            if (!in_array($column, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE notifications $alter_sql");
                    $fixed_count++;
                    error_log("Added missing column: $column");
                } catch (PDOException $e) {
                    $errors[] = "Failed to add column $column: " . $e->getMessage();
                }
            }
        }
    }
    
    // 2. Fix the header.php file JavaScript issues
    $header_file = file_get_contents('_header.php');
    $fixed_header = false;
    
    // Fix 1: Ensure the dismiss notification uses the right parameter name
    if (strpos($header_file, "body: 'id=' + id") !== false) {
        $header_file = str_replace(
            "body: 'id=' + id", 
            "body: 'notification_id=' + id", 
            $header_file
        );
        $fixed_header = true;
        $fixed_count++;
    }
    
    // Fix 2: Fix the notification dismiss handler - ensure proper event handling
    $dismiss_pattern = "/document\.addEventListener\('click', function\(e\) {\s*if \(e\.target\.classList\.contains\('notification-dismiss'\)\) {/";
    $dismiss_replacement = "document.addEventListener('click', function(e) {
      if (e.target.classList.contains('notification-dismiss')) {
        e.stopPropagation(); // Prevent event bubbling";
    
    if (preg_match($dismiss_pattern, $header_file)) {
        $header_file = preg_replace($dismiss_pattern, $dismiss_replacement, $header_file);
        $fixed_header = true;
        $fixed_count++;
    }
    
    // Fix 3: Add error handling for notification dismissal
    $dismiss_function_pattern = "/function dismissNotification\(id\) {\s*if \(!id\) return;/";
    $dismiss_function_replacement = "function dismissNotification(id) {
      if (!id) {
        console.error('Attempted to dismiss notification without ID');
        return;
      }
      console.log('Dismissing notification ID:', id);";
    
    if (preg_match($dismiss_function_pattern, $header_file)) {
        $header_file = preg_replace($dismiss_function_pattern, $dismiss_function_replacement, $header_file);
        $fixed_header = true;
        $fixed_count++;
    }
    
    // Write the fixed header file if changes were made
    if ($fixed_header) {
        file_put_contents('_header.php', $header_file);
    }
    
    // 3. Fix dismiss_notification.php if needed
    $dismiss_file = file_get_contents('dismiss_notification.php');
    $fixed_dismiss = false;
    
    // Ensure the notifications.php is required
    if (strpos($dismiss_file, "require_once 'notifications.php';") === false) {
        $dismiss_file = str_replace(
            "require_once 'connection.php';", 
            "require_once 'connection.php';\nrequire_once 'notifications.php';", 
            $dismiss_file
        );
        $fixed_dismiss = true;
        $fixed_count++;
    }
    
    // Ensure the countUnreadNotifications function is called 
    if (strpos($dismiss_file, "countUnreadNotifications") === false) {
        $success_pattern = "/header\('Content-Type: application\/json'\);\s*echo json_encode\(\[\s*'success' => true/";
        $success_replacement = "// Get updated unread count
    \$unread_count = countUnreadNotifications(\$user_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'unread_count' => \$unread_count";
        
        if (preg_match($success_pattern, $dismiss_file)) {
            $dismiss_file = preg_replace($success_pattern, $success_replacement, $dismiss_file);
            $fixed_dismiss = true;
            $fixed_count++;
        }
    }
    
    // Write the fixed dismiss file if changes were made
    if ($fixed_dismiss) {
        file_put_contents('dismiss_notification.php', $dismiss_file);
    }
    
    // 4. Create a small JavaScript fix for the header
    $js_fix = "
// Fix notification system issues
document.addEventListener('DOMContentLoaded', function() {
    // 1. Fix dismiss buttons if they're not working
    const dismissButtons = document.querySelectorAll('.notification-dismiss');
    if (dismissButtons) {
        dismissButtons.forEach(button => {
            // Remove existing event handlers
            const clone = button.cloneNode(true);
            button.parentNode.replaceChild(clone, button);
            
            // Add new event handler
            clone.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                if (id) {
                    console.log('Dismissing notification ID:', id);
                    dismissNotification(id);
                }
            });
        });
    }
    
    // 2. Fix notification onClick if needed
    const notificationItems = document.querySelectorAll('.notification-item');
    if (notificationItems) {
        notificationItems.forEach(item => {
            // Make sure clicking on notification content works
            item.style.cursor = 'pointer';
            
            // Fix links if they exist
            const link = item.getAttribute('data-link');
            if (link) {
                item.addEventListener('click', function(e) {
                    if (!e.target.classList.contains('notification-dismiss')) {
                        window.location.href = link;
                    }
                });
            }
        });
    }
});
";
    
    // Write the JavaScript fix to a file
    file_put_contents('assets/js/notification_fixes.js', $js_fix);
    $fixed_count++;
    
    // 5. Add the script include to the header file if not already there
    if (strpos($header_file, 'notification_fixes.js') === false) {
        $script_tag = "<script src=\"assets/js/notification_fixes.js\"></script>\n</body>";
        $header_file = str_replace("</body>", $script_tag, $header_file);
        file_put_contents('_header.php', $header_file);
        $fixed_count++;
    }
    
    // Commit database changes
    $pdo->commit();
    
    // If we get here, everything worked
    $response['success'] = true;
    $response['message'] = 'Notification system has been fixed successfully';
    $response['fixed_count'] = $fixed_count;
    $response['errors'] = $errors;
    
} catch (Exception $e) {
    // Rollback database changes
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response['message'] = 'Error fixing notification system: ' . $e->getMessage();
    $response['errors'] = $errors;
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
