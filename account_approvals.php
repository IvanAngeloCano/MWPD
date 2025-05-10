<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Account Approval Requests";
include '_head.php';

// Check if user has Regional Director role
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Get pending approval requests
$pending_approvals = [];
$approved_requests = [];
$rejected_requests = [];

try {
    // First, check if the account_approvals table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'account_approvals'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        // Table doesn't exist, set error message
        $error_message = "The account_approvals table does not exist yet. <a href='create_account_approvals_table.php' style='color: #007bff;'>Click here</a> to create it.";
    } else {
        // Get pending approvals
        $stmt = $pdo->query("SELECT a.*, u.full_name as submitted_by_name 
                             FROM account_approvals a 
                             LEFT JOIN users u ON a.submitted_by = u.id 
                             WHERE a.status = 'pending' 
                             ORDER BY a.submitted_date DESC");
        $pending_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get approved requests
        $stmt = $pdo->query("SELECT a.*, 
                             u1.full_name as submitted_by_name,
                             u2.full_name as approved_by_name
                             FROM account_approvals a 
                             LEFT JOIN users u1 ON a.submitted_by = u1.id 
                             LEFT JOIN users u2 ON a.approved_by = u2.id
                             WHERE a.status = 'approved' 
                             ORDER BY a.approved_date DESC");
        $approved_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get rejected requests
        $stmt = $pdo->query("SELECT a.*, 
                             u1.full_name as submitted_by_name,
                             u2.full_name as approved_by_name
                             FROM account_approvals a 
                             LEFT JOIN users u1 ON a.submitted_by = u1.id 
                             LEFT JOIN users u2 ON a.approved_by = u2.id
                             WHERE a.status = 'rejected' 
                             ORDER BY a.approved_date DESC");
        $rejected_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Get active tab from query string, default to "pending"
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

// Email function to send credentials
function sendAccountCredentialsEmail($to, $username, $password, $full_name) {
    $subject = "MWPD System - Your Account Credentials";
    
    $message = "
    <html>
    <head>
        <title>MWPD System - Your Account Credentials</title>
    </head>
    <body>
        <p>Dear $full_name,</p>
        <p>Your account for the MWPD System has been approved. Here are your login credentials:</p>
        <p><strong>Username:</strong> $username</p>
        <p><strong>Password:</strong> $password</p>
        <p>For security reasons, please change your password after your first login.</p>
        <p>Thank you,</p>
        <p>MWPD Administration</p>
    </body>
    </html>
    ";
    
    // Set content-type header
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@mwpd.gov.ph" . "\r\n";
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="account-approval-wrapper">
          <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0; font-size: 24px; color: #333;">Account Approval Requests</h1>
            <a href="account_dashboard.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-weight: 500;">
              <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
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

          <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <div class="card-body" style="padding: 20px;">
              <!-- Tabs Navigation -->
              <div style="display: flex; gap: 0.5rem; margin-bottom: 20px;" class="process-page-top">
                <div style="display: flex; gap: 0.5rem;" class="tabs">
                  <a href="?tab=pending" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $activeTab === 'pending' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $activeTab === 'pending' ? 'white' : 'inherit' ?>;">
                    <i class="fa fa-clock" style="margin-right: 5px;"></i> Pending
                    <?php if (count($pending_approvals) > 0): ?>
                      <span style="display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; background-color: <?= $activeTab === 'pending' ? 'white' : '#246EE9' ?>; color: <?= $activeTab === 'pending' ? '#246EE9' : 'white' ?>; border-radius: 50%; width: 20px; height: 20px; font-size: 12px;"><?= count($pending_approvals) ?></span>
                    <?php endif; ?>
                  </a>
                  <a href="?tab=approved" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $activeTab === 'approved' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $activeTab === 'approved' ? 'white' : 'inherit' ?>;">
                    <i class="fa fa-check-circle" style="margin-right: 5px;"></i> Approved
                    <?php if (count($approved_requests) > 0): ?>
                      <span style="display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; background-color: <?= $activeTab === 'approved' ? 'white' : '#246EE9' ?>; color: <?= $activeTab === 'approved' ? '#246EE9' : 'white' ?>; border-radius: 50%; width: 20px; height: 20px; font-size: 12px;"><?= count($approved_requests) ?></span>
                    <?php endif; ?>
                  </a>
                  <a href="?tab=rejected" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $activeTab === 'rejected' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $activeTab === 'rejected' ? 'white' : 'inherit' ?>;">
                    <i class="fa fa-times-circle" style="margin-right: 5px;"></i> Rejected
                    <?php if (count($rejected_requests) > 0): ?>
                      <span style="display: inline-flex; align-items: center; justify-content: center; margin-left: 5px; background-color: <?= $activeTab === 'rejected' ? 'white' : '#246EE9' ?>; color: <?= $activeTab === 'rejected' ? '#246EE9' : 'white' ?>; border-radius: 50%; width: 20px; height: 20px; font-size: 12px;"><?= count($rejected_requests) ?></span>
                    <?php endif; ?>
                  </a>
                </div>
              </div>
              <?php if ($activeTab === 'pending'): ?>
                <?php if (empty($pending_approvals)): ?>
                  <div style="padding: 20px; text-align: center; color: #6c757d;">
                    No pending account approval requests
                  </div>
                <?php else: ?>
                  <div class="approval-table-wrapper" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                      <thead>
                        <tr>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Username</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Full Name</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Email</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Role</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Submitted By</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date Submitted</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($pending_approvals as $index => $approval): ?>
                          <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['username']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['full_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['email']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['role']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['submitted_by_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= date('M d, Y', strtotime($approval['submitted_date'])) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;">
                              <div style="display: flex; gap: 8px;">
                                <button onclick="showApproveModal(<?= $approval['id'] ?>, '<?= htmlspecialchars(addslashes($approval['username'])) ?>', '<?= htmlspecialchars(addslashes($approval['full_name'])) ?>')" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px;">
                                  <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="showRejectModal(<?= $approval['id'] ?>, '<?= htmlspecialchars(addslashes($approval['username'])) ?>', '<?= htmlspecialchars(addslashes($approval['full_name'])) ?>')" style="display: inline-flex; align-items: center; gap: 5px; background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px;">
                                  <i class="fas fa-times"></i> Reject
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              <?php elseif ($activeTab === 'approved'): ?>
                <?php if (empty($approved_requests)): ?>
                  <div style="padding: 20px; text-align: center; color: #6c757d;">
                    No approved account requests
                  </div>
                <?php else: ?>
                  <div class="approval-table-wrapper" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                      <thead>
                        <tr>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Username</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Full Name</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Email</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Role</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Submitted By</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Approved By</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date Approved</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($approved_requests as $index => $request): ?>
                          <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['username']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['full_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['email']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['role']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['submitted_by_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['approved_by_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= date('M d, Y', strtotime($request['approved_date'])) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              <?php elseif ($activeTab === 'rejected'): ?>
                <?php if (empty($rejected_requests)): ?>
                  <div style="padding: 20px; text-align: center; color: #6c757d;">
                    No rejected account requests
                  </div>
                <?php else: ?>
                  <div class="approval-table-wrapper" style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                      <thead>
                        <tr>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Username</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Full Name</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Email</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Role</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Submitted By</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Rejected By</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date Rejected</th>
                          <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Reason</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($rejected_requests as $index => $request): ?>
                          <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['username']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['full_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['email']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['role']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['submitted_by_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['approved_by_name']) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= date('M d, Y', strtotime($request['approved_date'])) ?></td>
                            <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($request['rejection_reason']) ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Approve Modal -->
  <div id="approveModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
      <h3 style="margin-top: 0;">Approve User Account</h3>
      <p>Are you sure you want to approve this user account?</p>
      <p id="approveUserName" style="font-weight: bold;"></p>
      
      <form id="approveForm" action="process_account_approval.php" method="POST">
        <input type="hidden" name="action" value="approve">
        <input type="hidden" name="approval_id" id="approveId">
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" class="close-modal" data-modal-id="approveModal" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 12px; cursor: pointer;">Cancel</button>
          <button type="submit" style="background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 8px 12px; cursor: pointer;">Approve</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Reject Modal -->
  <div id="rejectModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
      <h3 style="margin-top: 0;">Reject User Account</h3>
      <p>Are you sure you want to reject this user account?</p>
      <p id="rejectUserName" style="font-weight: bold;"></p>
      
      <form id="rejectForm" action="process_account_approval.php" method="POST">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" name="approval_id" id="rejectId">
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: 500;">Reason for Rejection</label>
          <textarea name="rejection_reason" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 4px;" required></textarea>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" class="close-modal" data-modal-id="rejectModal" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 12px; cursor: pointer;">Cancel</button>
          <button type="submit" style="background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 8px 12px; cursor: pointer;">Reject</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showApproveModal(id, username, fullName) {
      document.getElementById('approveId').value = id;
      document.getElementById('approveUserName').textContent = fullName + ' (' + username + ')';
      document.getElementById('approveModal').style.display = 'block';
    }
    
    function showRejectModal(id, username, fullName) {
      document.getElementById('rejectId').value = id;
      document.getElementById('rejectUserName').textContent = fullName + ' (' + username + ')';
      document.getElementById('rejectModal').style.display = 'block';
    }
    
    // Close modal when clicking close button
    document.addEventListener('DOMContentLoaded', function() {
      const closeButtons = document.querySelectorAll('.close-modal');
      closeButtons.forEach(button => {
        button.addEventListener('click', function() {
          const modalId = this.getAttribute('data-modal-id');
          document.getElementById(modalId).style.display = 'none';
        });
      });
      
      // Close modal when clicking outside the modal
      window.addEventListener('click', function(event) {
        if (event.target.id === 'approveModal') {
          document.getElementById('approveModal').style.display = 'none';
        }
        if (event.target.id === 'rejectModal') {
          document.getElementById('rejectModal').style.display = 'none';
        }
      });
    });
  </script>

  <script>
    // Add double-click functionality to table rows in the pending tab
    document.addEventListener('DOMContentLoaded', function() {
      const pendingTableRows = document.querySelectorAll('.approval-table tbody tr');
      
      pendingTableRows.forEach(row => {
        row.style.cursor = 'pointer';
        
        row.addEventListener('dblclick', function(e) {
          // Make sure we're not clicking on a button or link
          if (e.target.tagName.toLowerCase() !== 'button' && 
              e.target.tagName.toLowerCase() !== 'a' && 
              e.target.tagName.toLowerCase() !== 'i' &&
              !e.target.closest('button') && 
              !e.target.closest('a')) {
              
            // Get the approve or reject button based on which tab is active
            const activeTab = '<?= $activeTab ?>';
            
            if (activeTab === 'pending') {
              // Get the first button (approve) and trigger its click
              const approveButton = this.querySelector('button');
              if (approveButton) {
                approveButton.click();
              }
            }
          }
        });
      });
    });
  </script>
</body>
</html>
