<?php
/**
 * Regional Director Dashboard
 * Enhanced dashboard with system-wide audit logs and activity tracking
 */
include 'session.php';
require_once 'connection.php';
include 'includes/audit_logger.php';

// Check if user has Regional Director role
if ($_SESSION['role'] !== 'Regional Director') {
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Initialize audit logger
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role']
];
$auditLogger = new AuditLogger($pdo, $user);

// Log the dashboard access
$auditLogger->log('view', 'rd_dashboard', null, ['action' => 'Dashboard access']);

// Process filter form submission
$filters = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_logs'])) {
    if (!empty($_POST['user_id'])) {
        $filters['user_id'] = $_POST['user_id'];
    }
    
    if (!empty($_POST['module'])) {
        $filters['module'] = $_POST['module'];
    }
    
    if (!empty($_POST['action'])) {
        $filters['action'] = $_POST['action'];
    }
    
    if (!empty($_POST['start_date'])) {
        $filters['start_date'] = $_POST['start_date'];
    }
    
    if (!empty($_POST['end_date'])) {
        $filters['end_date'] = $_POST['end_date'];
    }
}

// Default limit for logs
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Get recent activity logs
$activityLogs = $auditLogger->getRecentActivity($filters, $limit, $offset);

// Get users for filter dropdown
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Get module list for filter dropdown
$modules = [
    'direct_hire' => 'Direct Hire',
    'bm' => 'Balik Manggagawa',
    'gov_to_gov' => 'Gov-to-Gov',
    'job_fairs' => 'Job Fairs',
    'info_sheet' => 'Information Sheet',
    'users' => 'User Management',
    'blacklist' => 'Blacklist'
];

// Get action list for filter dropdown
$actions = [
    'create' => 'Create',
    'update' => 'Update',
    'delete' => 'Delete',
    'view' => 'View',
    'export' => 'Export',
    'login' => 'Login',
    'logout' => 'Logout'
];

// Process export request
if (isset($_GET['export']) && $_GET['export'] === 'logs') {
    $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
    
    // Include report exporter
    include 'includes/report_exporter.php';
    $exporter = new ReportExporter($pdo);
    
    try {
        $exportData = $exporter->generateReport('audit_log', $filters, $format);
        
        // Set appropriate headers based on format
        switch ($format) {
            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');
                break;
                
            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.json"');
                break;
                
            case 'excel':
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.xls"');
                break;
                
            case 'pdf':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.pdf"');
                break;
                
            case 'docx':
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.docx"');
                break;
        }
        
        echo $exportData;
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error exporting logs: " . $e->getMessage();
    }
}

