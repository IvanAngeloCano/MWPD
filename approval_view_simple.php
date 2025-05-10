<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'session.php';
require_once 'connection.php';
require_once 'notifications.php'; // Include notification functions
$pageTitle = "Approval";
include '_head.php';

// Function to log errors
function logError($message)
{
  file_put_contents('approval_error_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Check if user has Regional Director role
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
  // Redirect to dashboard with error message
  $_SESSION['error_message'] = "You don't have permission to access this page.";
  header('Location: dashboard.php');
  exit();
}

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'direct_hire';

// Initialize variables
$pending_approvals = [];
$error_message = '';
$success_message = '';

// Process approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && isset($_POST['approval_id'])) {
    $action = $_POST['action'];
    $approval_id = (int)$_POST['approval_id'];
    $direct_hire_id = isset($_POST['direct_hire_id']) ? (int)$_POST['direct_hire_id'] : 0;
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

    try {
      // Update record status based on action
      $new_status = ($action === 'approve') ? 'approved' : 'denied';
      $bm_status = ($action === 'approve') ? 'Approved' : 'Declined';

      // Begin transaction
      $pdo->beginTransaction();

      // First check if the approval record exists
      $check_stmt = $pdo->prepare("SELECT * FROM direct_hire_clearance_approvals WHERE id = ?");
      $check_stmt->execute([$approval_id]);
      $approval_record = $check_stmt->fetch(PDO::FETCH_ASSOC);

      if ($approval_record) {
        // Get the direct_hire record to retrieve the name
        $name = "Applicant";
        $submitted_by = $approval_record['submitted_by'] ?? 0;

        if ($direct_hire_id > 0 || (isset($approval_record['direct_hire_id']) && $approval_record['direct_hire_id'] > 0)) {
          if ($direct_hire_id <= 0) {
            $direct_hire_id = $approval_record['direct_hire_id'];
          }

          $dh_stmt = $pdo->prepare("SELECT name FROM direct_hire WHERE id = ?");
          $dh_stmt->execute([$direct_hire_id]);
          $dh_record = $dh_stmt->fetch(PDO::FETCH_ASSOC);
          if ($dh_record && isset($dh_record['name'])) {
            $name = $dh_record['name'];
          }
        }

        // Update the approval record
        $update_stmt = $pdo->prepare("UPDATE direct_hire_clearance_approvals SET status = ?, approved_by = ?, comments = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$new_status, $_SESSION['user_id'], $comments, $approval_id]);

        // Get the direct_hire_id from the approval record if not provided
        if ($direct_hire_id <= 0 && isset($approval_record['direct_hire_id'])) {
          $direct_hire_id = $approval_record['direct_hire_id'];
        }

        // Get the record type from the approval record
        $record_type = $approval_record['record_type'] ?? 'direct_hire';
        
        // Update the appropriate record based on record type
        if ($direct_hire_id > 0) {
          if ($record_type === 'balik_manggagawa') {
            // Update Balik Manggagawa record
            $check_bm = $pdo->prepare("SELECT * FROM bm WHERE bmid = ?");
            $check_bm->execute([$direct_hire_id]);
            
            if ($check_bm->rowCount() > 0) {
              // Set fixed remarks based on status
              $bm_remarks = ($action === 'approve') ? 'Approved' : 'Declined';
              
              // Update status and remarks in the bm table
              $update_bm = $pdo->prepare("UPDATE bm SET status = ?, remarks = ? WHERE bmid = ?");
              $update_bm->execute([$bm_status, $bm_remarks, $direct_hire_id]);
              logError("Updated Balik Manggagawa record ID: $direct_hire_id with status: $bm_status and remarks: $bm_remarks");
            } else {
              logError("Balik Manggagawa record not found for ID: $direct_hire_id");
            }
          } else {
            // Update Direct Hire record
            $check_dh = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
            $check_dh->execute([$direct_hire_id]);

            if ($check_dh->rowCount() > 0) {
              $update_dh = $pdo->prepare("UPDATE direct_hire SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
              $update_dh->execute([$new_status, $_SESSION['user_id'], $direct_hire_id]);
              logError("Updated Direct Hire record ID: $direct_hire_id with status: $new_status");
            } else {
              logError("Direct hire record not found for ID: $direct_hire_id");
            }
          }
        }

        // Send notification to the user who submitted the record
        if ($submitted_by > 0) {
          // Make sure notifications.php is included
          require_once 'notifications.php';
          
          // Ensure notifications table exists
          ensureNotificationsTableExists();
          
          // Log details about the notification being sent
          logError("Sending approval notification to user ID: $submitted_by for record: $direct_hire_id with status: $new_status");
          
          // Send the notification
          $notificationResult = notifyApprovalDecision($direct_hire_id, $approval_id, $submitted_by, $name, $new_status, $comments);
          
          if ($notificationResult) {
            logError("Notification successfully sent to user $submitted_by about $name approval status: $new_status");
          } else {
            logError("Failed to send notification to user $submitted_by about $name approval status: $new_status");
          }
        } else {
          // Try to find the submitter from the direct_hire record if not in approval record
          try {
            $submitter_query = $pdo->prepare("SELECT created_by FROM direct_hire WHERE id = ?");
            $submitter_query->execute([$direct_hire_id]);
            $alt_submitted_by = $submitter_query->fetchColumn();
            
            if ($alt_submitted_by) {
              require_once 'notifications.php';
              ensureNotificationsTableExists();
              
              logError("Found alternative submitter (ID: $alt_submitted_by) from direct_hire record");
              $notificationResult = notifyApprovalDecision($direct_hire_id, $approval_id, $alt_submitted_by, $name, $new_status, $comments);
              
              if ($notificationResult) {
                logError("Notification sent to alternative submitter (ID: $alt_submitted_by)");
              } else {
                logError("Failed to send notification to alternative submitter (ID: $alt_submitted_by)");
              }
            } else {
              logError("No submitter found for record ID: $direct_hire_id");
            }
          } catch (PDOException $e) {
            logError("Error finding alternative submitter: " . $e->getMessage());
          }
        }

        // Commit the transaction
        $pdo->commit();
        $success_message = "Record has been " . ($new_status == 'approved' ? 'approved' : 'denied') . " successfully.";

        // Log the approval action
        logError("Approval action: $action for $name (ID: $direct_hire_id) by user " . $_SESSION['user_id'] . " with status $new_status");
      } else {
        $pdo->rollBack();
        $error_message = "Approval record not found.";
        logError("Approval record not found for ID: $approval_id");
      }
    } catch (PDOException $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $error_message = "Database error: " . $e->getMessage();
      logError("Database error: " . $e->getMessage());
    }
  } else {
    $error_message = "Missing required parameters.";
  }
}

// Create approval table if it doesn't exist
try {
  $tableCheck = $pdo->prepare("SHOW TABLES LIKE 'direct_hire_clearance_approvals'");
  $tableCheck->execute();
  $tableExists = $tableCheck->rowCount() > 0;

  if (!$tableExists) {
    // Create the approval table
    $createTableSQL = "CREATE TABLE direct_hire_clearance_approvals (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            direct_hire_id INT NOT NULL,
            document_id INT NULL,
            record_type ENUM('direct_hire','clearance','gov_to_gov','balik_manggagawa') NOT NULL DEFAULT 'clearance',
            status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending',
            submitted_by INT NULL,
            approved_by INT NULL,
            comments TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (direct_hire_id) REFERENCES direct_hire(id) ON DELETE CASCADE
        )";
    $pdo->exec($createTableSQL);
    logError("Created direct_hire_clearance_approvals table");
  }
} catch (PDOException $e) {
  logError("Error checking/creating table: " . $e->getMessage());
}

