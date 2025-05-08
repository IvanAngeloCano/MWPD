<?php
// Start session for user ID
session_start();

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

// Process deletion if confirmed
$message = '';
$notifications = [];
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Get all current notifications
try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error: " . $e->getMessage();
}

// Handle deletion
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_all') {
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $count = $stmt->rowCount();
            $message = "$count notification(s) deleted successfully";
            
            // Refresh the list
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'delete_one' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $count = $stmt->rowCount();
            $message = $count ? "Notification deleted successfully" : "No notification found";
            
            // Refresh the list
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clear All Notifications</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .notification-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-content {
            flex: 1;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border: none;
            margin-right: 5px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Notification Manager</h1>
        <p>Use this page to view and manage all notifications in the database.</p>
        
        <?php if ($message): ?>
        <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Current Notifications</h2>
            <?php if (empty($notifications)): ?>
                <p>No notifications found.</p>
            <?php else: ?>
                <p>Found <?php echo count($notifications); ?> notification(s) for user ID: <?php echo $user_id; ?></p>
                <form method="post" action="">
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" class="btn btn-danger">Delete All Notifications</button>
                </form>
                
                <div class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item">
                            <div class="notification-content">
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($notification['id']); ?></p>
                                <p><strong>Message:</strong> <?php echo htmlspecialchars($notification['message']); ?></p>
                                <p><strong>Read:</strong> <?php echo $notification['is_read'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Created:</strong> <?php echo htmlspecialchars($notification['created_at']); ?></p>
                            </div>
                            <div class="notification-actions">
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_one">
                                    <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Back to Application</h2>
            <a href="index.php" class="btn">Go Back to Home</a>
        </div>
    </div>
</body>
</html>
