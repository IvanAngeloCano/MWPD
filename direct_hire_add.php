<?php
include 'session.php';
require_once 'connection.php';
require_once 'blacklist_check.php';

// Process form submission
$success_message = '';
$error_message = '';
$blacklist_warning = '';

// Check if we're looking up a person
if (isset($_GET['name']) && !empty($_GET['name'])) {
    $name = trim($_GET['name']);
    $blacklist_record = checkBlacklist($pdo, $name);
    if ($blacklist_record) {
        // Person is blacklisted, show warning
        $blacklist_warning = generateBlacklistWarning($blacklist_record);
    }
}

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
        
        // Check if the person is blacklisted
        $name = trim($_POST['name']);
        $blacklist_record = checkBlacklist($pdo, $name);
        if ($blacklist_record) {
            // Person is blacklisted, but still allow the form to be submitted with a warning
            $blacklist_warning = generateBlacklistWarning($blacklist_record);
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
        // Always set new records to NULL status, regardless of what was submitted
        $status = null;
        
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
        
        // Automatically generate clearance document for the new record
        try {
            // Load the Word template
            require_once 'vendor/autoload.php';
            
            // Check if ZipArchive is available
            if (!class_exists('ZipArchive')) {
                // Create a simple text file with the data instead
                $outputDir = 'uploads/direct_hire_clearance';
                if (!file_exists($outputDir)) {
                    mkdir($outputDir, 0777, true);
                }
                
                $textFile = $outputDir . '/clearance_' . $direct_hire_id . '_' . date('Ymd_His') . '.txt';
                $content = "Direct Hire Clearance Document\n" .
                          "Control No: {$control_no}\n" .
                          "Name: {$name}\n" .
                          "Jobsite: {$jobsite}\n" .
                          "Type: " . ucfirst($type) . "\n" .
                          "Status: " . ucfirst($status) . "\n" .
                          "Evaluator: {$evaluator}\n" .
                          "Evaluated: " . (!empty($evaluated) ? date('F j, Y', strtotime($evaluated)) : 'Not set') . "\n" .
                          "For Confirmation: " . (!empty($for_confirmation) ? date('F j, Y', strtotime($for_confirmation)) : 'Not set') . "\n" .
                          "Emailed to DHAD: " . (!empty($emailed_to_dhad) ? date('F j, Y', strtotime($emailed_to_dhad)) : 'Not set') . "\n" .
                          "Received from DHAD: " . (!empty($received_from_dhad) ? date('F j, Y', strtotime($received_from_dhad)) : 'Not set') . "\n" .
                          "Note: " . (!empty($note) ? $note : 'No notes available') . "\n" .
                          "Current Date: " . date('F j, Y') . "\n";
                
                file_put_contents($textFile, $content);
                
                // Save the document reference to the database
                $stmt = $pdo->prepare("INSERT INTO direct_hire_documents (direct_hire_id, filename, original_filename, file_type, file_size) 
                                     VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $direct_hire_id,
                    basename($textFile),
                    basename($textFile),
                    'text/plain',
                    filesize($textFile)
                ]);
                
                // Skip the rest of the document generation code
                $_SESSION['success_message'] = 'Direct Hire record added successfully with text clearance document.';
                header('Location: direct_hire.php');
                exit;
            }
            
            // If ZipArchive is available, load the Word template
            $template = new \PhpOffice\PhpWord\TemplateProcessor('Directhireclearance.docx');
            
            // Map all database fields to their corresponding placeholders
            // Basic information
            $template->setValue('control_no', $control_no);
            $template->setValue('name', $name);
            $template->setValue('jobsite', $jobsite);
            $template->setValue('type', ucfirst($type));
            $template->setValue('status', ucfirst($status));
            $template->setValue('evaluator', $evaluator ?? 'Not assigned');
            
            // Format dates properly using the April 23, 2025 format
            $evaluated_formatted = !empty($evaluated) ? date('F j, Y', strtotime($evaluated)) : 'Not set';
            $for_confirmation_formatted = !empty($for_confirmation) ? date('F j, Y', strtotime($for_confirmation)) : 'Not set';
            $emailed_to_dhad_formatted = !empty($emailed_to_dhad) ? date('F j, Y', strtotime($emailed_to_dhad)) : 'Not set';
            $received_from_dhad_formatted = !empty($received_from_dhad) ? date('F j, Y', strtotime($received_from_dhad)) : 'Not set';
            $created_at = date('F j, Y');
            $updated_at = date('F j, Y');
            
            // Set date values
            $template->setValue('evaluated', $evaluated_formatted);
            $template->setValue('for_confirmation', $for_confirmation_formatted);
            $template->setValue('emailed_to_dhad', $emailed_to_dhad_formatted);
            $template->setValue('received_from_dhad', $received_from_dhad_formatted);
            $template->setValue('created_at', $created_at);
            $template->setValue('updated_at', $updated_at);
            
            // Add note if available
            $template->setValue('note', !empty($note) ? $note : 'No notes available');
            
            // Add current date
            $template->setValue('current_date', date('F j, Y'));
            
            // Additional placeholders that might be in the template
            $template->setValue('clearance_id', 'CLR-NEW-' . date('Ymd'));
            $template->setValue('clearance_date', date('F j, Y'));
            $template->setValue('approval_date', $status === 'approved' ? date('F j, Y') : 'Pending');
            
            // Try to fill all possible placeholders that might be in the template
            $all_fields = [
                'control_no', 'name', 'jobsite', 'type', 'status', 'evaluator',
                'evaluated', 'for_confirmation', 'emailed_to_dhad', 'received_from_dhad', 'note'
            ];
            
            foreach ($all_fields as $field) {
                $value = null;
                // Get the value based on variable name
                switch ($field) {
                    case 'control_no': $value = $control_no; break;
                    case 'name': $value = $name; break;
                    case 'jobsite': $value = $jobsite; break;
                    case 'type': $value = ucfirst($type); break;
                    case 'status': $value = ucfirst($status); break;
                    case 'evaluator': $value = $evaluator ?? 'Not assigned'; break;
                    case 'evaluated': $value = $evaluated_formatted; break;
                    case 'for_confirmation': $value = $for_confirmation_formatted; break;
                    case 'emailed_to_dhad': $value = $emailed_to_dhad_formatted; break;
                    case 'received_from_dhad': $value = $received_from_dhad_formatted; break;
                    case 'note': $value = !empty($note) ? $note : 'No notes available'; break;
                }
                
                if ($value !== null) {
                    $template->setValue($field, $value);
                }
            }
            
            // Check if signatories table exists, if not create it
            try {
                $tableExists = $pdo->query("SHOW TABLES LIKE 'signatories'")->rowCount() > 0;
                
                if (!$tableExists) {
                    // Create the signatories table
                    $sql = "CREATE TABLE signatories (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        name VARCHAR(255) NOT NULL,
                        position VARCHAR(255) NOT NULL,
                        position_order INT(11) NOT NULL DEFAULT 0,
                        signature_file VARCHAR(255) DEFAULT NULL,
                        active TINYINT(1) NOT NULL DEFAULT 1,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    
                    $pdo->exec($sql);
                    
                    // Insert default signatories
                    $insertSql = "INSERT INTO signatories (name, position, position_order) VALUES 
                        ('IVAN ANGELO M. CANO', 'MWPD', 1),
                        ('JOHN DOE', 'Department Head', 2),
                        ('JANE SMITH', 'Director', 3)";
                    
                    $pdo->exec($insertSql);
                }
                
                // Get signatories from the database
                $signatories_stmt = $pdo->prepare("SELECT * FROM signatories WHERE active = 1 ORDER BY position_order LIMIT 3");
                $signatories_stmt->execute();
                $signatories = $signatories_stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // If there's an error with the database, use default signatories
                $signatories = [];
            }
            
            // Default signatories in case database doesn't have enough
            $default_signatories = [
                ['name' => 'IVAN ANGELO M. CANO', 'position' => 'MWPD'],
                ['name' => 'JOHN DOE', 'position' => 'Department Head'],
                ['name' => 'JANE SMITH', 'position' => 'Director']
            ];
            
            // Add e-signature placeholders
            for ($i = 1; $i <= 3; $i++) {
                $signatory = isset($signatories[$i-1]) ? $signatories[$i-1] : $default_signatories[$i-1];
                
                $template->setValue("signature{$i}_name", $signatory['name']);
                $template->setValue("signature{$i}_position", $signatory['position']);
                $template->setValue("signature{$i}_date", date('F j, Y'));
                
                // Add e-signature image if available
                if (isset($signatory['signature_file']) && !empty($signatory['signature_file']) && file_exists("signatures/{$signatory['signature_file']}")) {
                    $template->setImageValue("signature{$i}_image", [
                        'path' => "signatures/{$signatory['signature_file']}",
                        'width' => 100,
                        'height' => 50,
                        'ratio' => false
                    ]);
                }
            }
            
            // Create directory for clearance documents if it doesn't exist
            $clearance_dir = 'uploads/direct_hire_clearance/';
            if (!file_exists($clearance_dir)) {
                mkdir($clearance_dir, 0755, true);
            }
            
            // Save DOCX file with a unique name
            $docxFile = $clearance_dir . 'DirectHireClearance_' . $control_no . '_' . date('Ymd_His') . '.docx';
            $template->saveAs($docxFile);
            
            // Save the document reference to the database
            $stmt = $pdo->prepare("INSERT INTO direct_hire_documents (direct_hire_id, filename, original_filename, file_type, file_size) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $direct_hire_id,
                basename($docxFile),
                basename($docxFile),
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                filesize($docxFile)
            ]);
            
        } catch (Exception $e) {
            // If there's an error generating the document, just log it and continue
            error_log("Error generating clearance document: " . $e->getMessage());
        }
        
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

    $pageTitle = 'Direct Hire - Add New Record';
    $currentPage == 'direct_hire.php';
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
        
        <?php
        // Display blacklist warning if it exists
        if (!empty($blacklist_warning)) {
            echo $blacklist_warning;
        }
        ?>
        
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
              <h3>Supporting Files</h3>
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
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
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