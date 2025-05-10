<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Account Management Dashboard";
include '_head.php';

// Check if user has Regional Director or Division Head role
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director' &&
    $_SESSION['role'] !== 'div head' && $_SESSION['role'] !== 'Division Head') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Check if user is a Division Head (to restrict certain functionality)
$is_division_head = ($_SESSION['role'] === 'div head' || $_SESSION['role'] === 'Division Head');

// Initialize account statistics
$account_stats = [
    'active_accounts' => 0,
    'pending_approvals' => 0,
    'approved_accounts' => 0,
    'rejected_accounts' => 0
];

$error_message = null;
$recent_activity = [];
$recent_pending = [];
$pending_approvals = [];

// Helper function to get role badge colors
function getRoleBadgeColor($role) {
    switch (strtolower($role)) {
        case 'admin':
            return '#dc3545'; // Red for admin
        case 'regional director':
            return '#17a2b8'; // Teal for regional director
        case 'division head':
            return '#6610f2'; // Purple for division head
        case 'staff':
            return '#28a745'; // Green for staff
        default:
            return '#6c757d'; // Gray for any other role
    }
}

try {
    // Check if users table has the required columns
    $status_column_exists = false;
    $email_column_exists = false;
    
    // Check for status column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    $status_column_exists = $stmt->rowCount() > 0;
    
    // Check for email column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
    $email_column_exists = $stmt->rowCount() > 0;
    
    // If columns are missing, show appropriate message
    if (!$status_column_exists || !$email_column_exists) {
        if (!$status_column_exists && !$email_column_exists) {
            $error_message = "The users table is missing required columns (status and email). <a href='add_status_column.php' style='color: #007bff;'>Click here</a> to fix this.";
        } else if (!$status_column_exists) {
            $error_message = "The users table is missing the status column. <a href='add_status_column.php' style='color: #007bff;'>Click here</a> to add it.";
        } else {
            $error_message = "The users table is missing the email column. <a href='add_email_column.php' style='color: #007bff;'>Click here</a> to add it.";
        }
    }
    
    // Check if the account_approvals table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'account_approvals'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        // Display a message about creating the table
        $error_message = "The account_approvals table does not exist yet. <a href='create_account_approvals_table.php' style='color: #007bff;'>Click here</a> to create it.";
    } else if ($error_message === null) { // Only proceed if no errors so far
        // Count pending approvals
        $stmt = $pdo->query("SELECT COUNT(*) FROM account_approvals WHERE status = 'pending'");
        $account_stats['pending_approvals'] = $stmt->fetchColumn();
        
        // Count approved accounts
        $stmt = $pdo->query("SELECT COUNT(*) FROM account_approvals WHERE status = 'approved'");
        $account_stats['approved_accounts'] = $stmt->fetchColumn();
        
        // Count rejected accounts
        $stmt = $pdo->query("SELECT COUNT(*) FROM account_approvals WHERE status = 'rejected'");
        $account_stats['rejected_accounts'] = $stmt->fetchColumn();
        
        // Get recent activity (approvals/rejections)
        $stmt = $pdo->query("SELECT a.*, 
                             u1.full_name as submitted_by_name,
                             u2.full_name as approved_by_name
                             FROM account_approvals a 
                             LEFT JOIN users u1 ON a.submitted_by = u1.id 
                             LEFT JOIN users u2 ON a.approved_by = u2.id
                             WHERE a.status != 'pending' 
                             ORDER BY a.approved_date DESC LIMIT 5");
        $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent pending approvals
        $stmt = $pdo->query("SELECT a.*, u.full_name as submitted_by_name 
                             FROM account_approvals a 
                             LEFT JOIN users u ON a.submitted_by = u.id 
                             WHERE a.status = 'pending' 
                             ORDER BY a.submitted_date DESC LIMIT 5");
        $recent_pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all pending approvals for the approval tab
        $stmt = $pdo->query("SELECT a.*, u.full_name as submitted_by_name 
                             FROM account_approvals a 
                             LEFT JOIN users u ON a.submitted_by = u.id 
                             WHERE a.status = 'pending' 
                             ORDER BY a.submitted_date DESC");
        $pending_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Count total users (without using the status column)
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $account_stats['active_accounts'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Fetch active users for the users tab
$active_users = [];
if ($active_tab === 'users') {
    try {
        $stmt = $pdo->query("SELECT id, username, full_name, role, email FROM users ORDER BY full_name");
        $active_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error fetching users: " . $e->getMessage();
    }
}

// Handle new user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if username already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Username already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Error checking username: " . $e->getMessage();
    }
    
    // If no errors, add the user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $role, $email]);
            
            $_SESSION['success_message'] = "User added successfully";
            header("Location: account_dashboard.php?tab=users");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error adding user: " . $e->getMessage();
        }
    }
}

?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="account-dashboard-wrapper">
          <div style="margin-bottom: 20px;"></div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>
          
          <!-- Success Modal -->
          <div id="successModal" class="modal" style="display: none;">
            <div class="modal-content" style="border-radius: 8px; max-width: 400px; padding: 0;">
              <div class="modal-header" style="background-color: #28a745; color: white; padding: 15px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                <h2 style="margin: 0; font-size: 18px;">Success</h2>
                <span class="close" data-modal-id="successModal" style="color: white; font-size: 24px;">&times;</span>
              </div>
              <div class="modal-body" style="padding: 20px;">
                <p id="successMessage" style="margin-bottom: 20px;"></p>
                <div style="text-align: right;">
                  <button onclick="document.getElementById('successModal').style.display='none'; window.location.reload();" 
                          style="background-color: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                    OK
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Error Modal -->
          <div id="errorModal" class="modal" style="display: none;">
            <div class="modal-content" style="border-radius: 8px; max-width: 400px; padding: 0;">
              <div class="modal-header" style="background-color: #dc3545; color: white; padding: 15px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                <h2 style="margin: 0; font-size: 18px;">Error</h2>
                <span class="close" data-modal-id="errorModal" style="color: white; font-size: 24px;">&times;</span>
              </div>
              <div class="modal-body" style="padding: 20px;">
                <p id="errorMessage" style="margin-bottom: 20px;"></p>
                <div style="text-align: right;">
                  <button onclick="document.getElementById('errorModal').style.display='none';" 
                          style="background-color: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tabs Navigation -->
          <div class="process-page-top">
            <div class="tabs">
              <a href="?tab=dashboard<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab == 'dashboard' ? 'active' : '' ?>">
                <i class="fa fa-chart-bar"></i> Dashboard
                <?php if ($account_stats['pending_approvals'] > 0): ?>
                <span class="badge"><?= $account_stats['pending_approvals'] ?></span>
                <?php endif; ?>
              </a>
              <?php if (!$is_division_head): ?>
              <a href="?tab=approvals<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab == 'approvals' ? 'active' : '' ?>">
                <i class="fa fa-user-check"></i> Account Approvals
                <?php if ($account_stats['pending_approvals'] > 0): ?>
                <span class="badge"><?= $account_stats['pending_approvals'] ?></span>
                <?php endif; ?>
              </a>
              <?php endif; ?>
              <a href="?tab=users<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab == 'users' ? 'active' : '' ?>">
                <i class="fa fa-users"></i> Active Users
              </a>
            </div>
          </div>
          <style>
            /* Tab navigation styling to match Gov-to-Gov */
            .process-page-top {
              display: flex;
              flex-direction: column;
              gap: 0.5rem;
              margin-bottom: 15px;
            }
            
            .tabs {
              display: flex;
              align-items: center;
              border-bottom: 1px solid #dee2e6;
              margin-bottom: 15px;
            }
            
            .tab {
              padding: 10px 15px;
              text-decoration: none;
              color: #495057;
              font-weight: 500;
              border-radius: 5px 5px 0 0;
              border: 1px solid transparent;
              border-bottom: none;
              margin-right: 5px;
              position: relative;
              display: flex;
              align-items: center;
              gap: 5px;
            }
            
            .tab.active {
              background-color: white;
              color: #007bff;
              border-color: #dee2e6;
              border-bottom-color: white;
              z-index: 1;
            }
            
            .tab:not(.active):hover {
              background-color: #f8f9fa;
            }
            
            .badge {
              background-color: #dc3545;
              color: white;
              font-size: 11px;
              border-radius: 50%;
              width: 18px;
              height: 18px;
              display: inline-flex;
              align-items: center;
              justify-content: center;
            }
          </style>
          <!-- Dashboard Tab -->
          <div class="tab-content <?= $active_tab == 'dashboard' ? 'active' : 'hidden' ?>" style="display: <?= $active_tab == 'dashboard' ? 'block' : 'none' ?>;">
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
              <h1 style="margin: 0; font-size: 24px; color: #333;">Account Management Dashboard</h1>
              <div style="display: flex; gap: 10px;">
                <a href="account_approvals.php?tab=pending" style="display: inline-flex; align-items: center; gap: 5px; background-color: #007bff; color: white; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-weight: 500;">
                  <i class="fa fa-user-check"></i> Account Approvals
                </a>
                <a href="accounts.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-weight: 500;">
                  <i class="fa fa-users"></i> View All Users
                </a>
                <a href="view_approval_logs.php" class="btn btn-primary" style="display: inline-flex; align-items: center;">
                  <i class="fas fa-list-alt mr-2"></i> View Approval Logs
                </a>
              </div>
            </div>

            <?php if (!empty($_SESSION['success_message'])): ?>
              <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
              </div>
              <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error_message'])): ?>
              <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
              </div>
              <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
              <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="card-body" style="padding: 20px;">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 16px; color: #6c757d;">Pending Approvals</h3>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #007bff; color: white; border-radius: 50%;">
                      <i class="fa fa-clock"></i>
                    </span>
                  </div>
                  <div style="font-size: 28px; font-weight: 600; color: #333;"><?= $account_stats['pending_approvals'] ?></div>
                  <a href="account_approvals.php?tab=pending" style="display: inline-block; margin-top: 10px; color: #007bff; text-decoration: none; font-size: 14px;">View all pending approvals</a>
                </div>
              </div>
              
              <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="card-body" style="padding: 20px;">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 16px; color: #6c757d;">Approved Accounts</h3>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #28a745; color: white; border-radius: 50%;">
                      <i class="fa fa-check"></i>
                    </span>
                  </div>
                  <div style="font-size: 28px; font-weight: 600; color: #333;"><?= $account_stats['approved_accounts'] ?></div>
                  <a href="account_approvals.php?tab=approved" style="display: inline-block; margin-top: 10px; color: #28a745; text-decoration: none; font-size: 14px;">View approved accounts</a>
                </div>
              </div>
              
              <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="card-body" style="padding: 20px;">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 16px; color: #6c757d;">Rejected Accounts</h3>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #dc3545; color: white; border-radius: 50%;">
                      <i class="fa fa-times"></i>
                    </span>
                  </div>
                  <div style="font-size: 28px; font-weight: 600; color: #333;"><?= $account_stats['rejected_accounts'] ?></div>
                  <a href="account_approvals.php?tab=rejected" style="display: inline-block; margin-top: 10px; color: #dc3545; text-decoration: none; font-size: 14px;">View rejected accounts</a>
                </div>
              </div>
              
              <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="card-body" style="padding: 20px;">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 16px; color: #6c757d;">Active Users</h3>
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #17a2b8; color: white; border-radius: 50%;">
                      <i class="fa fa-users"></i>
                    </span>
                  </div>
                  <div style="font-size: 28px; font-weight: 600; color: #333;"><?= $account_stats['active_accounts'] ?></div>
                  <a href="?tab=users" style="display: inline-block; margin-top: 10px; color: #17a2b8; text-decoration: none; font-size: 14px;">Manage users</a>
                </div>
              </div>
            </div>
            
            <!-- Recent Activity and Pending Approvals -->
            <div class="content-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px;">
              <!-- Recent Pending Approvals -->
              <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="card-body" style="padding: 20px;">
                  <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #333;"><?= $is_division_head ? 'Recent Account Requests' : 'Recent Pending Approvals' ?></h2>
                  
                  <?php if (empty($recent_pending)): ?>
                    <div style="padding: 20px; text-align: center; color: #6c757d;">
                      No pending approvals
                    </div>
                  <?php else: ?>
                    <div style="overflow-x: auto;">
                      <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                          <tr>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">User</th>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Role</th>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Submitted By</th>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($recent_pending as $index => $approval): ?>
                            <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6; cursor: pointer;" onclick="window.location.href='account_approvals.php?tab=pending'">
                              <td style="padding: 12px 15px; vertical-align: middle;">
                                <div style="font-weight: 500;"><?= htmlspecialchars($approval['full_name']) ?></div>
                                <div style="font-size: 12px; color: #6c757d;"><?= htmlspecialchars($approval['email']) ?></div>
                              </td>
                              <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['role']) ?></td>
                              <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['submitted_by_name']) ?></td>
                              <td style="padding: 12px 15px; vertical-align: middle; white-space: nowrap;"><?= date('M d, Y', strtotime($approval['submitted_date'])) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    
                    <div style="margin-top: 15px; text-align: right;">
                      <?php if (!$is_division_head): ?>
                      <a href="account_approvals.php?tab=pending" style="color: #007bff; text-decoration: none;">View all pending approvals →</a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Recent Activity -->
              <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="card-body" style="padding: 20px;">
                  <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #333;">Recent Account Activity</h2>
                  
                  <?php if (empty($recent_activity)): ?>
                    <div style="padding: 20px; text-align: center; color: #6c757d;">
                      No recent activity
                    </div>
                  <?php else: ?>
                    <div style="overflow-x: auto;">
                      <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                          <tr>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">User</th>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Action</th>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">By</th>
                            <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($recent_activity as $index => $activity): ?>
                            <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6; cursor: pointer;" onclick="window.location.href='view_approval_logs.php'">
                              <td style="padding: 12px 15px; vertical-align: middle;">
                                <div style="font-weight: 500;"><?= htmlspecialchars($activity['full_name']) ?></div>
                                <div style="font-size: 12px; color: #6c757d;"><?= htmlspecialchars($activity['username']) ?></div>
                              </td>
                              <td style="padding: 12px 15px; vertical-align: middle;">
                                <?php if ($activity['status'] === 'approved'): ?>
                                  <span style="display: inline-block; padding: 4px 8px; background-color: #d4edda; color: #155724; border-radius: 4px; font-size: 12px;">Approved</span>
                                <?php else: ?>
                                  <span style="display: inline-block; padding: 4px 8px; background-color: #f8d7da; color: #721c24; border-radius: 4px; font-size: 12px;">Rejected</span>
                                <?php endif; ?>
                              </td>
                              <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($activity['approved_by_name']) ?></td>
                              <td style="padding: 12px 15px; vertical-align: middle; white-space: nowrap;"><?= date('M d, Y', strtotime($activity['approved_date'])) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    
                    <div style="margin-top: 15px; text-align: right;">
                      <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <a href="account_add.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-weight: 500;">
                          <i class="fa fa-plus"></i> Add New User
                        </a>
                        <?php if (!$is_division_head): ?>
                        <a href="view_approval_logs.php" style="display: inline-flex; align-items: center; color: #007bff; text-decoration: none; padding: 8px 0;">
                          View all activity <i class="fa fa-arrow-right" style="margin-left: 5px;"></i>
                        </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Users Tab -->
          <div class="tab-content <?= $active_tab == 'users' ? 'active' : 'hidden' ?>" style="display: <?= $active_tab == 'users' ? 'block' : 'none' ?>;">
            <div class="page-header" style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
              <a href="account_add.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 10px 15px; text-decoration: none; font-weight: 500;">
                <i class="fa fa-plus"></i> Add New User
              </a>
            </div>
            
            <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
              <div class="card-body" style="padding: 20px;">
                <div class="user-table-wrapper" style="overflow-x: auto;">
                  <table class="user-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                      <tr>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Username</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Full Name</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Role</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($active_users as $index => $user): ?>
                        <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6;" class="user-row">
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($user['username']) ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($user['full_name']) ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($user['role']) ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;">
                            <div style="display: flex; gap: 8px;">
                              <a href="account_edit.php?id=<?= $user['id'] ?>" title="Edit User" style="display: inline-flex; align-items: center; color: #28a745; text-decoration: none;">
                                <i class="fa fa-edit" style="font-size: 16px;"></i>
                              </a>
                              <a href="reset_password.php?id=<?= $user['id'] ?>" title="Reset Password" style="display: inline-flex; align-items: center; color: #ffc107; text-decoration: none;">
                                <i class="fa fa-key" style="font-size: 16px;"></i>
                              </a>
                              <a href="account_delete.php?id=<?= $user['id'] ?>" title="Delete User" style="display: inline-flex; align-items: center; color: #dc3545; text-decoration: none;">
                                <i class="fa fa-trash" style="font-size: 16px;"></i>
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                      <?php if (count($active_users) === 0): ?>
                        <tr>
                          <td colspan="4" style="padding: 20px; text-align: center; color: #6c757d;">No users found</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Approvals Tab -->
          <div class="tab-content <?= $active_tab == 'approvals' ? 'active' : 'hidden' ?>" style="display: <?= ($active_tab == 'approvals' && !$is_division_head) ? 'block' : 'none' ?>;">
            <?php if (!$is_division_head): ?>
            <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
              <div class="card-body" style="padding: 20px;">
                <h2 style="margin-top: 0; font-size: 18px; color: #333; margin-bottom: 15px;">
                  Pending Account Approvals
                  <?php if (count($pending_approvals) > 0): ?>
                  <span style="background-color: #007bff; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; font-size: 14px; margin-left: 8px;">
                    <?= count($pending_approvals) ?>
                  </span>
                  <?php endif; ?>
                </h2>
                
                <?php if (count($pending_approvals) > 0): ?>
                <div class="table-responsive" style="margin-bottom: 0;">
                  <table style="width: 100%; border-collapse: collapse; border: 1px solid #dee2e6;">
                    <thead>
                      <tr style="border-bottom: 2px solid #dee2e6;">
                        <th style="padding: 12px 15px; text-align: left;">Username</th>
                        <th style="padding: 12px 15px; text-align: left;">Full Name</th>
                        <th style="padding: 12px 15px; text-align: left;">Role</th>
                        <th style="padding: 12px 15px; text-align: left;">Email</th>
                        <th style="padding: 12px 15px; text-align: left;">Submitted By</th>
                        <th style="padding: 12px 15px; text-align: left;">Date</th>
                        <th style="padding: 12px 15px; text-align: center;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($pending_approvals as $index => $approval): ?>
                       <tr class="<?= $index % 2 == 0 ? 'even' : 'odd' ?>" style="background-color: <?= $index % 2 == 0 ? '#f8f9fa' : 'white' ?>; border-bottom: 1px solid #dee2e6;" data-approval-id="<?= $approval['id'] ?>" data-username="<?= htmlspecialchars($approval['username']) ?>" data-fullname="<?= htmlspecialchars($approval['full_name']) ?>">
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['username']) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['full_name']) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['role']) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['email'] ?: 'No email') ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['submitted_by_name'] ?: 'Unknown') ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= date('M d, Y', strtotime($approval['submitted_date'])) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;">
                          <div style="display: flex; gap: 8px; justify-content: center;">
                            <?php if (!$is_division_head): ?>
                            <button type="button" onclick="showApproveModal(<?= $approval['id'] ?>, '<?= htmlspecialchars(addslashes($approval['username'])) ?>', '<?= htmlspecialchars(addslashes($approval['full_name'])) ?>')" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer;">
                              <i class="fas fa-check"></i> Approve
                            </button>
                            <button type="button" onclick="showDenyModal(<?= $approval['id'] ?>, '<?= htmlspecialchars(addslashes($approval['username'])) ?>', '<?= htmlspecialchars(addslashes($approval['full_name'])) ?>')" style="display: inline-flex; align-items: center; gap: 5px; background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer;">
                              <i class="fas fa-times"></i> Deny
                            </button>
                            <?php else: ?>
                            <span class="badge" style="background-color: #6c757d; color: white; padding: 6px 12px; border-radius: 4px;">Pending Approval</span>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info" style="background-color: #d1ecf1; color: #0c5460; padding: 12px 15px; border-radius: 4px; margin-bottom: 0; border-left: 4px solid #17a2b8;">
                  <p style="margin: 0;"><i class="fa fa-info-circle"></i> There are no pending account approvals at this time.</p>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Approve Modal -->
        <div id="approveModal" class="modal">
          <div class="modal-content">
            <div class="modal-header">
              <h2>Approve Account Request</h2>
              <span class="close" data-modal-id="approveModal">&times;</span>
            </div>
            <div class="modal-body">
              <form id="approveForm" action="process_account_approval.php" method="post">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" id="approve_approval_id" name="approval_id" value="">
                <input type="hidden" name="no_redirect" value="1">
                <p>Are you sure you want to approve the account request for <strong id="approve_user_name"></strong>?</p>
                <p><small>This will create a new user account and send access credentials to the email address provided.</small></p>
                <div class="form-group">
                  <label for="notes">Notes (optional):</label>
                  <textarea class="form-control" name="notes" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                  <button type="button" class="close-modal btn btn-secondary" data-modal-id="approveModal">Cancel</button>
                  <button type="submit" class="btn btn-success" onclick="submitApproval(); return false;">Approve</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        
        <!-- Deny Modal -->
        <div id="denyModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
          <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; border-radius: 8px; width: 50%; max-width: 500px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <div class="modal-header" style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
              <h2 style="margin: 0; font-size: 20px; color: #333;">Deny Account</h2>
              <span class="close" data-modal-id="denyModal" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            </div>
            <form id="denyForm" method="POST" action="process_account_approval.php">
              <input type="hidden" name="approval_id" id="deny_approval_id">
              <input type="hidden" name="action" value="reject">
              
              <p style="margin-bottom: 20px;">Are you sure you want to deny the account for <span id="deny_user_name" style="font-weight: bold;"></span>?</p>
              
              <div class="form-group" style="margin-bottom: 15px;">
                <label for="deny_reason" style="display: block; margin-bottom: 5px; font-weight: 500;">Reason for Denial (Required):</label>
                <textarea id="deny_reason" name="reason" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px; min-height: 80px;" required></textarea>
              </div>
              
              <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="close-modal" data-modal-id="denyModal" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer;">Cancel</button>
                <button type="submit" style="background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer;" onclick="submitDenial(); return false;">
                  <i class="fas fa-times"></i> Deny
                </button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </div>
  
  <!-- Approve Account Modal -->
  <div id="approveAccountModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; overflow: auto;">
    <div class="modal-content" style="background-color: white; margin: 15% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); position: relative;">
      <span class="close" onclick="closeModals()" style="position: absolute; top: 10px; right: 15px; font-size: 24px; font-weight: bold; cursor: pointer;">&times;</span>
      <h2 style="margin-top: 0; color: #333; font-size: 20px;">Approve Account</h2>
      <p>Are you sure you want to approve the account for <strong id="approveUsername"></strong>?</p>
      <p>Full name: <span id="approveFullname"></span></p>
      <form method="POST" action="process_account_approval.php" style="margin-top: 20px;">
        <input type="hidden" name="approval_id" id="approveId" value="">
        <input type="hidden" name="action" value="approve">
        <div style="text-align: right; margin-top: 20px;">
          <button type="button" onclick="closeModals()" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 16px; margin-right: 10px; cursor: pointer;">Cancel</button>
          <button type="submit" style="background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 8px 16px; cursor: pointer;">Confirm Approval</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Deny Account Modal -->
  <div id="denyAccountModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; overflow: auto;">
    <div class="modal-content" style="background-color: white; margin: 15% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); position: relative;">
      <span class="close" onclick="closeModals()" style="position: absolute; top: 10px; right: 15px; font-size: 24px; font-weight: bold; cursor: pointer;">&times;</span>
      <h2 style="margin-top: 0; color: #333; font-size: 20px;">Deny Account</h2>
      <p>Are you sure you want to deny the account for <strong id="denyUsername"></strong>?</p>
      <p>Full name: <span id="denyFullname"></span></p>
      <form method="POST" action="process_account_approval.php" id="denyForm" onsubmit="return validateDenialForm(this);">
        <input type="hidden" name="approval_id" id="denyId" value="">
        <input type="hidden" name="action" value="reject">
        <div class="form-group" style="margin-top: 15px;">
          <label for="rejection_reason" style="display: block; margin-bottom: 5px; font-weight: 500;">Reason for Denial <span style="color: red;">*</span></label>
          <textarea name="rejection_reason" id="rejection_reason" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;" placeholder="Please provide a reason for denying this account"></textarea>
          <p id="reasonError" style="color: #dc3545; font-size: 14px; margin-top: 5px; display: none;">Please provide a reason for the denial</p>
        </div>
        <div style="text-align: right; margin-top: 20px;">
          <button type="button" onclick="closeModals()" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 16px; margin-right: 10px; cursor: pointer;">Cancel</button>
          <button type="submit" style="background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 8px 16px; cursor: pointer;">Confirm Denial</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showApproveModal(id, username, fullname) {
      document.getElementById('approveId').value = id;
      document.getElementById('approveUsername').textContent = username;
      document.getElementById('approveFullname').textContent = fullname;
      document.getElementById('approveAccountModal').style.display = 'block';
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function showDenyModal(id, username, fullname) {
      // Log the values to ensure they're being passed correctly
      console.log('Opening deny modal with ID:', id, 'Username:', username, 'Fullname:', fullname);
      
      // Make sure the ID is set properly
      var denyIdField = document.getElementById('denyId');
      denyIdField.value = id;
      console.log('Set denyId value to:', denyIdField.value);
      
      // Update modal content
      document.getElementById('denyUsername').textContent = username;
      document.getElementById('denyFullname').textContent = fullname;
      
      // Reset previous form state
      document.getElementById('reasonError').style.display = 'none';
      document.getElementById('rejection_reason').value = '';
      
      // Show the modal
      document.getElementById('denyAccountModal').style.display = 'block';
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function closeModals() {
      document.getElementById('approveAccountModal').style.display = 'none';
      document.getElementById('denyAccountModal').style.display = 'none';
      document.body.style.overflow = ''; // Re-enable scrolling
    }

    function validateDenialForm(form) {
      console.log('Validating denial form');
      var reason = document.getElementById('rejection_reason').value.trim();
      if (reason === '') {
        document.getElementById('reasonError').style.display = 'block';
        return false;
      }
      console.log('Denial form validation passed, submitting form with ID:', document.getElementById('denyId').value);
      return true;
    }
    
    function submitDenyForm() {
      var reason = document.getElementById('rejection_reason').value.trim();
      if (reason === '') {
        document.getElementById('reasonError').style.display = 'block';
        return false;
      }
      
      // Hide the error message if validation passes
      document.getElementById('reasonError').style.display = 'none';
      
      // Get the approval ID
      var approvalId = document.getElementById('denyId').value;
      console.log('Denial for approval ID:', approvalId);
      
      // Create a form data object
      var formData = new FormData();
      formData.append('approval_id', approvalId);
      formData.append('action', 'reject');
      formData.append('rejection_reason', reason);
      formData.append('no_redirect', '1'); // For AJAX handling
      
      // Disable the button and show loading state
      var submitBtn = document.querySelector('#denyFormWrapper button[type="button"]:last-child');
      var originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
      submitBtn.disabled = true;
      
      // For debugging, show what we're submitting
      console.log('Submitting denial with reason:', reason);
      
      // Send the AJAX request
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'process_account_approval.php', true);
      xhr.onload = function() {
        if (xhr.status === 200) {
          console.log('Server response:', xhr.responseText);
          try {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
              // Show success message
              console.log('Denial successful:', response.message);
              document.getElementById('successMessage').textContent = response.message || 'Account denied successfully.';
              document.getElementById('successModal').style.display = 'block';
              
              // Close the denial modal
              closeModals();
              
              // Reload the page after a delay
              setTimeout(function() {
                window.location.reload();
              }, 1500);
            } else {
              // Show error message
              console.error('Denial failed:', response.message);
              document.getElementById('errorMessage').textContent = response.message || 'An error occurred during account denial.';
              document.getElementById('errorModal').style.display = 'block';
            }
          } catch (e) {
            console.error('Error parsing JSON response:', e, 'Raw response:', xhr.responseText);
            document.getElementById('errorMessage').textContent = 'Error parsing server response: ' + e.message;
            document.getElementById('errorModal').style.display = 'block';
          }
        } else {
          // HTTP error
          console.error('HTTP error:', xhr.status, xhr.statusText);
          document.getElementById('errorMessage').textContent = 'HTTP error ' + xhr.status + ' occurred during account denial.';
          document.getElementById('errorModal').style.display = 'block';
        }
        
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      };
      
      xhr.onerror = function() {
        console.error('Network error occurred');
        document.getElementById('errorMessage').textContent = 'A network error occurred during account denial.';
        document.getElementById('errorModal').style.display = 'block';
        
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      };
      
      // Send the request
      xhr.send(formData);
      console.log('Request sent to process_account_approval.php');
    }
    
    // Override any existing prompt-based validation
    var oldValidateDenyPrompt = window.validateDenyForm;
    window.validateDenyForm = function(form) {
      // Only use our modal validation, no browser prompts
      return true;
    };

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      var approveModal = document.getElementById('approveAccountModal');
      var denyModal = document.getElementById('denyAccountModal');
      if (event.target === approveModal || event.target === denyModal) {
        closeModals();
      }
    };
  </script>
