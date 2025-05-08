<?php
// Emergency script to delete notifications - bypasses all existing code
// Use the main system session handler instead of starting a new one
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For debugging - log all session variables
error_log("SESSION VARS: " . print_r($_SESSION, true));

// Direct database connection
$host = 'localhost';
$db   = 'mwpd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create PDO connection
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Default response
$response = [
    'success' => false,
    'message' => 'Failed to delete notification',
    'unread_count' => 0,
    'debug_info' => []
];

// Get the notification ID from POST or GET
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Log input parameters for debugging
$response['debug_info']['input'] = [
    'notification_id' => $notification_id,
    'user_id' => $user_id,
    'post' => $_POST,
    'get' => $_GET
];

// 1. First check if the notification exists
try {
    $check_sql = "SELECT * FROM notifications WHERE id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$notification_id]);
    $notification = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['debug_info']['notification'] = $notification;
    
    if ($notification) {
        // 2. Force delete directly with SQL
        $delete_sql = "DELETE FROM notifications WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$notification_id]);
        
        $affected_rows = $delete_stmt->rowCount();
        $response['debug_info']['affected_rows'] = $affected_rows;
        
        if ($affected_rows > 0) {
            // 3. Verify deletion was successful
            $verify_sql = "SELECT id FROM notifications WHERE id = ?";
            $verify_stmt = $pdo->prepare($verify_sql);
            $verify_stmt->execute([$notification_id]);
            
            if ($verify_stmt->rowCount() === 0) {
                $response['success'] = true;
                $response['message'] = 'Notification successfully deleted';
                
                // 4. Count remaining unread notifications
                $count_sql = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0";
                $count_stmt = $pdo->prepare($count_sql);
                $count_stmt->execute([$user_id]);
                $unread_count = $count_stmt->fetchColumn();
                
                $response['unread_count'] = $unread_count;
            } else {
                // Emergency deletion failed
                $response['message'] = 'Deletion verification failed';
            }
        } else {
            $response['message'] = 'No rows affected by deletion';
        }
    } else {
        $response['message'] = 'Notification not found';
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    $response['debug_info']['error'] = $e->getMessage();
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
?>
