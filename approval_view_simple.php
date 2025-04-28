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
    /* Fix Bootstrap modal backdrop and z-index issues */   
    
    .modal-backdrop.show {
      opacity: 0.5 !important;
      z-index: 1050 !important;
    }

    .modal {
      z-index: 1060 !important;
    }

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
                        <td class="action-icons">
                          <a href="approval_detail_view.php?id=<?= $approval['approval_id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> View
                          </a>
                          <button class="btn btn-sm btn-success approve-record"
                            data-bs-toggle="modal"
                            data-bs-target="#approveModal"
                            data-id="<?= $approval['approval_id'] ?>"
                            data-direct-hire-id="<?= $approval['direct_hire_id'] ?>"
                            data-name="<?= htmlspecialchars($approval['name']) ?>">
                            <i class="fas fa-check"></i> Approve
                          </button>
                          <button class="btn btn-sm btn-danger deny-record"
                            data-bs-toggle="modal"
                            data-bs-target="#denyModal"
                            data-id="<?= $approval['approval_id'] ?>"
                            data-direct-hire-id="<?= $approval['direct_hire_id'] ?>"
                            data-name="<?= htmlspecialchars($approval['name']) ?>">
                            <i class="fas fa-times"></i> Deny
                          </button>
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
  <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="approveModalLabel">Approve Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="">
          <div class="modal-body">
            <p>Are you sure you want to approve the record for <strong id="approve_name"></strong>?</p>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="approval_id" id="approve_approval_id">
            <input type="hidden" name="direct_hire_id" id="approve_direct_hire_id">
            <div class="mb-3">
              <label for="approve_comments" class="form-label">Comments (Optional)</label>
              <textarea class="form-control" id="approve_comments" name="comments" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">Approve</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Deny Modal -->
  <div class="modal fade" id="denyModal" tabindex="-1" aria-labelledby="denyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="denyModalLabel">Deny Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="">
          <div class="modal-body">
            <p>Are you sure you want to deny the record for <strong id="deny_name"></strong>?</p>
            <input type="hidden" name="action" value="deny">
            <input type="hidden" name="approval_id" id="deny_approval_id">
            <input type="hidden" name="direct_hire_id" id="deny_direct_hire_id">
            <div class="mb-3">
              <label for="deny_comments" class="form-label">Reason for Denial (Required)</label>
              <textarea class="form-control" id="deny_comments" name="comments" rows="3" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger">Deny</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
    document.addEventListener('shown.bs.modal', function(event) {
      const modal = event.target;
      setTimeout(() => {
        modal.focus();
      }, 50);
    }, true);
  </script>
</body>

</html>