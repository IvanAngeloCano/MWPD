<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: direct_hire.php?error=No record ID specified');
    exit();
}

$record_id = (int)$_GET['id'];
$success_message = '';
$error_message = '';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/direct_hire/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

try {
    // Get record details first
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
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate form data
        $required_fields = ['control_no', 'name', 'jobsite'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Get form data
        $control_no = $_POST['control_no'];
        $name = $_POST['name'];
        $jobsite = $_POST['jobsite'];
        $type = $_POST['type'] ?? $record['type'];
        $evaluated = !empty($_POST['evaluated']) ? $_POST['evaluated'] : null;
        $for_confirmation = !empty($_POST['for_confirmation']) ? $_POST['for_confirmation'] : null;
        $emailed_to_dhad = !empty($_POST['emailed_to_dhad']) ? $_POST['emailed_to_dhad'] : null;
        $received_from_dhad = !empty($_POST['received_from_dhad']) ? $_POST['received_from_dhad'] : null;
        $evaluator = $_POST['evaluator'] ?? '';
        $note = $_POST['note'] ?? '';
        $status = $_POST['status'] ?? $record['status'];
        
        // Validate data
        if (strlen($control_no) > 20) {
            throw new Exception("Control number is too long (maximum 20 characters)");
        }
        
        // Check for duplicate control number (excluding current record)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM direct_hire WHERE control_no = :control_no AND id != :id");
        $stmt->execute(['control_no' => $control_no, 'id' => $record_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Another record with this control number already exists");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update record
        $sql = "UPDATE direct_hire SET
                control_no = :control_no,
                name = :name,
                jobsite = :jobsite,
                evaluated = :evaluated,
                for_confirmation = :for_confirmation,
                emailed_to_dhad = :emailed_to_dhad,
                received_from_dhad = :received_from_dhad,
                evaluator = :evaluator,
                note = :note,
                type = :type,
                status = :status
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'control_no' => $control_no,
            'name' => $name,
            'jobsite' => $jobsite,
            'evaluated' => $evaluated,
            'for_confirmation' => $for_confirmation,
            'emailed_to_dhad' => $emailed_to_dhad,
            'received_from_dhad' => $received_from_dhad,
            'evaluator' => $evaluator,
            'note' => $note,
            'type' => $type,
            'status' => $status,
            'id' => $record_id
        ]);
        
        // Handle file uploads
        if (!empty($_FILES['documents']['name'][0])) {
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                             'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                             'image/jpeg', 'image/png', 'image/gif', 'text/plain'];
            
            foreach ($_FILES['documents']['name'] as $key => $name) {
                if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['documents']['tmp_name'][$key];
                    $file_type = $_FILES['documents']['type'][$key];
                    $file_size = $_FILES['documents']['size'][$key];
                    
                    // Validate file type
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("File type not allowed: $name");
                    }
                    
                    // Generate a unique filename
                    $filename = uniqid('doc_') . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $name);
                    $destination = $upload_dir . $filename;
                    
                    // Move uploaded file
                    if (!move_uploaded_file($tmp_name, $destination)) {
                        throw new Exception("Failed to upload file: $name");
                    }
                    
                    // Insert into direct_hire_documents table
                    $doc_sql = "INSERT INTO direct_hire_documents (direct_hire_id, filename, original_filename, file_type, file_size)
                                VALUES (?, ?, ?, ?, ?)";
                    $doc_stmt = $pdo->prepare($doc_sql);
                    $doc_stmt->execute([
                        $record_id,
                        $filename,
                        $name,
                        $file_type,
                        $file_size
                    ]);
                } elseif ($_FILES['documents']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                    $error_code = $_FILES['documents']['error'][$key];
                    throw new Exception("File upload error: $name, Code: $error_code");
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect based on button clicked
        if (isset($_POST['save_and_continue'])) {
            $success_message = "Record updated successfully";
            
            // Refresh record data
            $stmt = $pdo->prepare("SELECT * FROM direct_hire WHERE id = ?");
            $stmt->execute([$record_id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Refresh documents
            $docs_stmt = $pdo->prepare("SELECT * FROM direct_hire_documents WHERE direct_hire_id = ? ORDER BY uploaded_at DESC");
            $docs_stmt->execute([$record_id]);
            $documents = $docs_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Redirect to the record view page
            header("Location: direct_hire_view.php?id=$record_id&success=Record updated successfully");
            exit();
        }
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_message = $e->getMessage();
}

$pageTitle = "Edit Record - Direct Hire";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="edit-record-wrapper">
        <div class="page-header">
          <div class="header-content">
            <h2>Edit Record</h2>
            <div class="record-subtitle">
              <span class="control-no"><?= htmlspecialchars($record['control_no']) ?></span>
              <span class="record-type"><?= ucfirst(htmlspecialchars($record['type'])) ?></span>
            </div>
          </div>
          
          <div class="header-actions">
            <a href="direct_hire_view.php?id=<?= $record_id ?>" class="btn btn-secondary">
              <i class="fa fa-eye"></i> View Record
            </a>
            <a href="direct_hire.php?tab=<?= urlencode($record['type']) ?>" class="btn btn-outline-secondary">
              <i class="fa fa-arrow-left"></i> Back to List
            </a>
          </div>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="success-message">
          <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="error-message">
          <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <!-- Form Section -->
        <form class="record-form" method="POST" action="" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?= $record_id ?>">
          
          <div class="form-grid">
            <label>
              Control No.
              <input type="text" name="control_no" value="<?= htmlspecialchars($record['control_no']) ?>" required>
            </label>
            <label>
              Name
              <input type="text" name="name" value="<?= htmlspecialchars($record['name']) ?>" required>
            </label>
            <label>
              Jobsite
              <input type="text" name="jobsite" value="<?= htmlspecialchars($record['jobsite']) ?>" required>
            </label>
            <label>
              Type
              <select name="type">
                <option value="professional" <?= $record['type'] === 'professional' ? 'selected' : '' ?>>Professional</option>
                <option value="household" <?= $record['type'] === 'household' ? 'selected' : '' ?>>Household</option>
              </select>
            </label>
            <label>
              Evaluated
              <input type="date" name="evaluated" value="<?= htmlspecialchars($record['evaluated'] ?? '') ?>">
            </label>
            <label>
              For Confirmation
              <input type="date" name="for_confirmation" value="<?= htmlspecialchars($record['for_confirmation'] ?? '') ?>">
            </label>
            <label>
              Emailed to DHAD
              <input type="date" name="emailed_to_dhad" value="<?= htmlspecialchars($record['emailed_to_dhad'] ?? '') ?>">
            </label>
            <label>
              Received from DHAD
              <input type="date" name="received_from_dhad" value="<?= htmlspecialchars($record['received_from_dhad'] ?? '') ?>">
            </label>
            <label>
              Evaluator
              <input type="text" name="evaluator" value="<?= htmlspecialchars($record['evaluator'] ?? '') ?>">
            </label>
            <label>
              Status
              <select name="status">
                <option value="pending" <?= $record['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $record['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="denied" <?= $record['status'] === 'denied' ? 'selected' : '' ?>>Denied</option>
              </select>
            </label>
            <label class="full-width">
              Note
              <textarea name="note"><?= htmlspecialchars($record['note'] ?? '') ?></textarea>
            </label>
          </div>

          <!-- File Upload Section -->
          <div class="file-section">
            <h3>Documents</h3>
            
            <div class="file-upload-section">
              <div class="upload-box">
                <div class="upload-placeholder">
                  <i class="fa fa-cloud-upload-alt"></i>
                  <p>Drag files here or <button type="button" onclick="document.getElementById('fileInput').click()" class="browse-btn">Browse</button></p>
                  <input type="file" id="fileInput" name="documents[]" multiple style="display: none;">
                </div>
              </div>

              <div class="uploaded-files">
                <h4>New Files</h4>
                <div id="fileList"></div>
              </div>
            </div>
            
            <?php if (count($documents) > 0): ?>
            <div class="existing-documents">
              <h4>Existing Documents</h4>
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
                    <a href="javascript:void(0)" onclick="confirmDeleteFile(<?= $doc['id'] ?>)" class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="fa fa-trash"></i>
                    </a>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Action Buttons -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
            <button type="submit" name="save_and_continue" value="1" class="btn btn-outline-primary"><i class="fa fa-edit"></i> Save and Continue Editing</button>
            <a href="direct_hire_view.php?id=<?= $record_id ?>" class="btn btn-cancel">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<!-- Delete File Confirmation Modal -->
<div id="deleteFileModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <h3>Delete Document</h3>
      <button class="modal-close" onclick="closeDeleteFileModal()">&times;</button>
    </div>
    <div class="modal-body" style="text-align: center;">
      <p>Are you sure you want to delete this document? This action cannot be undone.</p>
      <div class="modal-actions" style="justify-content: center; margin-top: 20px;">
        <button class="btn btn-cancel" onclick="closeDeleteFileModal()">Cancel</button>
        <button class="btn btn-danger" id="confirmDeleteFileBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Handle file input
  document.getElementById('fileInput').addEventListener('change', function(e) {
    const fileList = document.getElementById('fileList');
    fileList.innerHTML = '';
    
    // Display selected files
    for (let i = 0; i < this.files.length; i++) {
      const file = this.files[i];
      const fileSize = (file.size / 1024).toFixed(1) + ' KB';
      
      const fileItem = document.createElement('div');
      fileItem.className = 'file-item';
      fileItem.innerHTML = `
        <span class="drag-handle">☰</span>
        <span class="file-name">${file.name}</span>
        <span class="file-size">${fileSize}</span>
        <button type="button" class="delete-file" onclick="removeFile(this, ${i})"><i class="fa fa-trash"></i></button>
      `;
      
      fileList.appendChild(fileItem);
    }
  });
  
  // Remove file from list
  function removeFile(button, index) {
    // Note: This only removes it from the display, not from the actual FileList
    // When the form is submitted, all files will still be included
    // For a more complete solution, a custom file upload solution using AJAX would be needed
    const fileItem = button.parentNode;
    fileItem.parentNode.removeChild(fileItem);
  }
  
  // Handle drag and drop
  const uploadBox = document.querySelector('.upload-box');
  
  uploadBox.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.style.backgroundColor = '#e9f7fe';
    this.style.borderColor = '#007bff';
  });
  
  uploadBox.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.style.backgroundColor = '#f9f9f9';
    this.style.borderColor = '#ccc';
  });
  
  uploadBox.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.backgroundColor = '#f9f9f9';
    this.style.borderColor = '#ccc';
    
    const fileInput = document.getElementById('fileInput');
    fileInput.files = e.dataTransfer.files;
    
    // Trigger change event
    const event = new Event('change');
    fileInput.dispatchEvent(event);
  });
  
  function confirmDeleteFile(fileId) {
    const modal = document.getElementById('deleteFileModal');
    const confirmBtn = document.getElementById('confirmDeleteFileBtn');
    
    // Set the onclick event for the confirm button
    confirmBtn.onclick = function() {
      window.location.href = 'delete_document.php?id=' + fileId + '&record_id=<?= $record_id ?>';
    };
    
    // Show the modal
    modal.style.display = 'flex';
  }
  
  function closeDeleteFileModal() {
    const modal = document.getElementById('deleteFileModal');
    modal.style.display = 'none';
  }
  
  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('deleteFileModal');
    if (event.target === modal) {
      closeDeleteFileModal();
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
  .edit-record-wrapper {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 30px;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
  }
  
  .header-content h2 {
    margin: 0 0 5px 0;
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
  
  .header-actions {
    display: flex;
    gap: 10px;
  }
  
  .success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 25px;
  }
  
  .form-grid label {
    display: flex;
    flex-direction: column;
    gap: 5px;
    font-weight: 500;
  }
  
  .form-grid input, 
  .form-grid textarea,
  .form-grid select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100%;
  }
  
  .form-grid textarea {
    min-height: 100px;
    resize: vertical;
  }
  
  .full-width {
    grid-column: span 3;
  }
  
  .file-section {
    margin-bottom: 25px;
  }
  
  .file-section h3 {
    font-size: 18px;
    margin: 0 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
  }
  
  .file-section h4 {
    font-size: 16px;
    margin: 0 0 10px 0;
  }
  
  .file-upload-section {
    margin-bottom: 20px;
  }
  
  .upload-box {
    border: 2px dashed #ccc;
    border-radius: 5px;
    padding: 30px;
    text-align: center;
    margin-bottom: 15px;
    background-color: #f9f9f9;
  }
  
  .upload-placeholder i {
    font-size: 40px;
    color: #aaa;
    margin-bottom: 10px;
  }
  
  .browse-btn {
    background-color: transparent;
    color: #007bff;
    border: none;
    cursor: pointer;
    text-decoration: underline;
    padding: 0;
    margin: 0;
    font-weight: 500;
  }
  
  .uploaded-files {
    background-color: #f5f5f5;
    border-radius: 5px;
    padding: 15px;
  }
  
  .file-item {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    background-color: white;
    margin-bottom: 8px;
    border-radius: 4px;
    border: 1px solid #eee;
  }
  
  .drag-handle {
    cursor: move;
    margin-right: 10px;
    color: #888;
  }
  
  .file-name {
    flex-grow: 1;
  }
  
  .file-size {
    color: #888;
    margin-right: 15px;
    font-size: 12px;
  }
  
  .delete-file {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 14px;
  }
  
  .existing-documents {
    margin-top: 25px;
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
  
  .document-actions {
    display: flex;
    gap: 5px;
  }
  
  .form-actions {
    display: flex;
    gap: 10px;
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
  
  .btn-outline-primary {
    background-color: transparent;
    border: 1px solid #007bff;
    color: #007bff;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    border: 1px solid #6c757d;
    color: white;
  }
  
  .btn-outline-secondary {
    background-color: transparent;
    border: 1px solid #6c757d;
    color: #6c757d;
  }
  
  .btn-cancel {
    background-color: transparent;
    border: 1px solid #dc3545;
    color: #dc3545;
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
  
  .btn-outline-danger {
    background-color: transparent;
    border: 1px solid #dc3545;
    color: #dc3545;
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
  
  .btn-danger {
    background-color: #dc3545;
    border: 1px solid #dc3545;
    color: white;
  }
  
  @media (max-width: 992px) {
    .form-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    
    .full-width {
      grid-column: span 2;
    }
  }
  
  @media (max-width: 768px) {
    .page-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .header-actions {
      margin-top: 15px;
      width: 100%;
      justify-content: space-between;
    }
  }
  
  @media (max-width: 576px) {
    .form-grid {
      grid-template-columns: 1fr;
    }
    
    .full-width {
      grid-column: span 1;
    }
    
    .form-actions {
      flex-direction: column;
    }
    
    .form-actions .btn {
      width: 100%;
      justify-content: center;
    }
  }
</style> 