</body>
</html>

<script>
  // Modal functions
  function showApproveModal(approvalId, username, fullName) {
    document.getElementById('approve_approval_id').value = approvalId;
    document.getElementById('approve_user_name').textContent = fullName + ' (' + username + ')';
    document.getElementById('approveModal').style.display = 'block';
  }
  
  function showDenyModal(approvalId, username, fullName) {
    document.getElementById('deny_approval_id').value = approvalId;
    document.getElementById('deny_user_name').textContent = fullName + ' (' + username + ')';
    document.getElementById('denyModal').style.display = 'block';
  }
  
  // Add double-click functionality to approval table rows
  document.addEventListener('DOMContentLoaded', function() {
    const approvalRows = document.querySelectorAll('.tab-content[class*="approvals"] tbody tr');
    
    approvalRows.forEach(function(row) {
      row.style.cursor = 'pointer';
      
      row.addEventListener('dblclick', function(e) {
        // Don't trigger if clicking on buttons or checkboxes
        if (e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT' || 
            e.target.tagName === 'I' || e.target.closest('button') || e.target.closest('input')) {
          return;
        }
        
        const approvalId = this.getAttribute('data-approval-id');
        const username = this.getAttribute('data-username');
        const fullName = this.getAttribute('data-fullname');
        
        if (approvalId && username && fullName) {
          showApproveModal(approvalId, username, fullName);
        }
      });
    });
  });
  
  // Close modal when clicking on X or Cancel
  document.querySelectorAll('.close, .close-modal').forEach(function(element) {
    element.addEventListener('click', function() {
      const modalId = this.getAttribute('data-modal-id');
      document.getElementById(modalId).style.display = 'none';
    });
  });
  
  // Close modal when clicking outside of it
  window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
      event.target.style.display = 'none';
    }
  }
  
  // Handle form submissions via AJAX
  document.addEventListener('DOMContentLoaded', function() {
    // Setup form submission handlers
    const approveForm = document.getElementById('approveForm');
    const denyForm = document.getElementById('denyForm');
    
    // Handle approve form submission
    if (approveForm) {
      approveForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show a loading indicator
        const submitBtn = approveForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        // Prepare form data
        const formData = new FormData(approveForm);
        formData.append('no_redirect', '1');
        
        // Send AJAX request
        fetch('process_account_approval.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          // Hide the modal regardless of success
          document.getElementById('approveModal').style.display = 'none';
          
          if (data && data.success) {
            // Show success message
            document.getElementById('successMessage').textContent = data.message || 'Account approved successfully!';
            document.getElementById('successModal').style.display = 'block';
            
            // Reload the page to refresh the data
            //window.location.reload();
          } else {
            // Show error message
            document.getElementById('errorMessage').textContent = data.message || 'An error occurred during account approval.';
            document.getElementById('errorModal').style.display = 'block';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          // Show error message
          document.getElementById('errorMessage').textContent = 'An error occurred during account approval.';
          document.getElementById('errorModal').style.display = 'block';
        })
        .finally(() => {
          // Reset button
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
      });
    }
    
    // Handle deny form submission
    if (denyForm) {
      denyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show a loading indicator
        const submitBtn = denyForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        // Prepare form data
        const formData = new FormData(denyForm);
        formData.append('no_redirect', '1');
        
        // Send AJAX request
        fetch('process_account_approval.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          // Hide the modal regardless of success
          document.getElementById('denyModal').style.display = 'none';
          
          if (data && data.success) {
            // Show success message
            document.getElementById('successMessage').textContent = data.message || 'Account rejected successfully!';
            document.getElementById('successModal').style.display = 'block';
            
            // Reload the page to refresh the data
            //window.location.reload();
          } else {
            // Show error message
            document.getElementById('errorMessage').textContent = data.message || 'An error occurred during account rejection.';
            document.getElementById('errorModal').style.display = 'block';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          // Show error message
          document.getElementById('errorMessage').textContent = 'An error occurred during account rejection.';
          document.getElementById('errorModal').style.display = 'block';
        })
        .finally(() => {
          // Reset button
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
      });
    }
    
    // Add double-click functionality to approval rows
    const approvalRows = document.querySelectorAll('[data-approval-id]');
    
    approvalRows.forEach(function(row) {
      row.addEventListener('dblclick', function(e) {
        // Don't trigger if clicked on a button or link
        if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.tagName === 'I') {
          return;
        }
        
        const approvalId = this.getAttribute('data-approval-id');
        const username = this.getAttribute('data-username');
        const fullName = this.getAttribute('data-fullname');
        
        // Show approve modal on double-click
        if (typeof showApproveModal === 'function') {
          showApproveModal(approvalId, username, fullName);
        }
      });
    });
    
    // Add double-click functionality to user rows in the users tab
    const userRows = document.querySelectorAll('.user-row');
    
    userRows.forEach(row => {
      row.addEventListener('dblclick', function(e) {
        // Make sure we're not clicking on a button or link
        if (e.target.tagName.toLowerCase() !== 'button' && 
            e.target.tagName.toLowerCase() !== 'a' && 
            e.target.tagName.toLowerCase() !== 'i' &&
            !e.target.closest('button') && 
            !e.target.closest('a')) {
          
          // Get the edit link for this row
          const editLink = this.querySelector('a[href*="account_edit.php"]');
          if (editLink) {
            window.location.href = editLink.getAttribute('href');
          }
        }
      });
    });
    
    // Change cursor to pointer to indicate clickable
      row.style.cursor = 'pointer';
    });
  });
  // Simple direct function to handle approval form submission
