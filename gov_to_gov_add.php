<?php
include 'session.php';
require_once 'connection.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get name
        $name = $_POST['name'] ?? '';
        
        // Validate required fields
        $required_fields = ['control_no', 'name', 'destination'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Process form submission - insert into database
        // Get the form data
        $control_no = trim($_POST['control_no']);
        $name = trim($_POST['name']);
        $destination = trim($_POST['destination']);
        $date_received = !empty($_POST['date_received']) ? $_POST['date_received'] : null;
        $evaluated = !empty($_POST['evaluated']) ? $_POST['evaluated'] : null;
        $passport_no = trim($_POST['passport_no'] ?? '');
        $sex = $_POST['sex'] ?? '';
        $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
        $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
        $height = !empty($_POST['height']) ? $_POST['height'] : null;
        $contact_number = trim($_POST['contact_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $present_address = trim($_POST['present_address'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $evaluator = trim($_POST['evaluator'] ?? '');
        $remarks = trim($_POST['remarks'] ?? 'Pending');
        
        // Start a database transaction
        $pdo->beginTransaction();
        
        try {
            // Prepare the SQL statement with the correct column names based on gov_to_gov_view.php
            $sql = "INSERT INTO gov_to_gov (passport_number, first_name, middle_name, last_name,
                                         date_received_by_region, 
                                         sex, birth_date, weight, height, 
                                         contact_number, email_address, present_address, educational_attainment, 
                                         remarks) 
                    VALUES (:passport_number, :first_name, :middle_name, :last_name, 
                            :date_received_by_region, 
                            :sex, :birth_date, :weight, :height, 
                            :contact_number, :email_address, :present_address, :educational_attainment, 
                            :remarks)";
            
            // Split name into first, middle, last names
            $name_parts = explode(' ', $name);
            $first_name = $name_parts[0];
            $last_name = end($name_parts); // Last element
            
            // If there are more than 2 parts, the middle ones form the middle name
            $middle_name = '';
            if (count($name_parts) > 2) {
                $middle_parts = array_slice($name_parts, 1, count($name_parts) - 2);
                $middle_name = implode(' ', $middle_parts);
            }
            
            // Prepare and execute the query
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'passport_number' => $passport_no,
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'date_received_by_region' => $date_received,
                'sex' => $sex,
                'birth_date' => $birth_date,
                'weight' => $weight,
                'height' => $height,
                'contact_number' => $contact_number,
                'email_address' => $email, // Updated field name
                'present_address' => $present_address,
                'educational_attainment' => $education, // Updated field name
                'remarks' => $remarks
            ]);
            
            // Get the ID of the new record
            $new_id = $pdo->lastInsertId();
            
            // Add notification for record creation
            if (function_exists('addNotification') && isset($_SESSION['user_id'])) {
                $notificationText = "New Gov-to-Gov record added: $name";
                addNotification($_SESSION['user_id'], $notificationText, $new_id, 'gov_to_gov');
            }
            
            // Commit the transaction
            $pdo->commit();
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $pdo->rollBack();
            throw $e; // Re-throw to be caught by outer try-catch
        }
        
        // Set success message based on which button was clicked
        if (isset($_POST['save_and_add'])) {
            $success_message = "Record added successfully. You can add another record below.";
        } else {
            // Redirect to the listing page
            header("Location: gov_to_gov.php?success=Record added successfully");
            exit();
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$pageTitle = "Add New Record - Gov-to-Gov";
include '_head.php';
?>
<style>
/* Direct match of Direct Hire form styling */
.error-message {
  background-color: #f8d7da;
  color: #721c24;
  padding: 12px 15px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-size: 14px;
  display: flex;
}

/* Status indicator for name field */
.input-with-status {
  display: flex;
  align-items: center;
  width: 100%;
}

.input-status {
  display: inline-block;
  width: 20px;
  margin-left: 10px;
  text-align: center;
}
  align-items: center;
}

.error-message i {
  margin-right: 10px;
  font-size: 18px;
}

.success-message {
  background-color: #d4edda;
  color: #155724;
  padding: 12px 15px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-size: 14px;
  display: flex;
  align-items: center;
}

