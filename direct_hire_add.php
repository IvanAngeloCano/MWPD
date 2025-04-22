<?php
include 'session.php';
require_once 'connection.php';

// Process form submission
$success_message = '';
$error_message = '';

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/direct_hire/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
        $type = $_POST['type'] ?? 'professional';
        $evaluated = !empty($_POST['evaluated']) ? $_POST['evaluated'] : null;
        $for_confirmation = !empty($_POST['for_confirmation']) ? $_POST['for_confirmation'] : null;
        $emailed_to_dhad = !empty($_POST['emailed_to_dhad']) ? $_POST['emailed_to_dhad'] : null;
        $received_from_dhad = !empty($_POST['received_from_dhad']) ? $_POST['received_from_dhad'] : null;
        $evaluator = $_POST['evaluator'] ?? '';
        $note = $_POST['note'] ?? '';
        $status = $_POST['status'] ?? 'pending';
        
        // Validate data
        if (strlen($control_no) > 20) {
            throw new Exception("Control number is too long (maximum 20 characters)");
        }
        
        // Check for duplicate control number
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM direct_hire WHERE control_no = :control_no");
        $stmt->execute(['control_no' => $control_no]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("A record with this control number already exists");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert into direct_hire table
        $sql = "INSERT INTO direct_hire (control_no, name, jobsite, evaluated, for_confirmation, 
                                         emailed_to_dhad, received_from_dhad, evaluator, note, type, status) 
                VALUES (:control_no, :name, :jobsite, :evaluated, :for_confirmation, 
                        :emailed_to_dhad, :received_from_dhad, :evaluator, :note, :type, :status)";
        
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
            'status' => $status
        ]);
        
        // Get the ID of the inserted record
        $direct_hire_id = $pdo->lastInsertId();
        
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
                        $direct_hire_id,
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
        
        // Redirect with success message based on button clicked
        if (isset($_POST['save_and_add'])) {
            $success_message = "Record added successfully. You can add another record below.";
        } else {
            // Redirect to the direct hire listing page
            header("Location: direct_hire.php?tab=$type&success=Record added successfully");
            exit();
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

// Get record type from query parameter (professional or household)
$record_type = isset($_GET['type']) ? $_GET['type'] : 'professional';
if (!in_array($record_type, ['professional', 'household'])) {
    $record_type = 'professional';
}

$pageTitle = "Add New Record - Direct Hire";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    // Get current filename like 'dashboard-eme.php'
    $currentFile = basename($_SERVER['PHP_SELF']);

    // Remove the file extension
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);

    // Replace dashes with spaces
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', $fileWithoutExtension));

    $pageTitle = 'Add New Record to Direct Hire';
    include '_header.php';
    ?>

    <main class="main-content">
      <div class="add-record-wrapper">
        <!-- Tabs -->
        <div class="record-tabs">
          <h2>Adding records to:</h2>
          <a href="?type=professional" class="tab <?= $record_type === 'professional' ? 'active' : '' ?>">Professional</a>
          <a href="?type=household" class="tab <?= $record_type === 'household' ? 'active' : '' ?>">Household</a>
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
          <input type="hidden" name="type" value="<?= htmlspecialchars($record_type) ?>">
          
          <div class="form-grid">
            <label>Control No.<input type="text" name="control_no" required></label>
            <label>Name<input type="text" name="name" required></label>
            <label>Jobsite<input type="text" name="jobsite" required></label>
            <label>Evaluated<input type="date" name="evaluated"></label>
            <label>For Confirmation<input type="date" name="for_confirmation"></label>
            <label>Emailed to DHAD<input type="date" name="emailed_to_dhad"></label>
            <label>Received from DHAD<input type="date" name="received_from_dhad"></label>
            <label>Evaluator<input type="text" name="evaluator"></label>
            <label>Status
              <select name="status">
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="denied">Denied</option>
              </select>
            </label>
            <label>Note<textarea name="note"></textarea></label>
          </div>

          <!-- File Upload Section -->
          <div class="file-upload-section">
            <div class="upload-box">
              <div class="upload-placeholder">
                <i class="fa fa-cloud-upload-alt"></i>
                <p>Drag files here or <button type="button" onclick="document.getElementById('fileInput').click()" class="browse-btn">Browse</button></p>
                <input type="file" id="fileInput" name="documents[]" multiple style="display: none;">
              </div>
            </div>

            <div class="uploaded-files">
              <h3>Selected Files</h3>
              <div id="fileList"></div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
            <button type="submit" name="save_and_add" value="1" class="btn btn-outline-primary"><i class="fa fa-plus"></i> Save and Add Another</button>
            <button type="reset" class="btn btn-reset">
              <i class="fa fa-undo"></i> Reset
            </button>
            <a href="direct_hire.php?tab=<?= urlencode($record_type) ?>" class="btn btn-cancel">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>


    </main>
  </div>
</div>

<style>
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
  
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
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
  
  .form-grid label:nth-last-child(1) {
    grid-column: span 2;
  }
  
  .file-upload-section {
    margin-bottom: 25px;
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
  
  .uploaded-files h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
    font-weight: 600;
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
  
  .btn-reset {
    background-color: #6c757d;
    border: 1px solid #6c757d;
    color: white;
  }
  
  .btn-cancel {
    background-color: transparent;
    border: 1px solid #dc3545;
    color: #dc3545;
  }

  .tab {
    padding: 8px 16px;
    border-radius: 4px;
    margin-right: 10px;
    background-color: #f0f0f0;
    color: #555;
    text-decoration: none;
    display: inline-block;
  }
  
  .tab.active {
    background-color: #007bff;
    color: white;
  }
</style>

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
</script>