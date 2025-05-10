<?php
include 'session.php';
require_once 'connection.php';

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'gov_to_gov';

// Check if user has Regional Director role
$is_regional_director = false;
if (isset($_SESSION['role']) && (stripos($_SESSION['role'], 'regional director') !== false || stripos($_SESSION['role'], 'admin') !== false)) {
    $is_regional_director = true;
}

// If not a Regional Director, redirect to dashboard
if (!$is_regional_director) {
    $_SESSION['error_message'] = "You do not have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['approval_id'])) {
    $approval_id = (int)$_POST['approval_id'];
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'] ?? 0;
    
    try {
        $pdo->beginTransaction();
        
        // Get the approval details and submitter info
        $get_approval = $pdo->prepare("
            SELECT a.*, g.last_name, g.first_name, u.id as submitter_id 
            FROM pending_g2g_approvals a
            JOIN gov_to_gov g ON a.g2g_id = g.g2g
            LEFT JOIN users u ON a.submitted_by = u.id
            WHERE a.approval_id = ?
        ");
        $get_approval->execute([$approval_id]);
        $approval_details = $get_approval->fetch(PDO::FETCH_ASSOC);
        
        $submitter_id = $approval_details['submitter_id'] ?? 0;
        $applicant_name = $approval_details['last_name'] . ', ' . $approval_details['first_name'];
        
        if ($action === 'approve') {
            // Update the approval record
            $update_stmt = $pdo->prepare("
                UPDATE pending_g2g_approvals 
                SET status = 'Approved', 
                    approval_date = NOW(), 
                    approved_by = ? 
                WHERE approval_id = ?
            ");
            $update_stmt->execute([$user_id, $approval_id]);
            
            // Debug info for troubleshooting
            error_log("Approving record ID: $approval_id by user ID: $user_id");
            
            // Get the g2g_id and other details
            $get_details = $pdo->prepare("
                SELECT g2g_id, employer, memo_reference 
                FROM pending_g2g_approvals 
                WHERE approval_id = ?
            ");
            $get_details->execute([$approval_id]);
            $details = $get_details->fetch(PDO::FETCH_ASSOC);
            
            if ($details) {
                // 1. FIRST UPDATE ATTEMPT: Try to update the gov_to_gov record with all fields
                $endorse_stmt = $pdo->prepare("
                    UPDATE gov_to_gov 
                    SET remarks = 'Approved',
                        endorsement_date = NOW(),
                        employer = ?,
                        memo_reference = ?
                    WHERE g2g = ?
                ");
                $result = $endorse_stmt->execute([$details['employer'], $details['memo_reference'], $details['g2g_id']]);
                
                // 2. SECOND UPDATE ATTEMPT: Try a simpler update if the first one didn't affect any rows
                if (!$result || $endorse_stmt->rowCount() === 0) {
                    $simple_update = $pdo->prepare("UPDATE gov_to_gov SET remarks = 'Approved' WHERE g2g = ?");
                    $result2 = $simple_update->execute([$details['g2g_id']]);
                    error_log("First update failed, second attempt result: " . ($result2 ? "Success" : "Failed"));
                }
                
                // 3. THIRD UPDATE ATTEMPT: Force a direct update using raw SQL
                $g2g_id = $details['g2g_id'];
                $raw_sql = "UPDATE gov_to_gov SET remarks = 'Approved' WHERE g2g = $g2g_id";
                $pdo->exec($raw_sql);
                
                // 4. VERIFY: Check if the update was successful
                $check_stmt = $pdo->prepare("SELECT remarks FROM gov_to_gov WHERE g2g = ?");
                $check_stmt->execute([$details['g2g_id']]);
                $current_remarks = $check_stmt->fetchColumn();
                error_log("After all update attempts, remarks = $current_remarks for g2g_id: {$details['g2g_id']}");
                
                // Send notification to the submitter
                if ($submitter_id > 0) {
                    try {
                        require_once 'notifications.php';
                        
                        // Ensure notifications table exists
                        if (function_exists('ensureNotificationsTableExists')) {
                            ensureNotificationsTableExists();
                        }
                        
                        $message = "Gov-to-Gov record for $applicant_name has been approved";
                        $link = "gov_to_gov.php?tab=endorsed";
                        
                        if (function_exists('addNotification')) {
                            addNotification($submitter_id, $message, $details['g2g_id'], 'gov_to_gov', $link);
                        }
                    } catch (Exception $e) {
                        // Log notification error but continue with the process
                        error_log("Error sending notification: " . $e->getMessage());
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = "Record approved successfully and marked as 'Approved' in the Gov-to-Gov table.";
            
        } elseif ($action === 'reject') {
            // Update the approval record
            $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
            $update_stmt = $pdo->prepare("
                UPDATE pending_g2g_approvals 
                SET status = 'Rejected', 
                    approval_date = NOW(), 
                    approved_by = ?,
                    remarks = ?
                WHERE approval_id = ?
            ");
            $update_stmt->execute([$user_id, $remarks, $approval_id]);
            
            // Get the g2g_id from the pending approval
            $get_g2g_id = $pdo->prepare("SELECT g2g_id FROM pending_g2g_approvals WHERE approval_id = ?");
            $get_g2g_id->execute([$approval_id]);
            $g2g_id = $get_g2g_id->fetchColumn();
            
            if ($g2g_id) {
                // Update the gov_to_gov record to mark as rejected
                $reject_stmt = $pdo->prepare("
                    UPDATE gov_to_gov 
                    SET remarks = 'Rejected'
                    WHERE g2g = ?
                ");
                $reject_stmt->execute([$g2g_id]);
            }
            
            // Send notification to the submitter
            if ($submitter_id > 0) {
                try {
                    require_once 'notifications.php';
                    
                    // Ensure notifications table exists
                    if (function_exists('ensureNotificationsTableExists')) {
                        ensureNotificationsTableExists();
                    }
                    
                    $message = "Gov-to-Gov record for $applicant_name has been rejected. Reason: $remarks";
                    $link = "gov_to_gov.php";
                    
                    if (function_exists('addNotification')) {
                        addNotification($submitter_id, $message, $approval_details['g2g_id'], 'gov_to_gov', $link);
                    }
                } catch (Exception $e) {
                    // Log notification error but continue with the process
                    error_log("Error sending notification: " . $e->getMessage());
                }
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = "Record rejected.";
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    header('Location: g2g_pending_approvals.php');
    exit();
}

// Get pending approvals
$pending_approvals = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, g.last_name, g.first_name, g.middle_name, g.passport_number,
               u.full_name as submitter_name
        FROM pending_g2g_approvals a
        JOIN gov_to_gov g ON a.g2g_id = g.g2g
        LEFT JOIN users u ON a.submitted_by = u.id
        WHERE a.status = 'Pending'
        ORDER BY a.submitted_date DESC
    ");
    $stmt->execute();
    $pending_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error fetching pending approvals: " . $e->getMessage();
}

$pageTitle = "Approval";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="content-wrapper">
    <?php include '_header.php'; ?>
    
    <main class="main-content">
      <div class="approvals-wrapper">
        <div class="approvals-top">
          
          <!-- Tabs Navigation -->
          <div style="display: flex; gap: 0.5rem;" class="process-page-top">
            <div style="display: flex; gap: 0.5rem;" class="tabs">
              <a href="approval_view_simple.php?tab=direct_hire" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'direct_hire' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'direct_hire' ? 'white' : 'inherit' ?>;">Direct Hire</a>
              <a href="g2g_pending_approvals.php?tab=gov_to_gov" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'gov_to_gov' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'gov_to_gov' ? 'white' : 'inherit' ?>;">Gov-to-Gov</a>
              <a href="approval_view_simple.php?tab=balik_manggagawa" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'balik_manggagawa' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'balik_manggagawa' ? 'white' : 'inherit' ?>;">Balik Manggagawa</a>
            </div>
          </div>
        </div>
        
        <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($_SESSION['success_message']) ?>
          <?php unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($_SESSION['error_message']) ?>
          <?php unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($pending_approvals)): ?>
        <div class="alert alert-info">
          No pending approvals at this time.
        </div>
        <?php else: ?>
          <div style="margin-top: 10px;" class="approvals-table">
            <?php if (count($pending_approvals) > 0): ?>
              <table style="width: 100%; border-collapse: collapse;">
                <thead>
                  <tr>
                    <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Name</th>
                    <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Passport</th>
                    <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Reference</th>
                    <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Date Submitted</th>
                    <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pending_approvals as $index => $approval): ?>
                    <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6;">
                      <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['last_name'] . ', ' . $approval['first_name'] . ' ' . $approval['middle_name']) ?></td>
                      <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['passport_number']) ?></td>
                      <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($approval['memo_reference'] ?: 'N/A') ?></td>
                      <td style="padding: 12px 15px; vertical-align: middle;"><?= date('M d, Y', strtotime($approval['submitted_date'])) ?></td>
                      <td style="padding: 12px 15px; vertical-align: middle;">
                        <div style="display: flex; gap: 8px;">
                          <a href="gov_to_gov_view.php?id=<?= $approval['g2g_id'] ?>" style="display: inline-flex; align-items: center; gap: 5px; background-color: #007bff; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px; text-decoration: none;">
                            <i class="fas fa-eye"></i> View
                          </a>
                          <button onclick="showApproveModal(<?= $approval['approval_id'] ?>)" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px;">
                            <i class="fas fa-check"></i> Approve
                          </button>
                          <button onclick="showRejectModal(<?= $approval['approval_id'] ?>)" style="display: inline-flex; align-items: center; gap: 5px; background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px;">
                            <i class="fas fa-times"></i> Deny
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="no-records">
                <i class="fas fa-info-circle"></i> No pending approvals found.
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
  <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 0; border: 1px solid #888; width: 50%; box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);">
    <div class="modal-header" style="padding: 15px; border-bottom: 1px solid #e9ecef; background-color: #f8f9fa;">
      <h3 style="margin: 0; color: #333;"><i class="fa fa-check-circle" style="color: #28a745;"></i> Approve Record</h3>
      <span class="close" onclick="document.getElementById('approveModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
    </div>
    <div class="modal-body" style="padding: 15px;">
      <p>Are you sure you want to approve this record? This will mark the record as endorsed.</p>
      <form id="approveForm" method="post" action="g2g_pending_approvals.php">
        <input type="hidden" name="approval_id" id="approve_approval_id">
        <input type="hidden" name="action" value="approve">
      </form>
    </div>
    <div class="modal-footer" style="padding: 15px; border-top: 1px solid #e9ecef; text-align: right;">
      <button type="button" class="btn btn-secondary" style="padding: 6px 12px; margin-right: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="document.getElementById('approveModal').style.display='none'">Cancel</button>
      <button type="button" class="btn btn-success" style="padding: 6px 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="document.getElementById('approveForm').submit()">Approve</button>
    </div>
  </div>
</div>

<!-- Deny Modal -->
<div id="rejectModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
  <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 0; border: 1px solid #888; width: 50%; box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);">
    <div class="modal-header" style="padding: 15px; border-bottom: 1px solid #e9ecef; background-color: #f8f9fa;">
      <h3 style="margin: 0; color: #333;"><i class="fa fa-times-circle" style="color: #dc3545;"></i> Deny Record</h3>
      <span class="close" onclick="document.getElementById('rejectModal').style.display='none'" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
    </div>
    <div class="modal-body" style="padding: 15px;">
      <p>Please provide a reason for denying this record:</p>
      <form id="rejectForm" method="post" action="g2g_pending_approvals.php">
        <input type="hidden" name="approval_id" id="reject_approval_id">
        <input type="hidden" name="action" value="reject">
        <div class="form-group" style="margin-bottom: 15px;">
          <textarea name="remarks" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;" rows="3" required></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer" style="padding: 15px; border-top: 1px solid #e9ecef; text-align: right;">
      <button type="button" class="btn btn-secondary" style="padding: 6px 12px; margin-right: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
      <button type="button" class="btn btn-danger" style="padding: 6px 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="document.getElementById('rejectForm').submit()">Deny</button>
    </div>
  </div>
</div>

<!-- Result Modal for Approval/Rejection -->
<div id="resultModal" class="modal">
  <div class="modal-content">
    <div class="modal-header" id="resultModalHeader">
      <h3 id="resultModalTitle"><i class="fa fa-check-circle"></i> <span id="resultModalTitleText">Action Completed</span></h3>
      <span class="close" onclick="document.getElementById('resultModal').style.display='none'">&times;</span>
    </div>
    <div class="modal-body">
      <p id="resultModalMessage">The action has been completed successfully.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-primary" onclick="document.getElementById('resultModal').style.display='none'">OK</button>
    </div>
  </div>
</div>

<!-- No need for additional stylesheet reference as it's already included in _head.php -->
<style>
  /* Result Modal Styles */
  #resultModal .modal-header.success {
    background-color: #28a745;
    color: white;
  }
  
  #resultModal .modal-header.error {
    background-color: #dc3545;
    color: white;
  }
  
  #resultModal .modal-content {
    width: 400px;
    max-width: 90%;
    margin: 10% auto;
  }
</style>

<script>
function showApproveModal(approvalId) {
  document.getElementById('approve_approval_id').value = approvalId;
  document.getElementById('approveModal').style.display = 'block';
}

function showRejectModal(approvalId) {
  document.getElementById('reject_approval_id').value = approvalId;
  document.getElementById('rejectModal').style.display = 'block';
}

// Function to show result modal
function showResultModal(success, message) {
  const resultModal = document.getElementById('resultModal');
  const resultModalHeader = document.getElementById('resultModalHeader');
  const resultModalTitle = document.getElementById('resultModalTitleText');
  const resultModalMessage = document.getElementById('resultModalMessage');
  
  // Set header style and title based on success/error
  if (success) {
    resultModalHeader.className = 'modal-header success';
    resultModalTitle.innerHTML = 'Approved';
    resultModalMessage.innerHTML = message || 'Record has been approved successfully.';
  } else {
    resultModalHeader.className = 'modal-header error';
    resultModalTitle.innerHTML = 'Denied';
    resultModalMessage.innerHTML = message || 'Record has been denied.';
  }
  
  // Show the modal
  resultModal.style.display = 'block';
}

// Check for success/error messages on page load
document.addEventListener('DOMContentLoaded', function() {
  <?php if (isset($_SESSION['success_message'])): ?>
    showResultModal(true, '<?php echo addslashes($_SESSION['success_message']); ?>');
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>
  
  <?php if (isset($_SESSION['error_message'])): ?>
    showResultModal(false, '<?php echo addslashes($_SESSION['error_message']); ?>');
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>
});

// Double-click functionality for table rows
document.addEventListener('DOMContentLoaded', function() {
  const tableRows = document.querySelectorAll('tbody tr');
  tableRows.forEach(row => {
    row.addEventListener('dblclick', function(e) {
      // Don't trigger if clicking on action buttons
      if (e.target.closest('.action-icons')) {
        return;
      }
      
      // Get the approval ID from the approve button
      const approveBtn = row.querySelector('button[onclick*="showApproveModal"]');
      if (approveBtn) {
        const onclickAttr = approveBtn.getAttribute('onclick');
        const approvalId = onclickAttr.match(/showApproveModal\((\d+)\)/)[1];
        if (approvalId) {
          // Open the approve modal on double-click
          showApproveModal(approvalId);
        }
      }
    });
    
    // Add cursor style to indicate clickable
    row.style.cursor = 'pointer';
  });
});

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target.classList.contains('modal')) {
    event.target.style.display = 'none';
  }
}
</script>