.success-message i {
  margin-right: 10px;
  font-size: 18px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.form-grid label {
  display: block;
  font-weight: 500;
  margin-bottom: 5px;
  color: #444;
}

.form-grid input,
.form-grid select,
.form-grid textarea {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 14px;
}

.form-grid textarea {
  min-height: 80px;
  resize: vertical;
}

/* Status indicator */
.input-with-status {
  display: flex;
  align-items: center;
}

.input-status {
  display: inline-block;
  width: 20px;
  margin-left: 10px;
}

/* File upload section */
.file-upload-section {
  display: flex;
  gap: 20px;
  margin-bottom: 25px;
}

.upload-box {
  flex: 1;
  border: 2px dashed #ccc;
  border-radius: 5px;
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f8f9fa;
}

.upload-placeholder {
  text-align: center;
}

.upload-placeholder i {
  font-size: 40px;
  color: #6c757d;
  margin-bottom: 10px;
}

.browse-btn {
  background: none;
  border: none;
  color: #007bff;
  cursor: pointer;
  text-decoration: underline;
}

.uploaded-files {
  flex: 1;
  border: 1px solid #eee;
  border-radius: 5px;
  padding: 15px;
}

.uploaded-files h3 {
  margin-top: 0;
  margin-bottom: 10px;
  font-size: 16px;
  color: #495057;
}

/* Form actions */
.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 25px;
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

.record-tabs {
  display: flex;
  align-items: center;
  margin-bottom: 25px;
  border-bottom: 1px solid #eee;
  padding-bottom: 15px;
}

.record-tabs h2 {
  margin: 0;
  margin-right: 20px;
  font-size: 1.2rem;
  color: #666;
}

.add-record-wrapper {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  padding: 25px;
  margin-bottom: 30px;
}

/* Responsive design */
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .file-upload-section {
    flex-direction: column;
  }
}

