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

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'direct_hire';

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
                        $table_name = 'bm';
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

// Fetch pending approvals based on active tab
try {
    $pending_approvals = [];
    
    // Direct Hire approvals
    if ($active_tab === 'direct_hire') {
        // Use direct_hire_clearance_approvals table for direct hire approvals
        $clearance_approvals_db = 'direct_hire_clearance_approvals';
        $main_db = 'direct_hire';
        $main_docs_db = 'direct_hire_documents';
        
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
    }
    
    // Gov-to-Gov approvals
    elseif ($active_tab === 'gov_to_gov') {
        // Get pending gov-to-gov records if table exists
        if ($pdo->query("SHOW TABLES LIKE 'gov_to_gov'")->rowCount() > 0) {
            $gov_to_gov_stmt = $pdo->query("
                SELECT id, 'gov_to_gov' as record_type, name, position, employer, country, created_at 
                FROM gov_to_gov 
                WHERE status = 'pending' 
                ORDER BY created_at DESC
            ");
            $pending_approvals = $gov_to_gov_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Balik Manggagawa approvals
    elseif ($active_tab === 'balik_manggagawa') {
        // Get pending balik manggagawa records if table exists
        if ($pdo->query("SHOW TABLES LIKE 'bm'")->rowCount() > 0) {
            $balik_manggagawa_stmt = $pdo->query("
                SELECT bmid as id, 'balik_manggagawa' as record_type, 
                       CONCAT(last_name, ', ', given_name, ' ', middle_name) as name, 
                       destination as position, 
                       '' as employer, 
                       destination as country, 
                       NOW() as created_at 
                FROM bm 
                WHERE status = 'pending' OR status IS NULL
                ORDER BY bmid DESC
            ");
            $pending_approvals = $balik_manggagawa_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
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
      <?php
      $currentFile = basename($_SERVER['PHP_SELF']);
      $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
      $pageTitle = ucwords(str_replace(['-', '_'], ' ', $fileWithoutExtension));
      include '_header.php';
      ?>

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

          <!-- Tabs Navigation -->
          <div style="display: flex; gap: 0.5rem;" class="process-page-top">
            <div style="display: flex; gap: 0.5rem;" class="tabs">
              <a href="?tab=direct_hire" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'direct_hire' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'direct_hire' ? 'white' : 'inherit' ?>;">Direct Hire</a>
              <a href="?tab=gov_to_gov" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'gov_to_gov' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'gov_to_gov' ? 'white' : 'inherit' ?>;">Gov-to-Gov</a>
              <a href="?tab=balik_manggagawa" style="padding: 0 1rem; display: flex; align-items: center; border: none; background-color: <?= $active_tab === 'balik_manggagawa' ? '#246EE9' : '#eee' ?>; cursor: pointer; border-radius: 6px; font-size: 1rem; text-decoration: none; height: 2rem; color: <?= $active_tab === 'balik_manggagawa' ? 'white' : 'inherit' ?>;">Balik Manggagawa</a>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h5>Pending Approvals - <?= ucwords(str_replace('_', ' ', $active_tab)) ?></h5>
            </div>
            <div class="card-body">
              <?php if (count($pending_approvals) > 0): ?>
                <div class="table-responsive">
                  <table class="table table-striped" style="width: 100%; border-collapse: collapse;">
                    <thead>
                      <tr>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; vertical-align: middle;">Name</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; vertical-align: middle;">Position</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; vertical-align: middle;">Employer</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; vertical-align: middle;">Country</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; vertical-align: middle;">Type</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; vertical-align: middle;">Date Submitted</th>
                        <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6; vertical-align: middle;">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($pending_approvals as $i => $record): ?>
                        <tr style="background-color: <?= $i % 2 === 0 ? '#f2f2f2' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6;">
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($record['name']) ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($record['position']) ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($record['employer']) ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($record['country']) ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;">
                            <?php if ($record['record_type'] === 'clearance'): ?>
                              <span style="padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: normal; background-color: #17a2b8; color: white;">Clearance</span>
                            <?php elseif ($record['record_type'] === 'direct_hire'): ?>
                              <span style="padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: normal; background-color: #007bff; color: white;">Direct Hire</span>
                            <?php elseif ($record['record_type'] === 'gov_to_gov'): ?>
                              <span style="padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: normal; background-color: #28a745; color: white;">Gov-to-Gov</span>
                            <?php elseif ($record['record_type'] === 'balik_manggagawa'): ?>
                              <span style="padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: normal; background-color: #ffc107; color: #212529;">Balik Manggagawa</span>
                            <?php endif; ?>
                          </td>
                          <td style="padding: 12px 15px; vertical-align: middle;"><?= isset($record['created_at']) ? format_date($record['created_at']) : 'N/A' ?></td>
                          <td style="padding: 12px 15px; vertical-align: middle;">
                            <div style="display: flex; gap: 5px;">
                              <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#approvalModal" 
                                      data-record-id="<?= $record['id'] ?>" 
                                      data-record-type="<?= $record['record_type'] ?>" 
                                      data-record-name="<?= htmlspecialchars($record['name']) ?>"
                                      data-action="approve">
                                <i class="fas fa-check"></i> Approve
                              </button>
                              <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#approvalModal" 
                                      data-record-id="<?= $record['id'] ?>" 
                                      data-record-type="<?= $record['record_type'] ?>" 
                                      data-record-name="<?= htmlspecialchars($record['name']) ?>"
                                      data-action="deny">
                                <i class="fas fa-times"></i> Deny
                              </button>
                              
                              <?php if (isset($record['filename']) && !empty($record['filename'])): ?>
                                <a href="display_document.php?id=<?= $record['doc_id'] ?>" class="btn btn-sm btn-info" target="_blank">
                                  <i class="fas fa-file"></i> View Document
                                </a>
                              <?php endif; ?>
                              
                              <?php if ($record['record_type'] === 'direct_hire'): ?>
                                <a href="direct_hire_view.php?id=<?= $record['record_id'] ?>" class="btn btn-sm btn-secondary" target="_blank">
                                  <i class="fas fa-eye"></i> View Record
                                </a>
                              <?php elseif ($record['record_type'] === 'gov_to_gov'): ?>
                                <a href="gov_to_gov_view.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">
                                  <i class="fas fa-eye"></i> View Record
                                </a>
                              <?php elseif ($record['record_type'] === 'balik_manggagawa'): ?>
                                <a href="balik_manggagawa_edit.php?bmid=<?= $record['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">
                                  <i class="fas fa-eye"></i> View Record
                                </a>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="alert alert-info">
                  No pending approvals found for <?= ucwords(str_replace('_', ' ', $active_tab)) ?>.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Approval Modal -->
  <div class="modal fade" id="approvalModal" tabindex="-1" role="dialog" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="approvalModalLabel">Confirm Action</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="POST" action="">
          <div class="modal-body">
            <input type="hidden" name="record_id" id="modal_record_id">
            <input type="hidden" name="record_type" id="modal_record_type">
            <input type="hidden" name="action" id="modal_action">
            
            <p id="modal_message"></p>
            
            <div class="form-group">
              <label for="comments">Comments:</label>
              <textarea class="form-control" name="comments" id="comments" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" id="confirm_button">Confirm</button>
          </div>
        </form>
      </div>
    </div>
  </div>



  <script>
    $(document).ready(function() {
      // Handle modal data
      $('#approvalModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var recordId = button.data('record-id');
        var recordType = button.data('record-type');
        var recordName = button.data('record-name');
        var action = button.data('action');
        
        var modal = $(this);
        modal.find('#modal_record_id').val(recordId);
        modal.find('#modal_record_type').val(recordType);
        modal.find('#modal_action').val(action);
        
        var actionText = action === 'approve' ? 'approve' : 'deny';
        var recordTypeText = recordType === 'clearance' ? 'clearance document' : 'record';
        
        modal.find('#modal_message').text('Are you sure you want to ' + actionText + ' this ' + recordTypeText + ' for ' + recordName + '?');
        
        var confirmButton = modal.find('#confirm_button');
        confirmButton.removeClass('btn-success btn-danger');
        
        if (action === 'approve') {
          confirmButton.addClass('btn-success').text('Approve');
        } else {
          confirmButton.addClass('btn-danger').text('Deny');
        }
      });
    });
  </script>
</body>
</html>