$pageTitle = "Regional Director Dashboard - MWPD Filing System";
include '_head.php';
?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="container-fluid">
          <div class="page-header">
            <h1>Regional Director Dashboard</h1>
          </div>
          
          <!-- Error messages -->
          <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
              <?= htmlspecialchars($_SESSION['error_message']) ?>
              <?php unset($_SESSION['error_message']); ?>
            </div>
          <?php endif; ?>
          
          <!-- Success messages -->
          <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
              <?= htmlspecialchars($_SESSION['success_message']) ?>
              <?php unset($_SESSION['success_message']); ?>
            </div>
          <?php endif; ?>
          
          <!-- Filter Form -->
          <div class="card mb-4">
            <div class="card-header">
              <h5>Filter Audit Logs</h5>
            </div>
            <div class="card-body">
              <form method="POST" class="row">
                <div class="col-md-3 mb-3">
                  <label for="user_id">User</label>
                  <select name="user_id" id="user_id" class="form-control">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                      <option value="<?= $user['id'] ?>" <?= isset($filters['user_id']) && $filters['user_id'] == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-3 mb-3">
                  <label for="module">Module</label>
                  <select name="module" id="module" class="form-control">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $key => $label): ?>
                      <option value="<?= $key ?>" <?= isset($filters['module']) && $filters['module'] == $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-3 mb-3">
                  <label for="action">Action</label>
                  <select name="action" id="action" class="form-control">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $key => $label): ?>
                      <option value="<?= $key ?>" <?= isset($filters['action']) && $filters['action'] == $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="col-md-3 mb-3">
                  <label for="start_date">Start Date</label>
                  <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $filters['start_date'] ?? '' ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                  <label for="end_date">End Date</label>
                  <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $filters['end_date'] ?? '' ?>">
                </div>
                
                <div class="col-md-6 mb-3 d-flex align-items-end">
                  <button type="submit" name="filter_logs" class="btn btn-primary mr-2">
                    <i class="fa fa-filter"></i> Apply Filters
                  </button>
                  
                  <a href="rd_dashboard.php" class="btn btn-secondary mr-2">
                    <i class="fa fa-times"></i> Clear Filters
                  </a>
                  
                  <div class="dropdown">
                    <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="fa fa-download"></i> Export
                    </button>
                    <div class="dropdown-menu" aria-labelledby="exportDropdown">
                      <a class="dropdown-item" href="rd_dashboard.php?export=logs&format=csv">CSV</a>
                      <a class="dropdown-item" href="rd_dashboard.php?export=logs&format=excel">Excel</a>
                      <a class="dropdown-item" href="rd_dashboard.php?export=logs&format=pdf">PDF</a>
                      <a class="dropdown-item" href="rd_dashboard.php?export=logs&format=json">JSON</a>
                      <a class="dropdown-item" href="rd_dashboard.php?export=logs&format=docx">DOCX</a>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
          
          <!-- Audit Logs Table -->
          <div class="card">
            <div class="card-header">
              <h5>System Audit Logs</h5>
              <small>Showing <?= count($activityLogs) ?> logs</small>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Timestamp</th>
                      <th>User</th>
                      <th>Action</th>
                      <th>Module</th>
                      <th>Record ID</th>
                      <th>Description</th>
                      <th>IP Address</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($activityLogs)): ?>
                      <tr>
                        <td colspan="7" class="text-center">No audit logs found</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($activityLogs as $log): ?>
                        <tr>
                          <td>
                            <span title="<?= htmlspecialchars($log['created_at']) ?>">
                              <?= htmlspecialchars($log['time_elapsed']) ?>
                            </span>
                          </td>
                          <td>
                            <?= htmlspecialchars($log['full_name'] ?: $log['username']) ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($log['role']) ?></small>
                          </td>
                          <td><span class="badge badge-info"><?= htmlspecialchars($log['action_display']) ?></span></td>
                          <td><?= htmlspecialchars($log['module_display']) ?></td>
                          <td><?= $log['record_id'] ? htmlspecialchars($log['record_id']) : '<em>N/A</em>' ?></td>
                          <td><?= htmlspecialchars($log['activity_description']) ?></td>
                          <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
              
              <!-- Pagination -->
              <?php if (count($activityLogs) >= $limit): ?>
                <div class="mt-3">
                  <a href="rd_dashboard.php?offset=<?= $offset + $limit ?>" class="btn btn-primary btn-sm">
                    Load More <i class="fa fa-arrow-down"></i>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script>
    // Initialize dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
      var dropdownToggle = document.getElementById('exportDropdown');
      if (dropdownToggle) {
        dropdownToggle.addEventListener('click', function() {
          var dropdownMenu = this.nextElementSibling;
          if (dropdownMenu) {
            dropdownMenu.classList.toggle('show');
          }
        });
      }
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!event.target.matches('.dropdown-toggle')) {
          var dropdowns = document.getElementsByClassName('dropdown-menu');
          for (var i = 0; i < dropdowns.length; i++) {
            var dropdown = dropdowns[i];
            if (dropdown.classList.contains('show')) {
              dropdown.classList.remove('show');
            }
          }
        }
      });
    });
  </script>
</body>
</html>
