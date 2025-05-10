<?php
// Include session management
include 'session.php';

// Check if user has admin privileges
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director')) {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Initialize variables
$logs = [];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$log_file = dirname(__FILE__) . '/account_approvals_log.json';

// Load logs if file exists
if (file_exists($log_file) && filesize($log_file) > 0) {
    $logs = json_decode(file_get_contents($log_file), true);
    if (!is_array($logs)) {
        $logs = [];
    }
}

// Sort logs by timestamp (newest first)
usort($logs, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Filter logs based on type
$filtered_logs = [];
if ($filter === 'approvals') {
    foreach ($logs as $log) {
        if (isset($log['type']) && $log['type'] === 'approval') {
            $filtered_logs[] = $log;
        }
    }
} elseif ($filter === 'notifications') {
    foreach ($logs as $log) {
        if (isset($log['type']) && $log['type'] === 'notification') {
            $filtered_logs[] = $log;
        }
    }
} else {
    $filtered_logs = $logs;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MWPD Account Approval Logs</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .page-header {
            background-color: #007bff;
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .nav-links a {
            color: white;
            margin-left: 15px;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .btn-back {
            background-color: #6c757d;
            display: inline-flex;
            align-items: center;
        }
        .card {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }
        .card-body {
            padding: 20px;
        }
        h1, h2, h3 {
            margin-top: 0;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .refresh-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .refresh-btn:hover {
            background-color: #218838;
        }
        .filter-btn {
            margin-right: 5px;
        }
        .filter-btn.active {
            background-color: #007bff;
            color: white;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .badge-approval {
            background-color: #28a745;
            color: white;
        }
        .badge-notification {
            background-color: #17a2b8;
            color: white;
        }
        .badge-approved {
            background-color: #28a745;
            color: white;
        }
        .badge-rejected {
            background-color: #dc3545;
            color: white;
        }
        .credentials-cell {
            background-color: #fff3cd;
            font-family: monospace;
        }
        .actions-column {
            width: 100px;
        }
        .log-row:hover {
            background-color: #f8f9fa;
        }
        .copy-btn {
            cursor: pointer;
            color: #007bff;
        }
        .copy-btn:hover {
            color: #0056b3;
        }
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        .timestamp-col {
            width: 150px;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container nav-container">
            <a href="dashboard.php" class="nav-brand">MWPD Filing System</a>
            <div class="nav-links">
                <a href="account_dashboard.php" class="btn-back"><span>⬅</span> Back to Account Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Account Approval Logs</h2>
                <div>
                    <a href="view_approval_logs.php?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">
                        All Logs
                    </a>
                    <a href="view_approval_logs.php?filter=approvals" class="btn btn-sm <?php echo $filter === 'approvals' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">
                        Approval Emails
                    </a>
                    <a href="view_approval_logs.php?filter=notifications" class="btn btn-sm <?php echo $filter === 'notifications' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">
                        Notification Emails
                    </a>
                    <button onclick="location.reload()" class="btn btn-sm btn-success ml-2">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> This page displays all approval and notification email logs, including account credentials for approved users.
                </div>
                
                <?php if (empty($filtered_logs)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <h4>No logs found</h4>
                    <p>There are no approval logs yet. When you approve new accounts, the credentials will appear here.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="timestamp-col">Date/Time</th>
                                <th>Type</th>
                                <th>Recipient</th>
                                <th>Details</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filtered_logs as $log): ?>
                            <tr class="log-row">
                                <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                <td>
                                    <?php if (isset($log['type']) && $log['type'] === 'approval'): ?>
                                    <span class="badge badge-approval">Account Approval</span>
                                    <?php elseif (isset($log['type']) && $log['type'] === 'notification'): ?>
                                    <span class="badge badge-notification">Notification</span>
                                    <?php if (isset($log['status'])): ?>
                                    <br><span class="badge badge-<?php echo $log['status'] === 'approved' ? 'approved' : 'rejected'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($log['status'])); ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">Other</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($log['to_email'])): ?>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($log['to_email']); ?><br>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($log['type']) && $log['type'] === 'approval' && isset($log['full_name'])): ?>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($log['full_name']); ?>
                                    <?php elseif (isset($log['type']) && $log['type'] === 'notification' && isset($log['submitter_name'])): ?>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($log['submitter_name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($log['type']) && $log['type'] === 'approval'): ?>
                                    <div>
                                        <strong>Username:</strong> <?php echo htmlspecialchars($log['username']); ?>
                                    </div>
                                    <div class="credentials-cell p-2 mt-1 rounded">
                                        <strong>Password:</strong> 
                                        <span id="pwd-<?php echo md5($log['timestamp'] . $log['username']); ?>">
                                            <?php echo htmlspecialchars($log['password']); ?>
                                        </span>
                                    </div>
                                    <?php elseif (isset($log['type']) && $log['type'] === 'notification'): ?>
                                    <div>
                                        <strong>About User:</strong> <?php echo htmlspecialchars($log['user_full_name']); ?> 
                                        (<?php echo htmlspecialchars($log['username']); ?>)
                                    </div>
                                    <div>
                                        <strong>Status:</strong> 
                                        <span class="badge badge-<?php echo $log['status'] === 'approved' ? 'approved' : 'rejected'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($log['status'])); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (isset($log['type']) && $log['type'] === 'approval'): ?>
                                    <button class="btn btn-sm btn-outline-primary copy-btn" 
                                            onclick="copyCredentials('<?php echo htmlspecialchars($log['username']); ?>', '<?php echo htmlspecialchars($log['password']); ?>')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function copyCredentials(username, password) {
        const text = `Username: ${username}\nPassword: ${password}`;
        navigator.clipboard.writeText(text).then(() => {
            alert('Credentials copied to clipboard');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }
    </script>
</body>
</html>
