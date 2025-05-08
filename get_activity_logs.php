<?php
/**
 * AJAX endpoint to get recent activity logs
 */
include 'session.php';
require_once 'connection.php';
require_once 'includes/audit_logger.php';

// Initialize audit logger
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role']
];
$auditLogger = new AuditLogger($pdo, $user);

// Get requested limit
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit <= 0 || $limit > 100) {
    $limit = 10; // Enforce reasonable limits
}

// Get optional filters
$filters = [];
if (!empty($_GET['user_id'])) {
    $filters['user_id'] = $_GET['user_id'];
}

if (!empty($_GET['module'])) {
    $filters['module'] = $_GET['module'];
}

if (!empty($_GET['action'])) {
    $filters['action'] = $_GET['action'];
}

if (!empty($_GET['start_date'])) {
    $filters['start_date'] = $_GET['start_date'];
}

if (!empty($_GET['end_date'])) {
    $filters['end_date'] = $_GET['end_date'];
}

// Get recent activity logs
$logs = $auditLogger->getRecentActivity($filters, $limit);

// Process logs for JSON output
$response = [];
foreach ($logs as $log) {
    $response[] = [
        'timestamp' => isset($log['time_elapsed']) ? $log['time_elapsed'] : date('Y-m-d H:i:s', strtotime($log['created_at'])),
        'username' => $log['username'] ?? 'Unknown',
        'role' => $log['role'] ?? 'Unknown',
        'action' => $log['action_display'] ?? $log['action'],
        'module' => $log['module_display'] ?? $log['module'],
        'details' => $log['activity_description'] ?? 'No details provided',
        'record_id' => $log['record_id'] ?? null,
        'ip_address' => $log['ip_address'] ?? 'Unknown'
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