function submitApproval() {
  // Get the form and button
  const form = document.getElementById('approveForm');
  const submitBtn = form.querySelector('button[type="submit"]');
  
  // Show processing state
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  submitBtn.disabled = true;
  
  // Create form data with required parameters
  const formData = new FormData(form);
  formData.append('no_redirect', '1');
  
  // Use vanilla AJAX for maximum compatibility
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'process_account_approval.php', true);
  
  xhr.onload = function() {
    // Hide the modal regardless of success
    document.getElementById('approveModal').style.display = 'none';
    
    if (xhr.status >= 200 && xhr.status < 300) {
      try {
        // Try to parse as JSON
        const response = JSON.parse(xhr.responseText);
        if (response && response.success) {
          // Show success message
          document.getElementById('successMessage').textContent = response.message || 'Account approved successfully!';
          document.getElementById('successModal').style.display = 'block';
          
          // Reload the page to refresh the data
          setTimeout(function() {
            window.location.reload();
          }, 1500);
        } else {
          // Show error message
          document.getElementById('errorMessage').textContent = response.message || 'An error occurred during account approval.';
          document.getElementById('errorModal').style.display = 'block';
        }
      } catch (e) {
        // If not JSON, just show generic error
        document.getElementById('errorMessage').textContent = 'An error occurred during account approval.';
        document.getElementById('errorModal').style.display = 'block';
      }
    } else {
      // Error handling
      document.getElementById('errorMessage').textContent = 'An error occurred during account approval.';
      document.getElementById('errorModal').style.display = 'block';
    }
    
    // Reset button
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  };
  
  xhr.onerror = function() {
    // Show error message
    document.getElementById('errorMessage').textContent = 'An error occurred during account approval.';
    document.getElementById('errorModal').style.display = 'block';
    
    // Reset button
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  };
  
  // Send the form data
  xhr.send(formData);
}

