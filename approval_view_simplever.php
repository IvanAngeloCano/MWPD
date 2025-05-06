<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'session.php';
require_once 'connection.php';
require_once 'notifications.php'; // Include notification functions
$pageTitle = "Approval View - MWPD Filing System";
include '_head.php';

// Function to log errors
function logError($message) {
    file_put_contents('approval_error_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Check if user has Regional Director role
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

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
                
                // If we have the direct_hire_id, update the direct_hire record status
                if ($direct_hire_id > 0) {
                    $check_dh = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
                    $check_dh->execute([$direct_hire_id]);
                    
                    if ($check_dh->rowCount() > 0) {
                        $update_dh = $pdo->prepare("UPDATE direct_hire SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
                        $update_dh->execute([$new_status, $_SESSION['user_id'], $direct_hire_id]);
                    } else {
                        logError("Direct hire record not found for ID: $direct_hire_id");
                    }
                }
                
                // Send notification to the user who submitted the record
                if ($submitted_by > 0) {
                    notifyApprovalDecision($direct_hire_id, $approval_id, $submitted_by, $name, $new_status, $comments);
                    logError("Notification sent to user $submitted_by about $name approval status: $new_status");
                } else {
                    logError("No submitted_by user found for approval ID: $approval_id");
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

// Fetch all pending approvals
try {
    // Check if the required columns exist in the table
    $checkColumnQuery = "SHOW COLUMNS FROM direct_hire_clearance_approvals LIKE 'record_type'";
    $columnCheck = $pdo->query($checkColumnQuery);
    
    if ($columnCheck->rowCount() == 0) {
        // Add the record_type column if it doesn't exist
        $pdo->exec("ALTER TABLE direct_hire_clearance_approvals ADD COLUMN record_type ENUM('direct_hire','clearance','gov_to_gov','balik_manggagawa') NOT NULL DEFAULT 'clearance' AFTER document_id");
        logError("Added record_type column to direct_hire_clearance_approvals");
    }
    
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
} catch (PDOException $e) {
    $error_message = "Error fetching pending approvals: " . $e->getMessage();
    logError("Error fetching approvals: " . $e->getMessage());
}

// Format date for display
function format_date($date_string) {
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
        /* Prevent backdrop from blocking pointer events on modal buttons */
        .modal-backdrop {
          pointer-events: none !important;
        }
        .modal.show {
          pointer-events: auto !important;
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
            <div class="page-header mb-4">
              <h1>Pending Approvals</h1>
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

  <script>
    // Handle approve modal
    document.querySelectorAll('.approve-record').forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('approve_approval_id').value = this.getAttribute('data-id');
        document.getElementById('approve_direct_hire_id').value = this.getAttribute('data-direct-hire-id');
        document.getElementById('approve_name').textContent = this.getAttribute('data-name');
      });
    });

    // Handle deny modal
    document.querySelectorAll('.deny-record').forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('deny_approval_id').value = this.getAttribute('data-id');
        document.getElementById('deny_direct_hire_id').value = this.getAttribute('data-direct-hire-id');
        document.getElementById('deny_name').textContent = this.getAttribute('data-name');
      });
    });

    // Extra: force modal focus and prevent backdrop issues
    document.addEventListener('shown.bs.modal', function (event) {
      const modal = event.target;
      setTimeout(() => { modal.focus(); }, 50);
    }, true);

    function showApproveModal(approval_id, direct_hire_id, name) {
      document.getElementById('approve_approval_id').value = approval_id;
      document.getElementById('approve_direct_hire_id').value = direct_hire_id;
      document.getElementById('approve_name').textContent = name;
      var approveModal = document.getElementById('approveModal');
      approveModal.style.display = 'block';
    }

    function showDenyModal(approval_id, direct_hire_id, name) {
      document.getElementById('deny_approval_id').value = approval_id;
      document.getElementById('deny_direct_hire_id').value = direct_hire_id;
      document.getElementById('deny_name').textContent = name;
      var denyModal = document.getElementById('denyModal');
      denyModal.style.display = 'block';
    }

    document.querySelectorAll('.close-modal').forEach(button => {
      button.addEventListener('click', function() {
        var modalId = this.getAttribute('data-modal-id');
        var modal = document.getElementById(modalId);
        modal.style.display = 'none';
      });
    });

    window.addEventListener('click', function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
      }
    });
  </script>
</body>
</html>
