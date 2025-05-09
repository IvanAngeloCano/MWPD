<?php
include 'session.php';
require_once 'connection.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log function for debugging
function logDebug($message) {
    file_put_contents('clearance_approval_view_debug.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

logDebug("Clearance Approval View loaded");

// Ensure only regional directors can access this page
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    $_SESSION['error_message'] = "Access denied. Only Regional Directors can access this page.";
    logDebug("Access denied - not a regional director");
    header('Location: index.php');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    logDebug("No record ID specified");
    header('Location: pending_approvals.php?error=No record ID specified');
    exit();
}

$record_id = (int)$_GET['id'];
logDebug("Record ID: $record_id");

try {
    // Get record details
    $stmt = $pdo->prepare("SELECT dh.*, u.name as submitted_by_name 
                          FROM direct_hire dh 
                          LEFT JOIN users u ON dh.created_by = u.id
                          WHERE dh.id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        logDebug("Record not found for ID: $record_id");
        throw new Exception("Record not found");
    }
    
    logDebug("Record found: " . json_encode($record));
    
    // Get attached documents
    $docs_stmt = $pdo->prepare("SELECT * FROM direct_hire_documents WHERE direct_hire_id = ? ORDER BY uploaded_at DESC");
    $docs_stmt->execute([$record_id]);
    $documents = $docs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pageTitle = "Review Clearance Approval - " . htmlspecialchars($record['name']);
    include '_head.php';
} catch (Exception $e) {
    logDebug("Error: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: pending_approvals.php');
    exit();
}
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="approval-view-wrapper">
        <div class="page-header">
          <div class="header-content">
            <h2>Review Clearance Approval Request</h2>
            <div class="record-subtitle">
              <span class="control-no"><?= htmlspecialchars($record['control_no']) ?></span>
              <span class="separator">•</span>
              <span class="status-badge status-<?= strtolower($record['status']) ?>"><?= ucfirst($record['status']) ?></span>
            </div>
          </div>
          <div class="header-actions">
            <button type="button" class="btn btn-success" id="generateClearanceBtn" data-record-id="<?= $record_id ?>">
              <i class="fa fa-file-word"></i> Generate Clearance
            </button>
            <a href="pending_approvals.php" class="btn btn-secondary">
              <i class="fa fa-arrow-left"></i> Back to List
            </a>
          </div>
        </div>
        
        <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="success-message">
          <i class="fa fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>
        
        <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="error-message">
          <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <!-- Record Details -->
        <div class="record-details">
          <div class="record-section">
            <h3>Basic Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Control No.</div>
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
                  <span class="status-badge status-<?= strtolower($record['status']) ?>"><?= ucfirst($record['status']) ?></span>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Submitted By</div>
                <div class="detail-value"><?= htmlspecialchars($record['submitted_by_name'] ?? 'N/A') ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Submission Date</div>
                <div class="detail-value"><?= date('F j, Y', strtotime($record['updated_at'])) ?></div>
              </div>
            </div>
          </div>
          
          <div class="record-section">
            <h3>Additional Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <div class="detail-label">Evaluated</div>
                <div class="detail-value">
                  <?= !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : '<span class="no-data">Not evaluated</span>' ?>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Received from DHAD</div>
                <div class="detail-value">
                  <?= !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : '<span class="no-data">Not set</span>' ?>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Evaluator</div>
                <div class="detail-value">
                  <?= !empty($record['evaluator']) ? htmlspecialchars($record['evaluator']) : '<span class="no-data">Not assigned</span>' ?>
                </div>
              </div>
            </div>
          </div>
          
          <div class="record-section">
            <h3>Notes</h3>
            <div class="note-content">
              <?= !empty($record['note']) ? nl2br(htmlspecialchars($record['note'])) : '<span class="no-data">No notes available</span>' ?>
            </div>
          </div>
          
          <?php if (!empty($documents)): ?>
          <div class="record-section">
            <h3>Attached Documents</h3>
            <div class="documents-list">
              <?php foreach ($documents as $doc): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fa <?= getFileIcon($doc['file_type']) ?>"></i>
                </div>
                <div class="document-info">
                  <div class="document-name"><?= htmlspecialchars($doc['file_name'] ?? $doc['original_filename']) ?></div>
                  <div class="document-meta">
                    <span class="document-type"><?= htmlspecialchars($doc['file_type']) ?></span>
                    <span class="document-size"><?= formatFileSize($doc['file_size']) ?></span>
                    <span class="document-date">Uploaded: <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?></span>
                  </div>
                </div>
                <a href="download_document.php?id=<?= $doc['id'] ?>" class="document-action btn btn-sm btn-outline-primary">
                  <i class="fa fa-download"></i> Download
                </a>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        
        <!-- Approval Modal -->
        <div id="approvalModal" class="modal">
          <div class="modal-content">
            <div class="modal-header">
              <h3>Process Clearance Approval</h3>
              <span class="close" onclick="closeApprovalModal()">&times;</span>
            </div>
            <div class="modal-body">
              <form id="approvalForm" action="process_clearance_approval.php" method="POST">
                <input type="hidden" name="record_id" value="<?= $record_id ?>">
                
                <div class="form-group">
                  <label>Decision:</label>
                  <div class="radio-group">
                    <label class="radio-label">
                      <input type="radio" name="decision" value="approved" required> Approve
                    </label>
                    <label class="radio-label">
                      <input type="radio" name="decision" value="denied"> Deny
                    </label>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="comments">Comments:</label>
                  <textarea name="comments" id="comments" rows="4" placeholder="Add your comments here..."></textarea>
                </div>
                
                <div class="modal-actions">
                  <button type="button" class="btn btn-secondary" onclick="closeApprovalModal()">Cancel</button>
                  <button type="submit" class="btn btn-primary">Submit Decision</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<style>
  .approval-view-wrapper { padding: 20px; }
  .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
  .header-content h2 { margin: 0 0 5px 0; font-size: 24px; }
  .record-subtitle { display: flex; align-items: center; gap: 8px; color: #666; }
  .control-no { font-weight: 500; }
  .separator { color: #ccc; }
  .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; text-transform: uppercase; }
  .status-pending { background-color: #fff3cd; color: #856404; }
  .status-approved { background-color: #d4edda; color: #155724; }
  .status-denied { background-color: #f8d7da; color: #721c24; }
  .header-actions { display: flex; gap: 10px; }
  .success-message { background-color: #d4edda; color: #155724; padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center; gap: 10px; }
  .error-message { background-color: #f8d7da; color: #721c24; padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center; gap: 10px; }
  .record-details { background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
  .record-section { padding: 20px; border-bottom: 1px solid #eee; }
  .record-section:last-child { border-bottom: none; }
  .record-section h3 { margin: 0 0 15px 0; font-size: 18px; color: #333; }
  .detail-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  .detail-label { font-size: 14px; color: #666; margin-bottom: 5px; }
  .detail-value { font-size: 15px; color: #333; }
  .no-data { color: #999; font-style: italic; }
  .note-content { background-color: #f9f9f9; padding: 15px; border-radius: 4px; color: #333; line-height: 1.5; }
  .documents-list { display: flex; flex-direction: column; gap: 15px; }
  .document-item { display: flex; align-items: center; background-color: #f9f9f9; padding: 12px 15px; border-radius: 4px; }
  .document-icon { font-size: 24px; color: #007bff; margin-right: 15px; }
  .document-info { flex: 1; }
  .document-name { font-weight: 500; margin-bottom: 3px; }
  .document-meta { font-size: 12px; color: #666; display: flex; gap: 10px; }
  .document-action { white-space: nowrap; }
  .btn { display: inline-flex; align-items: center; gap: 5px; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500; text-decoration: none; }
  .btn-sm { padding: 4px 8px; font-size: 13px; }
  .btn-primary { background-color: #007bff; border: 1px solid #007bff; color: white; }
  .btn-secondary { background-color: #6c757d; border: 1px solid #6c757d; color: white; }
  .btn-success { background-color: #28a745; border: 1px solid #28a745; color: white; }
  .btn-outline-primary { background-color: transparent; border: 1px solid #007bff; color: #007bff; }
  /* Modal styles */
  .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
  .modal-content { background-color: white; border-radius: 8px; width: 500px; max-width: 90%; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
  .modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
  .modal-header h3 { margin: 0; font-size: 18px; }
  .close { font-size: 24px; font-weight: bold; cursor: pointer; color: #999; }
  .modal-body { padding: 20px; }
  .form-group { margin-bottom: 20px; }
  .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
  .radio-group { display: flex; gap: 20px; }
  .radio-label { display: flex; align-items: center; gap: 5px; cursor: pointer; }
  textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; }
  .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
  @media (max-width: 992px) { .detail-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 768px) { .page-header { flex-direction: column; align-items: flex-start; } .header-actions { margin-top: 15px; width: 100%; justify-content: space-between; } }
  @media (max-width: 576px) { .detail-grid { grid-template-columns: 1fr; } .header-actions { flex-direction: column; } .header-actions .btn { width: 100%; justify-content: center; } }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Generate Clearance Document
    const generateClearanceBtn = document.getElementById('generateClearanceBtn');
    if (generateClearanceBtn) {
      generateClearanceBtn.addEventListener('click', function() {
        const recordId = this.getAttribute('data-record-id');
        const button = this;
        
        // Change button state to loading
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
        button.disabled = true;
        
        // Send AJAX request to generate document
        fetch('generate_direct_hire_clearance.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
          body: 'record_id=' + recordId
        })
        .then(response => response.json())
        .then(data => {
          // Reset button state
          button.innerHTML = originalContent;
          button.disabled = false;
          
          if (data.success) {
            // Show success message
            alert(data.message);
            // Open document in new tab
            window.open(data.document_url, '_blank');
            // Show approval modal
            showApprovalModal();
          } else {
            alert('Error: ' + (data.message || 'Failed to generate document'));
          }
        })
        .catch(error => {
          // Reset button state
          button.innerHTML = originalContent;
          button.disabled = false;
          console.error('Error:', error);
          alert('Error generating document. Please try again.');
        });
      });
    }
  });
  function showApprovalModal() {
    const modal = document.getElementById('approvalModal');
    modal.style.display = 'flex';
  }
  function closeApprovalModal() {
    const modal = document.getElementById('approvalModal');
    modal.style.display = 'none';
  }
  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('approvalModal');
    if (event.target === modal) {
      closeApprovalModal();
    }
  };
</script>

<?php
// Helper functions
function getFileIcon($fileType) {
  $fileType = strtolower($fileType);
  if (strpos($fileType, 'pdf') !== false) {
    return 'fa-file-pdf';
  } elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'doc') !== false) {
    return 'fa-file-word';
  } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false || strpos($fileType, 'xls') !== false) {
    return 'fa-file-excel';
  } elseif (strpos($fileType, 'image') !== false || strpos($fileType, 'jpg') !== false || strpos($fileType, 'png') !== false) {
    return 'fa-file-image';
  } else {
    return 'fa-file';
  }
}
function formatFileSize($bytes) {
  if ($bytes >= 1048576) {
    return number_format($bytes / 1048576, 2) . ' MB';
  } elseif ($bytes >= 1024) {
    return number_format($bytes / 1024, 2) . ' KB';
  } else {
    return $bytes . ' bytes';
  }
}
?>