// Simple direct function to handle denial form submission
function submitDenial() {
  // Get the form and button
  const form = document.getElementById('denyForm');
  const submitBtn = form.querySelector('button[type="submit"]');
  
  // Show processing state
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  submitBtn.disabled = true;
  
  // Create form data with required parameters
  const formData = new FormData(form);
  formData.append('no_redirect', '1');
  
  // Use vanilla AJAX for maximum compatibility
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'process_account_approval.php', true);
  
  xhr.onload = function() {
    // Hide the modal regardless of success
    document.getElementById('denyModal').style.display = 'none';
    
    if (xhr.status >= 200 && xhr.status < 300) {
      try {
        // Try to parse as JSON
        const response = JSON.parse(xhr.responseText);
        if (response && response.success) {
          // Show success message using alert as fallback if modal elements don't exist
          if (document.getElementById('successMessage')) {
            document.getElementById('successMessage').textContent = response.message || 'Account denied successfully!';
            document.getElementById('successModal').style.display = 'block';
          } else {
            alert(response.message || 'Account denied successfully!');
          }
          
          // Reload the page to refresh the data
          setTimeout(function() {
            window.location.reload();
          }, 1500);
        } else {
          // Show error message
          if (document.getElementById('errorMessage')) {
            document.getElementById('errorMessage').textContent = response.message || 'An error occurred during account denial.';
            document.getElementById('errorModal').style.display = 'block';
          } else {
            alert(response.message || 'An error occurred during account denial.');
          }
        }
      } catch (e) {
        console.error('Error parsing JSON response:', e);
        if (document.getElementById('errorMessage')) {
          document.getElementById('errorMessage').textContent = 'An error occurred during account denial.';
          document.getElementById('errorModal').style.display = 'block';
        } else {
          alert('An error occurred during account denial.');
        }
      }
    } else {
      // HTTP error
      if (document.getElementById('errorMessage')) {
        document.getElementById('errorMessage').textContent = 'HTTP error ' + xhr.status + ' occurred during account denial.';
        document.getElementById('errorModal').style.display = 'block';
      } else {
        alert('HTTP error ' + xhr.status + ' occurred during account denial.');
      }
    }
    
    // Reset button
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  };
  
  xhr.onerror = function() {
    console.error('Network error occurred');
    // Show error message
    if (document.getElementById('errorMessage')) {
      document.getElementById('errorMessage').textContent = 'A network error occurred during account denial.';
      document.getElementById('errorModal').style.display = 'block';
    } else {
      alert('A network error occurred during account denial.');
    }
    
    // Reset button
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  };
  // Send the form data
  xhr.send(formData);
}

// Completely remove old validation system
// Make sure our custom modals are the only ones that show up
document.addEventListener('DOMContentLoaded', function() {
  // This globally prevents any old validateDenyForm functions
  window.validateDenyForm = function() {
    // Always return true so the form doesn't submit
    // Our modals will handle the actual submission
    return true;
  };
});
</script>

<!-- Success Modal -->
<div id="successModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
  <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; border-radius: 8px; width: 50%; max-width: 500px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
    <div class="modal-header" style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
      <h2 style="margin: 0; font-size: 20px; color: #333;">Success</h2>
      <span class="close" data-modal-id="successModal" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
    </div>
    <div class="modal-body">
      <p id="successMessage" style="margin-bottom: 20px;">Action completed successfully!</p>
    </div>
  </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
  <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; border-radius: 8px; width: 50%; max-width: 500px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
    <div class="modal-header" style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
      <h2 style="margin: 0; font-size: 20px; color: #333;">Error</h2>
      <span class="close" data-modal-id="errorModal" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
    </div>
    <div class="modal-body">
      <p id="errorMessage" style="margin-bottom: 20px; color: #dc3545;">An error occurred. Please try again.</p>
    </div>
  </div>
</div>
