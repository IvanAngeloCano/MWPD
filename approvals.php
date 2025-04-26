<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Record Approvals - MWPD Filing System";
include '_head.php';

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
    if (isset($_POST['action']) && isset($_POST['record_id']) && isset($_POST['record_type'])) {
        $action = $_POST['action'];
        $record_id = $_POST['record_id'];
        $record_type = $_POST['record_type'];
        $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
        
        try {
            // Update record status based on action
            $new_status = ($action === 'approve') ? 'approved' : 'denied';
            
            // Handle clearance document approvals
            if ($record_type === 'clearance') {
                // Update clearance approval status
                $update_stmt = $pdo->prepare("UPDATE direct_hire_clearance_approvals 
                                           SET status = ?, comments = ?, approved_by = ?, updated_at = NOW() 
                                           WHERE id = ?");
                $update_stmt->execute([$new_status, $comments, $_SESSION['user_id'], $record_id]);
                
                $success_message = "Clearance document has been $new_status successfully.";
            } else {
                // Determine which table to update based on record type
                $table_name = '';
                switch ($record_type) {
                    case 'direct_hire':
                        $table_name = 'direct_hire';
                        break;
                    case 'gov_to_gov':
                        $table_name = 'gov_to_gov';
                        break;
                    case 'balik_manggagawa':
                        $table_name = 'balik_manggagawa';
                        break;
                    // Add other record types as needed
                }
                
                if (!empty($table_name)) {
                    // Update the record status
                    $update_stmt = $pdo->prepare("UPDATE $table_name SET status = ?, comments = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->execute([$new_status, $comments, $record_id]);
                    
                    $success_message = "Record has been $new_status successfully.";
                } else {
                    $error_message = "Invalid record type.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all pending approvals
try {
    // Use only direct_hire_clearance_approvals table for all approvals
    $clearance_approvals_db = 'direct_hire_clearance_approvals';
    $main_db = 'direct_hire';
    $main_docs_db = 'direct_hire_documents';
    
    $pending_approvals = [];
    
    if ($pdo->query("SHOW TABLES LIKE '$clearance_approvals_db'")->rowCount() > 0) {
        // Get all pending approvals from the consolidated table
        $approval_stmt = $pdo->query("
            SELECT a.id, a.direct_hire_id as record_id, a.record_type,
                   d.name, 
                   CASE 
                     WHEN a.record_type = 'clearance' THEN 'Clearance Document'
                     ELSE d.jobsite
                   END as position,
                   '' as employer, 
                   CASE 
                     WHEN a.record_type = 'clearance' THEN 'Document'
                     ELSE d.type
                   END as country,
                   a.created_at,
                   doc.filename, doc.id as doc_id
            FROM $clearance_approvals_db a
            JOIN $main_db d ON a.direct_hire_id = d.id
            LEFT JOIN $main_docs_db doc ON a.document_id = doc.id
            WHERE a.status = 'pending'
            ORDER BY a.created_at DESC
        ");
        $pending_approvals = $approval_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get pending gov-to-gov records if table exists
    $gov_to_gov_pending = [];
    if ($pdo->query("SHOW TABLES LIKE 'gov_to_gov'")->rowCount() > 0) {
        $gov_to_gov_stmt = $pdo->query("
            SELECT id, 'gov_to_gov' as record_type, name, position, employer, country, created_at 
            FROM gov_to_gov 
            WHERE status = 'pending' 
            ORDER BY created_at DESC
        ");
        $gov_to_gov_pending = $gov_to_gov_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get pending balik manggagawa records if table exists
    $balik_manggagawa_pending = [];
    if ($pdo->query("SHOW TABLES LIKE 'balik_manggagawa'")->rowCount() > 0) {
        $balik_manggagawa_stmt = $pdo->query("
            SELECT id, 'balik_manggagawa' as record_type, name, position, employer, country, created_at 
            FROM balik_manggagawa 
            WHERE status = 'pending' 
            ORDER BY created_at DESC
        ");
        $balik_manggagawa_pending = $balik_manggagawa_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Combine all pending approvals
    $pending_approvals = array_merge($pending_approvals, $gov_to_gov_pending, $balik_manggagawa_pending);
    
    // Sort by created_at (most recent first)
    usort($pending_approvals, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
} catch (PDOException $e) {
    $error_message = "Error fetching pending approvals: " . $e->getMessage();
}

// Format date for display
function format_date($date_string) {
    $date = new DateTime($date_string);
    return $date->format('F j, Y'); // April 23, 2025 format
}
?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="container">
          <div class="page-header">
            <h1>Record Approvals</h1>
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

          <div class="card">
            <div class="card-header">
              <h5>Pending Approvals</h5>
            </div>
            <div class="card-body">
              <?php if (count($pending_approvals) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Employer</th>
                        <th>Country</th>
                        <th>Type</th>
                        <th>Date Submitted</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($pending_approvals as $record): ?>
                        <tr>
                          <td><?= htmlspecialchars($record['name']) ?></td>
                          <td><?= htmlspecialchars($record['position']) ?></td>
                          <td><?= htmlspecialchars($record['employer']) ?></td>
                          <td><?= htmlspecialchars($record['country']) ?></td>
                          <td>
                            <?php if ($record['record_type'] === 'clearance'): ?>
                              <span class="badge bg-info">
                                Clearance Document
                              </span>
                            <?php else: ?>
                              <span class="badge bg-<?= $record['record_type'] === 'direct_hire' ? 'primary' : ($record['record_type'] === 'gov_to_gov' ? 'success' : 'warning') ?>">
                                <?= htmlspecialchars(str_replace('_', ' ', ucwords($record['record_type']))) ?>
                              </span>
                            <?php endif; ?>
                          </td>
                          <td><?= format_date($record['created_at']) ?></td>
                          <td>
                            <?php if ($record['record_type'] === 'clearance'): ?>
                              <?php 
                              // Document filename is already included in the query results
                              $doc_filename = $record['filename'];
                              ?>
                              <a href="uploads/direct_hire_clearance/<?= urlencode($doc_filename) ?>" target="_blank" class="btn btn-sm btn-secondary">
                                <i class="fa fa-file"></i> View Document
                              </a>
                              <button type="button" class="btn btn-sm btn-success approve-record"
                                data-id="<?= $record['id'] ?>"
                                data-type="clearance"
                                data-name="<?= htmlspecialchars($record['name']) ?> Clearance"
                                data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fa fa-check"></i> Approve
                              </button>
                              <button type="button" class="btn btn-sm btn-danger deny-record"
                                data-id="<?= $record['id'] ?>"
                                data-type="clearance"
                                data-name="<?= htmlspecialchars($record['name']) ?> Clearance"
                                data-bs-toggle="modal" data-bs-target="#denyModal">
                                <i class="fa fa-times"></i> Deny
                              </button>
                            <?php else: ?>
                              <button type="button" class="btn btn-sm btn-success approve-record"
                                data-id="<?= $record['id'] ?>"
                                data-type="<?= $record['record_type'] ?>"
                                data-name="<?= htmlspecialchars($record['name']) ?>"
                                data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="fa fa-check"></i> Approve
                              </button>
                              <button type="button" class="btn btn-sm btn-danger deny-record"
                                data-id="<?= $record['id'] ?>"
                                data-type="<?= $record['record_type'] ?>"
                                data-name="<?= htmlspecialchars($record['name']) ?>"
                                data-bs-toggle="modal" data-bs-target="#denyModal">
                                <i class="fa fa-times"></i> Deny
                              </button>
                            <?php endif; ?>
                            <a href="<?= $record['record_type'] ?>_view.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-info">
                              <i class="fa fa-eye"></i> View
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> No pending approvals at this time.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Recently Approved/Denied Records Section -->
          <div class="card mt-4">
            <div class="card-header">
              <h5>Recent Decisions</h5>
            </div>
            <div class="card-body">
              <?php
              // Fetch recent decisions (approved or denied records)
              try {
                  $recent_decisions_stmt = $pdo->query("
                      SELECT id, 'direct_hire' as record_type, name, status, updated_at 
                      FROM direct_hire 
                      WHERE status IN ('approved', 'denied') 
                      ORDER BY updated_at DESC 
                      LIMIT 10
                  ");
                  $recent_decisions = $recent_decisions_stmt->fetchAll(PDO::FETCH_ASSOC);
              } catch (PDOException $e) {
                  $recent_decisions = [];
              }
              ?>
              
              <?php if (count($recent_decisions) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Decision Date</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recent_decisions as $decision): ?>
                        <tr>
                          <td><?= htmlspecialchars($decision['name']) ?></td>
                          <td>
                            <span class="badge bg-<?= $decision['record_type'] === 'direct_hire' ? 'primary' : ($decision['record_type'] === 'gov_to_gov' ? 'success' : 'info') ?>">
                              <?= htmlspecialchars(str_replace('_', ' ', ucwords($decision['record_type']))) ?>
                            </span>
                          </td>
                          <td>
                            <span class="badge bg-<?= $decision['status'] === 'approved' ? 'success' : 'danger' ?>">
                              <?= ucfirst($decision['status']) ?>
                            </span>
                          </td>
                          <td><?= format_date($decision['updated_at']) ?></td>
                          <td>
                            <a href="<?= $decision['record_type'] ?>_view.php?id=<?= $decision['id'] ?>" class="btn btn-sm btn-info">
                              <i class="fa fa-eye"></i> View
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  <i class="fa fa-info-circle"></i> No recent decisions found.
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
            <input type="hidden" name="record_id" id="approve_record_id">
            <input type="hidden" name="record_type" id="approve_record_type">
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
            <input type="hidden" name="record_id" id="deny_record_id">
            <input type="hidden" name="record_type" id="deny_record_type">
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

  <script>
    // Handle approve modal
    document.querySelectorAll('.approve-record').forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('approve_record_id').value = this.getAttribute('data-id');
        document.getElementById('approve_record_type').value = this.getAttribute('data-type');
        document.getElementById('approve_name').textContent = this.getAttribute('data-name');
      });
    });

    // Handle deny modal
    document.querySelectorAll('.deny-record').forEach(button => {
      button.addEventListener('click', function() {
        document.getElementById('deny_record_id').value = this.getAttribute('data-id');
        document.getElementById('deny_record_type').value = this.getAttribute('data-type');
        document.getElementById('deny_name').textContent = this.getAttribute('data-name');
      });
    });
  </script>
</body>
</html>
