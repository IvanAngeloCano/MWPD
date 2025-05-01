<?php
include 'session.php';
require_once 'connection.php';

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
            
            // Get the g2g_id and other details
            $get_details = $pdo->prepare("
                SELECT g2g_id, employer, memo_reference 
                FROM pending_g2g_approvals 
                WHERE approval_id = ?
            ");
            $get_details->execute([$approval_id]);
            $details = $get_details->fetch(PDO::FETCH_ASSOC);
            
            if ($details) {
                // Update the gov_to_gov record to mark as endorsed
                $endorse_stmt = $pdo->prepare("
                    UPDATE gov_to_gov 
                    SET remarks = 'Endorsed',
                        endorsement_date = NOW(),
                        employer = ?,
                        memo_reference = ?
                    WHERE g2g = ?
                ");
                $endorse_stmt->execute([$details['employer'], $details['memo_reference'], $details['g2g_id']]);
                
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
            $_SESSION['success_message'] = "Record approved successfully and marked as endorsed.";
            
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

$pageTitle = "Pending Gov-to-Gov Approvals";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="content-wrapper">
    <?php include '_header.php'; ?>
    <main class="main-content">
      <div class="container">
        <h1 class="page-title">Pending Gov-to-Gov Approvals</h1>
        
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
        
        <div class="approvals-wrapper">
          <div class="approvals-top">
            <h2 class="section-title">Pending Approvals</h2>
          </div>
          
          <div class="approvals-table">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Applicant Name</th>
                  <th>Passport Number</th>
                  <th>Submitted By</th>
                  <th>Submission Date</th>
                  <th>Employer</th>
                  <th>Reference</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $loop = 0; foreach ($pending_approvals as $approval): ?>
                <tr class="<?= $loop % 2 == 0 ? 'even-row' : 'odd-row' ?>">
                  <td><?= htmlspecialchars($approval['last_name'] . ', ' . $approval['first_name'] . ' ' . $approval['middle_name']) ?></td>
                  <td><?= htmlspecialchars($approval['passport_number']) ?></td>
                  <td><?= htmlspecialchars($approval['submitter_name']) ?></td>
                  <td><?= date('M d, Y', strtotime($approval['submitted_date'])) ?></td>
                  <td><?= htmlspecialchars($approval['employer']) ?></td>
                  <td><?= htmlspecialchars($approval['memo_reference']) ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-approve" onclick="showApproveModal(<?= $approval['approval_id'] ?>)"><i class="fa fa-check"></i> Approve</button>
                      <button class="btn btn-sm btn-reject" onclick="showRejectModal(<?= $approval['approval_id'] ?>)"><i class="fa fa-times"></i> Reject</button>
                    </div>
                  </td>
                </tr>
                <?php $loop++; endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fa fa-check-circle"></i> Approve Record</h3>
      <span class="close" onclick="document.getElementById('approveModal').style.display='none'">&times;</span>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to approve this record? This will mark the record as endorsed.</p>
      <form id="approveForm" method="post" action="g2g_pending_approvals.php">
        <input type="hidden" name="approval_id" id="approve_approval_id">
        <input type="hidden" name="action" value="approve">
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('approveModal').style.display='none'">Cancel</button>
      <button type="button" class="btn btn-approve" onclick="document.getElementById('approveForm').submit()">Approve</button>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fa fa-times-circle"></i> Reject Record</h3>
      <span class="close" onclick="document.getElementById('rejectModal').style.display='none'">&times;</span>
    </div>
    <div class="modal-body">
      <p>Please provide a reason for rejecting this record:</p>
      <form id="rejectForm" method="post" action="g2g_pending_approvals.php">
        <input type="hidden" name="approval_id" id="reject_approval_id">
        <input type="hidden" name="action" value="reject">
        <div class="form-group">
          <textarea name="remarks" class="form-control" rows="3" required></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
      <button type="button" class="btn btn-reject" onclick="document.getElementById('rejectForm').submit()">Reject</button>
    </div>
  </div>
</div>

<script>
function showApproveModal(approvalId) {
  document.getElementById('approve_approval_id').value = approvalId;
  document.getElementById('approveModal').style.display = 'block';
}

function showRejectModal(approvalId) {
  document.getElementById('reject_approval_id').value = approvalId;
  document.getElementById('rejectModal').style.display = 'block';
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target.className === 'modal') {
    event.target.style.display = 'none';
  }
}
</script>

<!-- No need for additional stylesheet reference as it's already included in _head.php -->
