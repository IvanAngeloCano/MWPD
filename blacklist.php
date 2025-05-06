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
$form_only = false;
if (strtolower($user_role) === 'staff') {
    $form_only = true;
}

// Handle form-only view when explicitly requested
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $form_only = true;
}

// Handle session messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Only fetch records if we're showing the dashboard
if (!$form_only) {
    try {
        // Check if the blacklist table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
        $table_exists = $stmt->rowCount() > 0;
        
        if (!$table_exists) {
            $error_message = "The blacklist table does not exist yet. <a href='create_blacklist_db.php' style='color: #007bff;'>Click here</a> to create it.";
        } else {
            // Get blacklist records based on tab and search query
            $where_conditions = [];
            $params = [];
            
            // Filter by status based on the active tab
            if ($active_tab == 'pending') {
                $where_conditions[] = "status = 'pending'";
            } elseif ($active_tab == 'blacklisted') {
                $where_conditions[] = "status = 'approved'"; // 'approved' in database means blacklisted
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
}

// Function to get status badge color
function getStatusBadgeColor($status) {
    switch ($status) {
        case 'pending':
            return '#ffc107'; // yellow
        case 'approved':
        case 'blacklisted':
            return '#28a745'; // green
        default:
            return '#6c757d'; // gray
    }
}

// Function to get display status
function getDisplayStatus($status) {
    if ($status == 'approved') {
        return 'Blacklisted';
    }
    return ucfirst($status);
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
      margin-bottom: 30px;
      border: 1px solid #ddd;
    }
    .balik-mangagawa-table th, .balik-mangagawa-table td {
      border: 1px solid #ddd;
      padding: 12px 15px;
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
    
    /* Search form styling */
    .search-form {
      display: flex;
      align-items: center;
    }
    
    .controls .search-form {
      margin-left: auto;
    }
    
    .search-bar {
      border: 1px solid #ddd;
      padding: 8px 12px;
      border-radius: 4px 0 0 4px;
      width: 200px;
      font-size: 14px;
      margin-right: 0;
      outline: none;
    }
    
    .search-btn {
      background-color: #007bff;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 0 4px 4px 0;
      cursor: pointer;
    }
    
    .search-btn:hover {
      background-color: #0069d9;
    }
    
    /* Modal styling */
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
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 80%;
      max-width: 600px;
      border-radius: 5px;
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .modal-body {
      padding: 20px 0;
    }
    
    .modal-footer {
      margin-top: 20px;
      padding-top: 10px;
      border-top: 1px solid #eee;
      text-align: right;
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
    
    .btn {
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
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
    
    .btn-warning {
      background-color: #ffc107;
      color: #212529;
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
                Pending
              </a>
              <?php endif; ?>
              <a href="?tab=blacklisted<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab == 'blacklisted' ? 'active' : '' ?>">
                Blacklisted
              </a>
            </div>
            
            <div class="controls">
              <?php if (!$can_approve): ?>
              <a href="?action=add" class="add-btn">
                <i class="fas fa-plus-circle"></i>
                <span>Add to Blacklist</span>
              </a>
              <?php endif; ?>
              <form action="" method="GET" class="search-form">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
                <div class="search-form">
                  <input type="text" name="search" placeholder="Search" class="search-bar" value="<?= htmlspecialchars($search_query) ?>">
                  <button type="submit" class="btn search-btn"><i class="fa fa-search"></i></button>
                </div>
              </form>
            </div>
          </div>

          <!-- Add spacing between controls and table -->
          <div style="height: 25px;"></div>

          <!-- Alerts for success or error messages -->
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

          <!-- Blacklist Records Table -->
          <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
            <div class="card-body" style="padding: 25px 30px;">
              <?php if (count($blacklist_records) > 0): ?>
              <div class="table-responsive">
                <table class="balik-mangagawa-table">
                  <thead>
                    <tr>
                      <?php if ($active_tab == 'pending' && $can_approve): ?>
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
                      <?php if ($active_tab == 'pending' && $can_approve): ?>
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
                          <?= htmlspecialchars(getDisplayStatus($record['status'])) ?>
                        </span>
                      </td>
                      <td style="text-align: center;">
                        <?php if ($active_tab == 'pending' && $can_approve): ?>
                        <button class="btn go-btn" onclick="showApproveModal(<?= $record['id'] ?>, '<?= htmlspecialchars(addslashes($record['full_name'])) ?>')" style="background-color: #28a745; margin-right: 5px; color: white;">
                          <i class="fa fa-ban"></i> Blacklist
                        </button>
                        <button class="btn clear-btn" onclick="showRejectModal(<?= $record['id'] ?>, '<?= htmlspecialchars(addslashes($record['full_name'])) ?>')" style="background-color: #dc3545; color: white;">
                          <i class="fa fa-times"></i> Reject
                        </button>
                        <?php elseif ($active_tab == 'blacklisted' && $can_approve): ?>
                        <button class="btn" onclick="showUnblockModal(<?= $record['id'] ?>, '<?= htmlspecialchars(addslashes($record['full_name'])) ?>')" style="background-color: #ffc107; color: #212529;">
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
              <p>No blacklist records found.</p>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>
  
  <?php if (!$form_only && $can_approve): ?>
  <!-- Blacklist Modal -->
  <div id="approveModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Confirm Blacklisting Request</h2>
        <span class="close" onclick="document.getElementById('approveModal').style.display='none'">&times;</span>
      </div>
      <form id="approveForm" action="process_blacklist.php" method="post">
        <input type="hidden" name="action" value="approve">
        <input type="hidden" id="approve_id" name="id" value="">
        <input type="hidden" id="approve_ids" name="ids" value="">
        
        <div class="modal-body">
          <p>Are you sure you want to blacklist <strong id="approve_name"></strong>?</p>
          <p><small>This person will be added to the blacklist and users will be warned when encountering this individual.</small></p>
          
          <div class="form-group">
            <label for="approve_notes">Notes (optional)</label>
            <textarea class="form-control" id="approve_notes" name="notes"></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('approveModal').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-success">Blacklist</button>
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
        
        <div class="modal-body">
          <p>Are you sure you want to reject the blacklist request for <strong id="reject_name"></strong>?</p>
          <p><small>This record will be permanently removed and not stored in the system.</small></p>
          
          <div class="form-group">
            <label for="reject_notes">Reason for Rejection *</label>
            <textarea class="form-control" id="reject_notes" name="notes" required></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Unblock Modal -->
  <div id="unblockModal" class="modal">
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
