<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'session.php';
require_once 'connection.php';

// Function to log debugging information
function logDebug($message) {
    file_put_contents('approval_detail_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Approval detail view loaded");

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "No approval ID specified.";
    header('Location: approval_view_simple.php');
    exit();
}

$approval_id = (int)$_GET['id'];
logDebug("Approval ID: $approval_id");

// Get approval details
try {
    // First get the approval record
    $approval_stmt = $pdo->prepare("
        SELECT a.*, u_submitted.full_name as submitted_by_name, u_approved.full_name as approved_by_name
        FROM direct_hire_clearance_approvals a
        LEFT JOIN users u_submitted ON a.submitted_by = u_submitted.id
        LEFT JOIN users u_approved ON a.approved_by = u_approved.id
        WHERE a.id = ?
    ");
    $approval_stmt->execute([$approval_id]);
    $approval = $approval_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$approval) {
        $_SESSION['error_message'] = "Approval record not found.";
        header('Location: approval_view_simple.php');
        exit();
    }
    
    logDebug("Approval record found: " . json_encode($approval));
    
    // Get direct hire record details
    $direct_hire_id = $approval['direct_hire_id'];
    $record_stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
    $record_stmt->execute([$direct_hire_id]);
    $record = $record_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        $_SESSION['error_message'] = "Direct hire record not found.";
        header('Location: approval_view_simple.php');
        exit();
    }
    
    logDebug("Direct hire record found: " . json_encode($record));
    
    // Get related documents
    $docs_stmt = $pdo->prepare("
        SELECT * FROM direct_hire_documents 
        WHERE direct_hire_id = ? 
        ORDER BY uploaded_at DESC
    ");
    $docs_stmt->execute([$direct_hire_id]);
    $documents = $docs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pageTitle = "Approval Details - " . htmlspecialchars($record['name']);
} catch (PDOException $e) {
    logDebug("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error retrieving approval details: " . $e->getMessage();
    header('Location: approval_view_simple.php');
    exit();
}

// Format date for display
function formatDate($date) {
    if (!$date || $date == '0000-00-00') return '<span class="no-data">Not set</span>';
    return date('F j, Y', strtotime($date));
}

// Function to get appropriate icon based on file type
function getFileIcon($fileType) {
    $fileType = strtolower($fileType);
    
    if (strpos($fileType, 'pdf') !== false) {
        return 'fa-file-pdf';
    } elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'doc') !== false) {
        return 'fa-file-word';
    } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'xls') !== false) {
        return 'fa-file-excel';
    } elseif (strpos($fileType, 'image') !== false || strpos($fileType, 'jpg') !== false || strpos($fileType, 'png') !== false) {
        return 'fa-file-image';
    } else {
        return 'fa-file';
    }
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="record-view-wrapper">
        <!-- Record Header -->
        <div class="record-header">
          <div class="record-title">
            <h2><?= htmlspecialchars($record['name']) ?></h2>
            <div class="record-subtitle">
              <span class="control-no"><?= htmlspecialchars($record['control_no']) ?></span>
              <span class="record-type"><?= ucfirst(htmlspecialchars($record['type'])) ?></span>
              <span class="status <?= strtolower($record['status']) ?>">
                <?= ucfirst(htmlspecialchars($record['status'])) ?>
              </span>
            </div>
          </div>
          
          <div class="record-actions">
            <?php if ($approval['status'] == 'pending' && (isset($_SESSION['role']) && (strtolower($_SESSION['role']) === 'regional director'))): ?>
              <button class="btn btn-success approve-record" 
                      data-bs-toggle="modal" 
                      data-bs-target="#approveModal"
                      data-id="<?= $approval['id'] ?>"
                      data-direct-hire-id="<?= $direct_hire_id ?>">
                <i class="fa fa-check"></i> Approve
              </button>
              <button class="btn btn-danger deny-record" 
                      data-bs-toggle="modal" 
                      data-bs-target="#denyModal"
                      data-id="<?= $approval['id'] ?>"
                      data-direct-hire-id="<?= $direct_hire_id ?>">
                <i class="fa fa-times"></i> Deny
              </button>
            <?php endif; ?>
            
            <button type="button" class="btn btn-success" id="generateClearanceBtn" onclick="window.open('generate_clearance.php?id=<?= $direct_hire_id ?>', '_blank')">
              <i class="fa fa-file-pdf"></i> Generate Clearance
            </button>
            
            <a href="approval_view_simple.php" class="btn btn-secondary">
              <i class="fa fa-arrow-left"></i> Back to List
            </a>
          </div>
        </div>
        
        <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>
        
        <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>
        
        <!-- Record Details -->
        <div class="record-details">
          <div class="record-section">
            <h3>Basic Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Control Number</div>
                <div class="detail-value"><?= htmlspecialchars($record['control_no']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Name</div>
                <div class="detail-value"><?= htmlspecialchars($record['name']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Jobsite</div>
                <div class="detail-value"><?= htmlspecialchars($record['jobsite']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Type</div>
                <div class="detail-value"><?= ucfirst(htmlspecialchars($record['type'])) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                  <span class="status-badge <?= strtolower($record['status']) ?>">
                    <?= ucfirst(htmlspecialchars($record['status'])) ?>
                  </span>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Evaluator</div>
                <div class="detail-value"><?= !empty($record['evaluator']) ? htmlspecialchars($record['evaluator']) : '<span class="no-data">Not assigned</span>' ?></div>
              </div>
            </div>
          </div>
          
          <div class="record-section">
            <h3>Important Dates</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Date Evaluated</div>
                <div class="detail-value"><?= formatDate($record['evaluated']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">For Confirmation</div>
                <div class="detail-value"><?= formatDate($record['for_confirmation']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Emailed to DHAD</div>
                <div class="detail-value"><?= formatDate($record['emailed_to_dhad']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Received from DHAD</div>
                <div class="detail-value"><?= formatDate($record['received_from_dhad']) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Created</div>
                <div class="detail-value"><?= date('F j, Y, g:i a', strtotime($record['created_at'])) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Last Updated</div>
                <div class="detail-value"><?= date('F j, Y, g:i a', strtotime($record['updated_at'])) ?></div>
              </div>
            </div>
          </div>
          
          <div class="record-section">
            <h3>Notes</h3>
            <div class="notes-container">
              <?php if (!empty($record['note'])): ?>
              <div class="note-content"><?= nl2br(htmlspecialchars($record['note'])) ?></div>
              <?php else: ?>
              <div class="no-data">No notes available</div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="record-section">
            <h3>Approval Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                  <span class="status-badge <?= strtolower($approval['status']) ?>">
                    <?= ucfirst(htmlspecialchars($approval['status'])) ?>
                  </span>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Submitted By</div>
                <div class="detail-value"><?= htmlspecialchars($approval['submitted_by_name'] ?? 'Unknown') ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Submitted Date</div>
                <div class="detail-value"><?= formatDate($approval['created_at']) ?></div>
              </div>
              <?php if ($approval['status'] != 'pending'): ?>
              <div class="detail-item">
                <div class="detail-label">Approved/Denied By</div>
                <div class="detail-value"><?= htmlspecialchars($approval['approved_by_name'] ?? 'Unknown') ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Decision Date</div>
                <div class="detail-value"><?= formatDate($approval['updated_at']) ?></div>
              </div>
              <?php endif; ?>
            </div>
            
            <?php if (!empty($approval['comments'])): ?>
            <div class="notes-container mt-3">
              <div class="detail-label mb-2">Comments</div>
              <div class="note-content"><?= nl2br(htmlspecialchars($approval['comments'])) ?></div>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="record-section">
            <h3>Documents</h3>
            <?php if (count($documents) > 0): ?>
            <div class="documents-container">
              <?php foreach ($documents as $doc): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fa <?= getFileIcon($doc['file_type'] ?? 'file') ?>"></i>
                </div>
                <div class="document-details">
                  <div class="document-name"><?= htmlspecialchars($doc['original_filename']) ?></div>
                  <div class="document-meta">
                    <span><?= date('F j, Y', strtotime($doc['uploaded_at'] ?? $doc['created_at'])) ?></span>
                    <span><?= isset($doc['file_size']) ? formatFileSize($doc['file_size']) : 'Unknown size' ?></span>
                  </div>
                </div>
                <div class="document-actions">
                  <a href="uploads/<?= htmlspecialchars($doc['filename']) ?>" class="btn btn-sm btn-primary" target="_blank">
                    <i class="fa fa-eye"></i> View
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-data">No documents attached</div>
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
        <h5 class="modal-title" id="approveModalLabel">Approve Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="process_approval.php">
        <div class="modal-body">
          <p>Are you sure you want to approve this request?</p>
          <input type="hidden" name="action" value="approve">
          <input type="hidden" name="approval_id" id="approve_approval_id">
          <input type="hidden" name="record_id" id="approve_direct_hire_id">
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
        <h5 class="modal-title" id="denyModalLabel">Deny Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="process_approval.php">
        <div class="modal-body">
          <p>Are you sure you want to deny this request?</p>
          <input type="hidden" name="action" value="deny">
          <input type="hidden" name="approval_id" id="deny_approval_id">
          <input type="hidden" name="record_id" id="deny_direct_hire_id">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Handle approve modal
  document.querySelectorAll('.approve-record').forEach(button => {
    button.addEventListener('click', function() {
      document.getElementById('approve_approval_id').value = this.getAttribute('data-id');
      document.getElementById('approve_direct_hire_id').value = this.getAttribute('data-direct-hire-id');
    });
  });

  // Handle deny modal
  document.querySelectorAll('.deny-record').forEach(button => {
    button.addEventListener('click', function() {
      document.getElementById('deny_approval_id').value = this.getAttribute('data-id');
      document.getElementById('deny_direct_hire_id').value = this.getAttribute('data-direct-hire-id');
    });
  });
</script>