/* Custom popup */
.custom-popup {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  overflow: auto;
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from {opacity: 0}
  to {opacity: 1}
}
</style>
<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="content-wrapper">
    <?php include '_header.php'; ?>
    <main class="main-content">
      <div class="add-record-wrapper">
        <h2>Add Gov-to-Gov Record</h2>
        
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
        <!-- Blacklist warning removed as requested -->
        ?>
        
        <!-- Form Section -->
        <form class="record-form" method="POST" action="" enctype="multipart/form-data">
          <div class="form-grid">
            <label>Control No.<input type="text" name="control_no" required></label>
            <div class="form-group">
              <label for="name">Name</label>
              <div class="input-with-status">
                <input type="text" class="form-control" id="name" name="name" required>
                <div id="checkStatus" class="input-status"></div>
              </div>
            </div>
            <label>Destination<input type="text" name="destination" required></label>
            <label>Evaluated<input type="date" name="evaluated"></label>
            <label>For Confirmation<input type="date" name="for_confirmation"></label>
            <label>Date Received<input type="date" name="date_received_by_region"></label>
            <label>Passport No.<input type="text" name="passport_number"></label>
            <label>Passport Validity<input type="date" name="passport_validity"></label>
            <label>Sex<select name="sex">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select></label>
            <label>Birth Date<input type="date" name="birth_date"></label>
            <label>Age<input type="number" name="age"></label>
            <label>Height<input type="text" name="height" placeholder="cm"></label>
            <label>Weight<input type="text" name="weight" placeholder="kg"></label>
            <label>Contact Number<input type="text" name="contact_number"></label>
            <label>Email Address<input type="email" name="email_address"></label>
            <label>Present Address<input type="text" name="present_address"></label>
            <label>Education<input type="text" name="educational_attainment"></label>
            <label>Evaluator<input type="text" name="evaluator"></label>
            <label>Note<textarea name="remarks"></textarea></label>
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
            <a href="gov_to_gov.php" class="btn btn-cancel">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const submitButton = document.getElementById('submitButton');
    
    if (nameInput && submitButton) {
      // Disable submit button initially if name is empty
      if (nameInput.value.trim() === '') {
        submitButton.disabled = true;
      }
      
      // Add event listener for input changes
      nameInput.addEventListener('input', function() {
        const name = this.value.trim();
        submitButton.disabled = (name === '');
      });
    }
  });
    // Check if we need to show blacklist warning modal
    <?php if ($show_blacklist_modal && !empty($blacklist_data)): ?>
    // Populate blacklist modal with data from session
    const blacklistDetails = document.getElementById('blacklistMatchDetails');
    let detailsHTML = '<div class="blacklist-match-item">';

    <?php
    // If blacklist_data is a single record, convert to array
    if (!is_array($blacklist_data) || !isset($blacklist_data[0])) {
        $blacklist_data = [$blacklist_data];
    }

    foreach ($blacklist_data as $record) :
    ?>
      detailsHTML += '<p><strong>Name:</strong> <?= htmlspecialchars($record["name"] ?? "Unknown") ?></p>';
      <?php if (!empty($record["reason"])): ?>
      detailsHTML += '<p><strong>Reason:</strong> <?= htmlspecialchars($record["reason"]) ?></p>';
      <?php endif; ?>
      <?php if (!empty($record["details"])): ?>
      detailsHTML += '<p><strong>Details:</strong> <?= htmlspecialchars($record["details"]) ?></p>';
      <?php endif; ?>
      <?php if (!empty($record["reference_no"])): ?>
      detailsHTML += '<p><strong>Reference #:</strong> <?= htmlspecialchars($record["reference_no"]) ?></p>';
      <?php endif; ?>
      <?php if (!empty($record["date_added"])): ?>
      detailsHTML += '<p><strong>Date Added:</strong> <?= htmlspecialchars($record["date_added"]) ?></p>';
      <?php endif; ?>
    <?php endforeach; ?>

    detailsHTML += '</div>';
    blacklistDetails.innerHTML = detailsHTML;

    // Show the popup
    document.getElementById('customBlacklistPopup').style.display = 'block';
    <?php endif; ?>
    
    // Setup name field for blacklist checking
    const nameField = document.getElementById('name');
    const statusDiv = document.getElementById('checkStatus');
    
    // Function to delay execution (debounce)
    function debounce(func, wait) {
      let timeout;
      return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
      };
    }
    
    // Function to check for blacklist match
    const checkBlacklist = debounce(function() {
      // Get value from name field
      const name = nameField ? nameField.value.trim() : '';
      
      // Don't proceed if name is empty
      if (!name) return;
      
      // Only check when name has at least two parts (first and last name)
      if (name.indexOf(' ') === -1) return;
      
      if (statusDiv) {
        statusDiv.innerHTML = '<img src="assets/img/loading.gif" alt="Checking..." width="16">';
      }
      
      // Make AJAX request to check blacklist
      fetch('basic_blacklist_check.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'name=' + encodeURIComponent(name)
      })
      .then(response => response.json())
      .then(data => {
        if (data.blacklisted) {
          // Match found - show warning
          statusDiv.innerHTML = '<i class="fa fa-exclamation-triangle" style="color: #dc3545;" title="Blacklisted"></i>';
          showBlacklistMatch(data.details);
        } else {
          // No match
          statusDiv.innerHTML = '<i class="fa fa-check-circle" style="color: #28a745;" title="No blacklist match"></i>';
        }
      })
      .catch(error => {
        console.error('Error checking blacklist:', error);
        statusDiv.innerHTML = '';
      });
    }, 500);
    
    // Function to display blacklist match details
    function showBlacklistMatch(details) {
      const blacklistDetails = document.getElementById('blacklistMatchDetails');
      let detailsHTML = '<div class="blacklist-match-item">';
      
      if (details && details.length > 0) {
        details.forEach(record => {
          detailsHTML += `<p><strong>Name:</strong> ${record.name || 'Unknown'}</p>`;
          if (record.reason) detailsHTML += `<p><strong>Reason:</strong> ${record.reason}</p>`;
          if (record.details) detailsHTML += `<p><strong>Details:</strong> ${record.details}</p>`;
          if (record.reference_no) detailsHTML += `<p><strong>Reference #:</strong> ${record.reference_no}</p>`;
          if (record.date_added) detailsHTML += `<p><strong>Date Added:</strong> ${record.date_added}</p>`;
        });
      } else {
        detailsHTML += '<p>No detailed information available.</p>';
      }
      
      detailsHTML += '</div>';
      blacklistDetails.innerHTML = detailsHTML;
      
      // Show the popup
      document.getElementById('customBlacklistPopup').style.display = 'block';
    }
    
    // Attach event listener
    if (nameField) nameField.addEventListener('blur', checkBlacklist);
  });
</script>