// Fetch pending approvals based on active tab
try {
  // Check if the required columns exist in the table
  $checkColumnQuery = "SHOW COLUMNS FROM direct_hire_clearance_approvals LIKE 'record_type'";
  $columnCheck = $pdo->query($checkColumnQuery);

  if ($columnCheck->rowCount() == 0) {
    // Add the record_type column if it doesn't exist
    $pdo->exec("ALTER TABLE direct_hire_clearance_approvals ADD COLUMN record_type ENUM('direct_hire','clearance','gov_to_gov','balik_manggagawa') NOT NULL DEFAULT 'clearance' AFTER document_id");
    logError("Added record_type column to direct_hire_clearance_approvals");
  }

  $pending_approvals = [];
  
  // Direct Hire approvals
  if ($active_tab === 'direct_hire') {
    // Use a basic query to avoid potential issues
    $stmt = $pdo->query("
          SELECT a.id as approval_id, a.direct_hire_id, a.status,
                 d.name, d.jobsite, d.type, a.created_at
          FROM direct_hire_clearance_approvals a
          JOIN direct_hire d ON a.direct_hire_id = d.id
          WHERE a.status = 'pending'
          ORDER BY a.created_at DESC
      ");

    if ($stmt) {
      $pending_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
      logError("Statement failed after execution");
    }
  }
  
  // Gov-to-Gov approvals
  elseif ($active_tab === 'gov_to_gov') {
    // Get pending gov-to-gov records if table exists
    if ($pdo->query("SHOW TABLES LIKE 'gov_to_gov'")->rowCount() > 0) {
      $gov_to_gov_stmt = $pdo->query("
          SELECT id as direct_hire_id, id as approval_id, 'gov_to_gov' as record_type, 
                 name, position as jobsite, country as type, created_at 
          FROM gov_to_gov 
          WHERE status = 'pending' OR status IS NULL
          ORDER BY created_at DESC
      ");
      $pending_approvals = $gov_to_gov_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }
  
  // Balik Manggagawa approvals
  elseif ($active_tab === 'balik_manggagawa') {
    // Get pending balik manggagawa records if table exists
    if ($pdo->query("SHOW TABLES LIKE 'bm'")->rowCount() > 0) {
      // First, check if the status column exists in the bm table
      $check_column = $pdo->prepare("SHOW COLUMNS FROM bm LIKE 'status'");
      $check_column->execute();
      $status_exists = $check_column->rowCount() > 0;
      
      if (!$status_exists) {
        // Add status column if it doesn't exist
        $pdo->exec("ALTER TABLE bm ADD COLUMN status VARCHAR(20) DEFAULT 'draft'");
        logError("Added status column to bm table");
      }
      
      // Get pending approvals from direct_hire_clearance_approvals table first
      $approvals_stmt = $pdo->query("
          SELECT a.id as approval_id, a.direct_hire_id, a.status, a.created_at,
                 CONCAT(b.last_name, ', ', b.given_name, ' ', b.middle_name) as name,
                 b.destination as jobsite,
                 'Balik Manggagawa' as type
          FROM direct_hire_clearance_approvals a
          JOIN bm b ON a.direct_hire_id = b.bmid
          WHERE a.status = 'pending' AND a.record_type = 'balik_manggagawa'
            AND b.status = 'Pending' -- Only show records that are still pending in the bm table
          ORDER BY a.created_at DESC
      ");
      
      $approvals_results = $approvals_stmt->fetchAll(PDO::FETCH_ASSOC);
      
      if (count($approvals_results) > 0) {
        $pending_approvals = $approvals_results;
      } else {
        // If no records in approvals table, get directly from bm table
        $balik_manggagawa_stmt = $pdo->query("
            SELECT bmid as direct_hire_id, bmid as approval_id, 'balik_manggagawa' as record_type, 
                   CONCAT(last_name, ', ', given_name, ' ', middle_name) as name, 
                   destination as jobsite, 
                   'Balik Manggagawa' as type, 
                   NOW() as created_at 
            FROM bm 
            WHERE status = 'Pending'
            ORDER BY bmid DESC
        ");
        $pending_approvals = $balik_manggagawa_stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }
  }
} catch (PDOException $e) {
  $error_message = "Error fetching pending approvals: " . $e->getMessage();
  logError("Error fetching approvals: " . $e->getMessage());
}

// Format date for display
function format_date($date_string)
{
  if (empty($date_string)) return 'N/A';
  return date('M j, Y', strtotime($date_string));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Ensure modal is above sidebar/header if custom z-indexes are used */
    .modal-dialog {
      z-index: 1061 !important;
    }
  </style>
</head>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="approvals-wrapper">
          <div class="approvals-top">
            <div class="page-header">
             
            </div>
            
            <!-- Tabs Navigation -->
            <div style="display: flex; gap: 0.5rem;" class="process-page-top">
              <div style="display: flex; gap: 0.5rem;" class="tabs">
                <a href="?tab=direct_hire" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'direct_hire' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'direct_hire' ? 'white' : 'inherit' ?>;">Direct Hire</a>
                <a href="g2g_pending_approvals.php?tab=gov_to_gov" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'gov_to_gov' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'gov_to_gov' ? 'white' : 'inherit' ?>;">Gov-to-Gov</a>
                <a href="?tab=balik_manggagawa" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'balik_manggagawa' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'balik_manggagawa' ? 'white' : 'inherit' ?>;">Balik Manggagawa</a>
              </div>
            </div>

            <?php if (!empty($error_message)): ?>
              <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
              <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
              </div>
            <?php endif; ?>

            <div class="approvals-table">
              <?php if (count($pending_approvals) > 0): ?>
                <table>
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Jobsite</th>
                      <th>Type</th>
                      <th>Date Submitted</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($pending_approvals as $approval): ?>
                      <tr>
                        <td><?= htmlspecialchars($approval['name']) ?></td>
                        <td><?= htmlspecialchars($approval['jobsite']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($approval['type'])) ?></td>
                        <td><?= format_date($approval['created_at']) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;">
                          <div style="display: flex; gap: 8px;">
                            <a href="approval_detail_view.php?id=<?= $approval['approval_id'] ?>" style="display: inline-flex; align-items: center; gap: 5px; background-color: #007bff; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px; text-decoration: none;">
                              <i class="fas fa-eye"></i> View
                            </a>
                            <button onclick="showApproveModal('<?= $approval['approval_id'] ?>', '<?= $approval['direct_hire_id'] ?>', '<?= htmlspecialchars(addslashes($approval['name'])) ?>')" 
                              style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px;">
                              <i class="fas fa-check"></i> Approve
                            </button>
                            <button onclick="showDenyModal('<?= $approval['approval_id'] ?>', '<?= $approval['direct_hire_id'] ?>', '<?= htmlspecialchars(addslashes($approval['name'])) ?>')" 
                              style="display: inline-flex; align-items: center; gap: 5px; background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 6px 12px; cursor: pointer; font-size: 14px;">
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
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Approve Modal -->
  <div id="approveModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 0; border: 1px solid #888; width: 50%; box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);">
      <div class="modal-header" style="padding: 15px; border-bottom: 1px solid #e9ecef; background-color: #f8f9fa;">
        <h3 style="margin: 0; color: #333;"><i class="fa fa-check-circle" style="color: #28a745;"></i> Approve Record</h3>
        <span class="close-modal" data-modal-id="approveModal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
      </div>
      <form method="POST" action="">
        <div class="modal-body" style="padding: 15px;">
          <p>Are you sure you want to approve the record for <strong id="approve_name"></strong>?</p>
          <input type="hidden" name="action" value="approve">
          <input type="hidden" name="approval_id" id="approve_approval_id">
          <input type="hidden" name="direct_hire_id" id="approve_direct_hire_id">
          <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Comments (Optional)</label>
            <textarea style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;" id="approve_comments" name="comments" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer" style="padding: 15px; border-top: 1px solid #e9ecef; text-align: right;">
          <button type="button" class="close-modal" data-modal-id="approveModal" style="padding: 6px 12px; margin-right: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
          <button type="submit" style="padding: 6px 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Approve</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Deny Modal -->
  <div id="denyModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div class="modal-content" style="background-color: #fefefe; margin: 10% auto; padding: 0; border: 1px solid #888; width: 50%; box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);">
      <div class="modal-header" style="padding: 15px; border-bottom: 1px solid #e9ecef; background-color: #f8f9fa;">
        <h3 style="margin: 0; color: #333;"><i class="fa fa-times-circle" style="color: #dc3545;"></i> Deny Record</h3>
        <span class="close-modal" data-modal-id="denyModal" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
      </div>
      <form method="POST" action="">
        <div class="modal-body" style="padding: 15px;">
          <p>Are you sure you want to deny the record for <strong id="deny_name"></strong>?</p>
          <input type="hidden" name="action" value="deny">
          <input type="hidden" name="approval_id" id="deny_approval_id">
          <input type="hidden" name="direct_hire_id" id="deny_direct_hire_id">
          <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px;">Reason for Denial (Required)</label>
            <textarea style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;" id="deny_comments" name="comments" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer" style="padding: 15px; border-top: 1px solid #e9ecef; text-align: right;">
          <button type="button" class="close-modal" data-modal-id="denyModal" style="padding: 6px 12px; margin-right: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
          <button type="submit" style="padding: 6px 12px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Deny</button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function showApproveModal(approvalId, directHireId, name) {
      document.getElementById('approve_approval_id').value = approvalId;
      document.getElementById('approve_direct_hire_id').value = directHireId;
      document.getElementById('approve_name').textContent = name;
      
      // Show the modal
      const modal = document.getElementById('approveModal');
      modal.style.display = 'block';
    }
    
    function showDenyModal(approvalId, directHireId, name) {
      document.getElementById('deny_approval_id').value = approvalId;
      document.getElementById('deny_direct_hire_id').value = directHireId;
      document.getElementById('deny_name').textContent = name;
      
      // Show the modal
      const modal = document.getElementById('denyModal');
      modal.style.display = 'block';
    }
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
      }
    }
    
    // Handle close buttons
    document.addEventListener('DOMContentLoaded', function() {
      const closeButtons = document.querySelectorAll('.close-modal');
      closeButtons.forEach(button => {
        button.addEventListener('click', function() {
          const modalId = this.getAttribute('data-modal-id');
          document.getElementById(modalId).style.display = 'none';
        });
      });
    });
  </script>
</body>

</html>