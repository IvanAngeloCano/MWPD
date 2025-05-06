<?php
require_once 'session.php';
require_once 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';
$error_message = null;
$success_message = null;
$blacklist_records = [];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Check if user has access to this page
$allowed_roles = ['admin', 'regional director', 'division head', 'staff'];

// Set access level - Regional Director can approve/reject, others can only submit
$can_approve = (strtolower($user_role) === 'regional director');

// Block unauthorized users
if (!in_array(strtolower($user_role), $allowed_roles)) {
    header('Location: index.php');
    exit();
}

// Staff can submit but can't view the dashboard
if (strtolower($user_role) === 'staff' && empty($_GET['action'])) {
    header('Location: blacklist.php?action=add');
    exit();
}

// Handle form-only view for staff
$form_only = (isset($_GET['action']) && $_GET['action'] === 'add');

// Handle session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

try {
    // Check if the blacklist table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        $error_message = "The blacklist table does not exist yet. <a href='create_blacklist_table.php' style='color: #007bff;'>Click here</a> to create it.";
    } else {
        // Get blacklist records based on tab and search query
        $where_conditions = [];
        $params = [];
        
        // Filter by status based on the active tab
        if ($active_tab == 'pending') {
            $where_conditions[] = "status = 'pending'";
        } elseif ($active_tab == 'blacklisted' || $active_tab == 'approved') {
            $where_conditions[] = "status = 'approved'";
        }
        
        // Add search query if provided
        if (!empty($search_query)) {
            $where_conditions[] = "(full_name LIKE ? OR passport_number LIKE ? OR email LIKE ? OR phone LIKE ?)"; 
            array_push($params, "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%");
        }
        
        // Build the SQL query
        $sql = "SELECT b.*, 
                (SELECT username FROM users WHERE id = b.submitted_by) as submitted_by_name,
                (SELECT username FROM users WHERE id = b.approved_by) as approved_by_name
                FROM blacklist b";
        
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $blacklist_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Function to get status badge color
function getStatusBadgeColor($status) {
    switch ($status) {
        case 'pending':
            return '#ffc107'; // yellow
        case 'approved':
            return '#28a745'; // green
        case 'rejected':
            return '#dc3545'; // red
        default:
            return '#6c757d'; // gray
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_head.php'; ?>
  <title>Blacklist Management - MWPD</title>
  <style>
    /* Table styling */
    .balik-mangagawa-table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 20px;
      border: 1px solid #ddd;
    }
    .balik-mangagawa-table th, .balik-mangagawa-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    .balik-mangagawa-table th {
      background-color: #007bff;
      color: white;
      font-weight: 500;
      border: none;
    }
    .balik-mangagawa-table tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    .balik-mangagawa-table tr:hover {
      background-color: #f1f1f1;
    }
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      text-transform: capitalize;
    }
    
    /* Floating action button */
    .floating-btn {
      position: fixed;
      width: 60px;
      height: 60px;
      bottom: 40px;
      right: 40px;
      background-color: rgba(0, 0, 0, 0.6);
      color: #FFF;
      border-radius: 50px;
      text-align: center;
      box-shadow: 2px 2px 3px #999;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      z-index: 1000;
      transition: all 0.3s ease;
    }
    
    .floating-btn:hover {
      background-color: rgba(0, 0, 0, 0.8);
      transform: scale(1.1);
    }
    
    .modal {
      display: none;
      position: fixed;
      z-index: 1001;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 20px;
      border-radius: 8px;
      width: 60%;
      max-width: 600px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .close {
      color: #aaa;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .close:hover {
      color: black;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-control {
      width: 100%;
      padding: 8px;
      border: 1px solid #ced4da;
      border-radius: 4px;
    }
    
    textarea.form-control {
      min-height: 80px;
    }
    
    .modal-footer {
      margin-top: 20px;
      text-align: right;
    }
    
    .btn {
      padding: 8px 15px;
      border-radius: 4px;
      border: none;
      cursor: pointer;
    }
    
    .btn-primary {
      background-color: #007bff;
      color: white;
    }
    
    .btn-success {
      background-color: #28a745;
      color: white;
    }
    
    .btn-danger {
      background-color: #dc3545;
      color: white;
    }
    
    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }
  </style>
</head>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>
    
    <div class="content-wrapper">
      <?php include '_header.php'; ?>
      
      <main class="main-content">
        <div class="container-fluid">
          <?php if ($form_only): ?>
          <!-- Form-only view for staff -->
          <div class="card" style="max-width: 800px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <div class="card-header" style="background-color: #007bff; color: white; padding: 15px 20px; border-radius: 7px 7px 0 0;">
              <h2 style="margin: 0; font-size: 1.5rem;">Submit New Blacklist Entry</h2>
            </div>
            <div class="card-body" style="padding: 20px;">
              <?php if ($success_message): ?>
              <div class="alert alert-success" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 8px 15px; margin-bottom: 15px; border-radius: 4px;">
                <?= $success_message ?>
              </div>
              <?php endif; ?>
              <?php if ($error_message): ?>
              <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 8px 15px; margin-bottom: 15px; border-radius: 4px;">
                <?= $error_message ?>
              </div>
              <?php endif; ?>
              
              <form id="addBlacklistForm" action="process_blacklist.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="full_name" style="font-weight: 500; margin-bottom: 5px; display: block;">Full Name *</label>
                  <input type="text" class="form-control" id="full_name" name="full_name" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="passport_number" style="font-weight: 500; margin-bottom: 5px; display: block;">Passport Number</label>
                  <input type="text" class="form-control" id="passport_number" name="passport_number" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="email" style="font-weight: 500; margin-bottom: 5px; display: block;">Email Address</label>
                  <input type="email" class="form-control" id="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="phone" style="font-weight: 500; margin-bottom: 5px; display: block;">Phone Number</label>
                  <input type="tel" class="form-control" id="phone" name="phone" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                  <label for="reason" style="font-weight: 500; margin-bottom: 5px; display: block;">Reason for Blacklisting *</label>
                  <textarea class="form-control" id="reason" name="reason" required style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; min-height: 100px;"></textarea>
                </div>
                <div style="margin-top: 20px; text-align: right;">
                  <a href="dashboard.php" class="btn btn-secondary" style="background-color: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; text-decoration: none; margin-right: 10px;">Cancel</a>
                  <button type="submit" class="btn btn-primary" style="background-color: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Submit Entry</button>
                </div>
              </form>
            </div>
          </div>
          <?php else: ?>
          <!-- Dashboard view for Regional Director and other roles -->
          <div class="process-page-top">
            <div class="tabs">
              <?php if ($can_approve): ?>
              <a href="?tab=pending<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab == 'pending' ? 'active' : '' ?>">
                Pending <span class="count"><?= count($blacklist_records) ?></span>
              </a>
              <?php endif; ?>
              <a href="?tab=blacklisted<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab == 'blacklisted' || $active_tab == 'approved' ? 'active' : '' ?>">
                Blacklisted
              </a>
            </div>
            
            <!-- Search Form -->
            <div class="search-container">
              <form action="" method="GET" class="search-form">
                <input type="hidden" name="tab" value="<?= $active_tab ?>">
                <div class="search-input-container">
                  <input type="text" name="search" placeholder="Search by name, passport, email or phone..." value="<?= htmlspecialchars($search_query) ?>" class="search-input">
                  <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </form>
            </div>
          </div>
          
          <!-- Alerts for success or error messages -->
          <?php if ($success_message): ?>
          <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #28a745;">
            <?= htmlspecialchars($success_message) ?>
          </div>
          <?php endif; ?>
          
          <?php if ($error_message): ?>
          <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
            <?= $error_message ?>
          </div>
          <?php endif; ?>
          
          <!-- Blacklist Records Table -->
          <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <div class="card-body" style="padding: 20px;">
              <?php if (count($blacklist_records) > 0): ?>
              <div class="table-responsive">
                <table class="balik-mangagawa-table">
                  <thead>
                    <tr>
                      <?php if ($active_tab == 'pending' && strtolower($user_role) == 'regional director'): ?>
                      <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="select-all" title="Select All">
                      </th>
                      <?php endif; ?>
                      <th>Full Name</th>
                      <th>Passport/ID</th>
                      <th>Contact</th>
                      <th>Reason</th>
                      <th>Submitted By</th>
                      <th>Date</th>
                      <th>Status</th>
                      <th style="text-align: center;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($blacklist_records as $record): ?>
                    <tr>
                      <?php if ($user_role === 'regional director' && ($active_tab == 'pending')): ?>
                      <td style="text-align: center;">
                        <input type="checkbox" class="record-checkbox" value="<?= $record['id'] ?>">
                      </td>
                      <?php endif; ?>
                      <td>
                        <div style="font-weight: 500;"><?= htmlspecialchars($record['full_name']) ?></div>
                      </td>
                      <td><?= htmlspecialchars($record['passport_number'] ?: 'N/A') ?></td>
                      <td>
                        <?php if (!empty($record['email'])): ?>
                        <div><?= htmlspecialchars($record['email']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($record['phone'])): ?>
                        <div><?= htmlspecialchars($record['phone']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td><?= htmlspecialchars($record['reason']) ?></td>
                      <td><?= htmlspecialchars($record['submitted_by_name'] ?: 'N/A') ?></td>
                      <td><?= date('M d, Y', strtotime($record['created_at'])) ?></td>
                      <td>
                        <span class="badge" style="background-color: <?= getStatusBadgeColor($record['status']) ?>; color: white;">
                          <?= htmlspecialchars($record['status']) ?>
                        </span>
                      </td>
                      <td style="text-align: center;">
                        <?php if ($active_tab == 'pending' && $can_approve): ?>
                        <button class="btn btn-success btn-sm" onclick="showApproveModal(<?= $record['id'] ?>, '<?= htmlspecialchars(addslashes($record['full_name'])) ?>')">
                          <i class="fa fa-check"></i> Approve
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="showRejectModal(<?= $record['id'] ?>, '<?= htmlspecialchars(addslashes($record['full_name'])) ?>')">
                          <i class="fa fa-times"></i> Reject
                        </button>
                        <?php elseif (($active_tab == 'blacklisted' || $active_tab == 'approved') && $can_approve): ?>
                        <button class="btn btn-warning btn-sm" onclick="showUnblockModal(<?= $record['id'] ?>, '<?= htmlspecialchars(addslashes($record['full_name'])) ?>')">
                          <i class="fa fa-unlock"></i> Unblock
                        </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php else: ?>
              <div class="alert alert-info" style="background-color: #d1ecf1; color: #0c5460; padding: 12px 15px; border-radius: 4px; margin-bottom: 0; border-left: 4px solid #17a2b8;">
                <p style="margin: 0;"><i class="fa fa-info-circle"></i> No records found.</p>
              </div>
              <?php endif; ?>
            </div>
          </div>
          
          <?php if ($user_role === 'regional director' && $active_tab == 'pending' && count($blacklist_records) > 0): ?>
          <!-- Batch Action Buttons -->
          <div style="margin-bottom: 30px; text-align: right;">
            <button id="approveSelectedBtn" onclick="approveSelected()" style="background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 8px 15px; margin-right: 10px; cursor: pointer;">
              <i class="fas fa-check"></i> Approve Selected
            </button>
            <button id="rejectSelectedBtn" onclick="rejectSelected()" style="background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 8px 15px; cursor: pointer;">
              <i class="fas fa-times"></i> Reject Selected
            </button>
          </div>
          <?php endif; ?>
        </div>
        
        <?php if (in_array(strtolower($user_role), ['staff', 'division head'])): ?>
        <!-- Floating Action Button for non-regional director users -->
        <a href="#" class="floating-btn" id="addBlacklistBtn">
          <i class="fas fa-user-slash"></i>
        </a>
        <?php endif; ?>
      </main>
    </div>
  </div>
  
  <?php if (in_array(strtolower($user_role), ['staff', 'division head'])): ?>
  <!-- Add to Blacklist Modal -->
  <div id="addBlacklistModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add Person to Blacklist</h2>
        <span class="close" onclick="document.getElementById('addBlacklistModal').style.display='none'">&times;</span>
      </div>
      <form id="blacklistForm" action="process_blacklist.php" method="post">
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
          <label for="full_name">Full Name *</label>
          <input type="text" class="form-control" id="full_name" name="full_name" required>
        </div>
        
        <div class="form-group">
          <label for="passport_number">Passport Number</label>
          <input type="text" class="form-control" id="passport_number" name="passport_number">
        </div>
        
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" class="form-control" id="email" name="email">
        </div>
        
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" class="form-control" id="phone" name="phone">
        </div>
        
        <div class="form-group">
          <label for="reason">Reason for Blacklisting *</label>
          <textarea class="form-control" id="reason" name="reason" required></textarea>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('addBlacklistModal').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-primary">Submit for Approval</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
  
  <?php if ($user_role === 'regional director'): ?>
  <!-- Approve Modal -->
  <div id="approveModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Approve Blacklist Request</h2>
        <span class="close" onclick="document.getElementById('approveModal').style.display='none'">&times;</span>
      </div>
      <form id="approveForm" action="process_blacklist.php" method="post">
        <input type="hidden" name="action" value="approve">
        <input type="hidden" id="approve_id" name="id" value="">
        <input type="hidden" id="approve_ids" name="ids" value="">
        
        <p>Are you sure you want to approve the blacklist request for <strong id="approve_name"></strong>?</p>
        <p><small>This person will be added to the blacklist and users will be warned when encountering this individual.</small></p>
        
        <div class="form-group">
          <label for="approve_notes">Notes (optional)</label>
          <textarea class="form-control" id="approve_notes" name="notes"></textarea>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('approveModal').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-success">Approve</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Reject Modal -->
  <div id="rejectModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Reject Blacklist Request</h2>
        <span class="close" onclick="document.getElementById('rejectModal').style.display='none'">&times;</span>
      </div>
      <form id="rejectForm" action="process_blacklist.php" method="post">
        <input type="hidden" name="action" value="reject">
        <input type="hidden" id="reject_id" name="id" value="">
        <input type="hidden" id="reject_ids" name="ids" value="">
        
        <p>Are you sure you want to reject the blacklist request for <strong id="reject_name"></strong>?</p>
        
        <div class="form-group">
          <label for="reject_notes">Reason for Rejection *</label>
          <textarea class="form-control" id="reject_notes" name="notes" required></textarea>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div>
  </div>
  <!-- Unblock Modal -->
  <div class="modal" id="unblockModal">
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h3>Confirm Unblock</h3>
        <span class="close" onclick="document.getElementById('unblockModal').style.display='none'">&times;</span>
      </div>
      <form action="process_blacklist.php" method="post">
        <input type="hidden" name="action" value="unblock">
        <input type="hidden" id="unblock_id" name="id" value="">
        
        <div class="modal-body">
          <p>Are you sure you want to unblock <strong id="unblock_name"></strong>?</p>
          <p class="text-danger">This action will permanently remove this person from the blacklist.</p>
          
          <div class="form-group">
            <label for="password">Enter your password to confirm *</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('unblockModal').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-warning">Unblock</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize the floating button for adding to blacklist
      const addBlacklistBtn = document.getElementById('addBlacklistBtn');
      if (addBlacklistBtn) {
        addBlacklistBtn.addEventListener('click', function(e) {
          e.preventDefault();
          document.getElementById('addBlacklistModal').style.display = 'block';
        });
      }
      
      // Initialize select all checkbox
      const selectAllCheckbox = document.getElementById('select-all');
      if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
          const isChecked = this.checked;
          document.querySelectorAll('.record-checkbox').forEach(checkbox => {
            checkbox.checked = isChecked;
          });
        });
      }
    });
    
    // Show approve modal for individual record
    function showApproveModal(id, name) {
      document.getElementById('approve_id').value = id;
      document.getElementById('approve_ids').value = '';
      document.getElementById('approve_name').textContent = name;
      document.getElementById('approveModal').style.display = 'block';
    }
    
    // Show reject modal for individual record
    function showRejectModal(id, name) {
      document.getElementById('reject_id').value = id;
      document.getElementById('reject_ids').value = '';
      document.getElementById('reject_name').textContent = name;
      document.getElementById('rejectModal').style.display = 'block';
    }
    
    // Show unblock modal for a blacklisted record
    function showUnblockModal(id, name) {
      document.getElementById('unblock_id').value = id;
      document.getElementById('unblock_name').textContent = name;
      document.getElementById('unblockModal').style.display = 'block';
    }
    
    // Approve selected records
    function approveSelected() {
      const selectedIds = getSelectedIds();
      if (selectedIds.length === 0) {
        alert('Please select at least one record to approve.');
        return;
      }
      
      document.getElementById('approve_id').value = '';
      document.getElementById('approve_ids').value = selectedIds.join(',');
      document.getElementById('approve_name').textContent = selectedIds.length + ' selected records';
      document.getElementById('approveModal').style.display = 'block';
    }
    
    // Reject selected records
    function rejectSelected() {
      const selectedIds = getSelectedIds();
      if (selectedIds.length === 0) {
        alert('Please select at least one record to reject.');
        return;
      }
      
      document.getElementById('reject_id').value = '';
      document.getElementById('reject_ids').value = selectedIds.join(',');
      document.getElementById('reject_name').textContent = selectedIds.length + ' selected records';
      document.getElementById('rejectModal').style.display = 'block';
    }
    
    // Get selected record IDs
    function getSelectedIds() {
      const checkboxes = document.querySelectorAll('.record-checkbox:checked');
      return Array.from(checkboxes).map(checkbox => checkbox.value);
    }
    
    // Close modals when clicking on the close button or outside the modal
    window.onclick = function(event) {
      const modals = document.getElementsByClassName('modal');
      for (let i = 0; i < modals.length; i++) {
        if (event.target === modals[i]) {
          modals[i].style.display = 'none';
        }
      }
    };
  </script>
</body>
</html>
