<?php
// Debug script to check notifications functionality
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'connection.php';
require_once 'notifications.php';

echo "<h1>Notification System Debug</h1>";

// Check notifications table structure
echo "<h2>Notifications Table Structure</h2>";
try {
    $columnsStmt = $pdo->query("SHOW COLUMNS FROM notifications");
    echo "<pre>";
    while ($row = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p>Error checking table structure: " . $e->getMessage() . "</p>";
}

// Create a test notification
echo "<h2>Test Notification Creation</h2>";
if (isset($_GET['test']) && $_GET['test'] == 1) {
    // Get user ID from GET or use a default test ID (1)
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
    
    $result = addNotification(
        $user_id,
        "This is a test notification at " . date('Y-m-d H:i:s'),
        1, // Direct hire ID
        'direct_hire',
        'direct_hire_view.php?id=1'
    );
    
    echo "<p>Test notification created: " . ($result ? "Success" : "Failed") . "</p>";
    
    // Also test the direct approach used in notifyApprovalDecision
    try {
        $columnsStmt = $pdo->query("SHOW COLUMNS FROM notifications");
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $message = "TEST: Direct approach at " . date('Y-m-d H:i:s');
        
        // Basic insert with minimum required fields
        $sql = "INSERT INTO notifications (user_id, message";
        $params = [$user_id, $message];
        
        // Add optional fields if they exist in the table
        if (in_array('record_id', $columns)) {
            $sql .= ", record_id";
            $params[] = 1;
        }
        
        if (in_array('record_type', $columns)) {
            $sql .= ", record_type";
            $params[] = 'direct_hire';
        }
        
        if (in_array('link', $columns)) {
            $sql .= ", link";
            $params[] = "direct_hire_view.php?id=1";
        }
        
        // Add created_at field if it exists
        if (in_array('created_at', $columns)) {
            $sql .= ", created_at";
            $params[] = date('Y-m-d H:i:s');
        }
        
        $sql .= ") VALUES (" . implode(", ", array_fill(0, count($params), "?")) . ")";
        
        echo "<p>SQL: " . $sql . "</p>";
        echo "<p>Params: " . implode(", ", $params) . "</p>";
        
        $insertStmt = $pdo->prepare($sql);
        $result = $insertStmt->execute($params);
        
        echo "<p>Direct insert: " . ($result ? "Success" : "Failed") . "</p>";
        
    } catch (PDOException $e) {
        echo "<p>Error with direct insert: " . $e->getMessage() . "</p>";
    }
}

// View current notifications
echo "<h2>Current Notifications in Database</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='5'>
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Message</th>
                <th>Record ID</th>
                <th>Record Type</th>
                <th>Is Read</th>
                <th>Created At</th>
                <th>Link</th>
            </tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['message']) . "</td>";
            echo "<td>" . ($row['record_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['record_type'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['is_read'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['created_at'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['link'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No notifications found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Error fetching notifications: " . $e->getMessage() . "</p>";
}

echo "<p><a href='?test=1'>Create Test Notification</a> | ";
echo "<a href='?'>Refresh Without Creating</a></p>";
?>
