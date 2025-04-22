<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: direct_hire.php?error=No record ID specified');
    exit();
}

$record_id = (int)$_GET['id'];

try {
    // Get record details
    $stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception("Record not found");
    }
    
    // Get attached documents
    $docs_stmt = $pdo->prepare("SELECT * FROM direct_hire_documents WHERE direct_hire_id = ? ORDER BY uploaded_at DESC");
    $docs_stmt->execute([$record_id]);
    $documents = $docs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    header('Location: direct_hire.php?error=' . urlencode($e->getMessage()));
    exit();
}

$pageTitle = "View Record - Direct Hire";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    include '_header.php';
    ?>

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
            <a href="direct_hire_edit.php?id=<?= $record['id'] ?>" class="btn btn-primary">
              <i class="fa fa-edit"></i> Edit
            </a>
            <a href="javascript:void(0)" onclick="confirmDelete(<?= $record['id'] ?>)" class="btn btn-danger">
              <i class="fa fa-trash"></i> Delete
            </a>
            <a href="direct_hire.php?tab=<?= urlencode($record['type']) ?>" class="btn btn-secondary">
              <i class="fa fa-arrow-left"></i> Back to List
            </a>
          </div>
        </div>
        
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
                <div class="detail-value"><?= !empty($record['evaluated']) ? date('F j, Y', strtotime($record['evaluated'])) : '<span class="no-data">Not set</span>' ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">For Confirmation</div>
                <div class="detail-value"><?= !empty($record['for_confirmation']) ? date('F j, Y', strtotime($record['for_confirmation'])) : '<span class="no-data">Not set</span>' ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Emailed to DHAD</div>
                <div class="detail-value"><?= !empty($record['emailed_to_dhad']) ? date('F j, Y', strtotime($record['emailed_to_dhad'])) : '<span class="no-data">Not set</span>' ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Received from DHAD</div>
                <div class="detail-value"><?= !empty($record['received_from_dhad']) ? date('F j, Y', strtotime($record['received_from_dhad'])) : '<span class="no-data">Not set</span>' ?></div>
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
            <h3>Documents</h3>
            <?php if (count($documents) > 0): ?>
            <div class="documents-container">
              <?php foreach ($documents as $doc): ?>
              <div class="document-item">
                <div class="document-icon">
                  <i class="fa <?= getFileIcon($doc['file_type']) ?>"></i>
                </div>
                <div class="document-details">
                  <div class="document-name"><?= htmlspecialchars($doc['original_filename']) ?></div>
                  <div class="document-meta">
                    <span class="document-size"><?= formatFileSize($doc['file_size']) ?></span>
                    <span class="document-date">Uploaded <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?></span>
                  </div>
                </div>
                <div class="document-actions">
                  <a href="download_document.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-outline" title="Download">
                    <i class="fa fa-download"></i>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <h3>Confirm Delete</h3>
      <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
    </div>
    <div class="modal-body" style="text-align: center;">
      <p>Are you sure you want to delete this record? This action cannot be undone.</p>
      <div class="modal-actions" style="justify-content: center; margin-top: 20px;">
        <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
        <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
  function confirmDelete(id) {
    const modal = document.getElementById('deleteModal');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    // Set the onclick event for the confirm button
    confirmBtn.onclick = function() {
      window.location.href = 'direct_hire_delete.php?id=' + id;
    };
    
    // Show the modal
    modal.style.display = 'flex';
  }
  
  function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
  }
  
  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
      closeDeleteModal();
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

function formatFileSize($size) {
  $units = ['B', 'KB', 'MB', 'GB'];
  $i = 0;
  
  while ($size >= 1024 && $i < count($units) - 1) {
    $size /= 1024;
    $i++;
  }
  
  return round($size, 1) . ' ' . $units[$i];
}
?>

<style>
  .record-view-wrapper {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
  }
  
  .record-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
    background-color: #f8f9fa;
  }
  
  .record-title h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
  }
  
  .record-subtitle {
    display: flex;
    gap: 15px;
    color: #666;
  }
  
  .control-no {
    font-weight: 500;
  }
  
  .record-actions {
    display: flex;
    gap: 10px;
  }
  
  .record-details {
    padding: 20px;
  }
  
  .record-section {
    margin-bottom: 30px;
  }
  
  .record-section h3 {
    font-size: 18px;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
  }
  
  .detail-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px 15px;
  }
  
  .detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  
  .detail-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
  }
  
  .detail-value {
    font-size: 15px;
  }
  
  .no-data {
    color: #999;
    font-style: italic;
  }
  
  .status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
  }
  
  .status-badge.pending {
    background-color: #ffeeba;
    color: #856404;
  }
  
  .status-badge.approved {
    background-color: #d4edda;
    color: #155724;
  }
  
  .status-badge.denied {
    background-color: #f8d7da;
    color: #721c24;
  }
  
  .notes-container {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    border-left: 3px solid #ddd;
  }
  
  .documents-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  
  .document-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-radius: 4px;
    background-color: #f9f9f9;
    border: 1px solid #eee;
  }
  
  .document-icon {
    font-size: 24px;
    margin-right: 15px;
    color: #6c757d;
  }
  
  .document-details {
    flex-grow: 1;
  }
  
  .document-name {
    font-weight: 500;
    margin-bottom: 3px;
  }
  
  .document-meta {
    display: flex;
    gap: 15px;
    color: #666;
    font-size: 12px;
  }
  
  .btn {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
  }
  
  .btn-primary {
    background-color: #007bff;
    border: 1px solid #007bff;
    color: white;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    border: 1px solid #6c757d;
    color: white;
  }
  
  .btn-danger {
    background-color: #dc3545;
    border: 1px solid #dc3545;
    color: white;
  }
  
  .btn-sm {
    padding: 4px 8px;
    font-size: 13px;
  }
  
  .btn-outline {
    background-color: transparent;
    border: 1px solid #ccc;
    color: #333;
  }
  
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
  }
  
  .modal-content {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    overflow: hidden;
    width: 100%;
    max-width: 500px;
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
  }
  
  .modal-header h3 {
    margin: 0;
    border-bottom: none;
    padding-bottom: 0;
    font-size: 18px;
  }
  
  .modal-close {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #888;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
  }
  
  .btn-cancel {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    color: #333;
  }
  
  @media (max-width: 768px) {
    .record-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .record-actions {
      margin-top: 15px;
      width: 100%;
      justify-content: space-between;
    }
    
    .detail-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 576px) {
    .detail-grid {
      grid-template-columns: 1fr;
    }
  }
</style> 