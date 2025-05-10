<?php
/**
 * AJAX endpoint to get recent blacklist check logs
 */
include 'session.php';
require_once 'connection.php';

// Get requested limit
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0 || $limit > 100) {
    $limit = 10; // Enforce reasonable limits
}

// Get blacklist check logs
try {
    // Check if blacklist_check_log table exists
    $tableCheckStmt = $pdo->query("SHOW TABLES LIKE 'blacklist_check_log'");
    if ($tableCheckStmt->rowCount() === 0) {
        // Table doesn't exist, create it
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `blacklist_check_log` (
              `id` bigint(20) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `check_date` datetime NOT NULL,
              `first_name` varchar(100) NOT NULL,
              `last_name` varchar(100) NOT NULL,
              `identifier_type` varchar(50) NOT NULL,
              `identifier_value` varchar(255) NOT NULL,
              `module` varchar(50) NOT NULL,
              `match_found` tinyint(1) NOT NULL DEFAULT 0,
              `blacklist_id` int(11) DEFAULT NULL,
              `action_taken` varchar(100) DEFAULT NULL,
              `ip_address` varchar(45) NOT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_user` (`user_id`),
              KEY `idx_date` (`check_date`),
              KEY `idx_name` (`last_name`,`first_name`),
              KEY `idx_identifier` (`identifier_type`,`identifier_value`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
    
    // Get logs with JOIN to users table
    $stmt = $pdo->prepare("
        SELECT 
            l.check_date,
            CONCAT(l.first_name, ' ', l.last_name) AS name,
            l.identifier_type,
            l.identifier_value,
            l.match_found,
            l.action_taken,
            u.username
        FROM blacklist_check_log l
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.check_date DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process logs for JSON output
    $response = [];
    foreach ($logs as $log) {
        $response[] = [
            'date' => date('Y-m-d H:i:s', strtotime($log['check_date'])),
            'name' => $log['name'],
            'identifier' => ucfirst($log['identifier_type']) . ': ' . $log['identifier_value'],
            'match' => (bool)$log['match_found'],
            'action' => $log['action_taken'] ?? 'Check performed',
            'user' => $log['username'] ?? 'Unknown'
        ];
    }
    
    // If no logs and table exists, return empty array
    if (empty($response)) {
        $response = [];
    }
} catch (PDOException $e) {
    // Log error and return empty response
    error_log("Error getting blacklist logs: " . $e->getMessage());
    $response = [];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
