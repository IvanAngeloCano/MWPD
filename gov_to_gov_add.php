<?php
include 'session.php';
require_once 'connection.php';

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get name components
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_initial'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        
        // Build full name for display purposes
        $name = $first_name;
        if (!empty($middle_name)) {
            $name .= ' ' . $middle_name;
        }
        $name .= ' ' . $last_name;
        
        // Validate required fields
        $required_fields = ['control_no', 'first_name', 'last_name', 'destination'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Process form submission - insert into database
        // Get the form data
        $control_no = trim($_POST['control_no']);
        $passport_no = trim($_POST['passport_no'] ?? '');
        $destination = trim($_POST['destination']);
        $date_received = !empty($_POST['date_received']) ? $_POST['date_received'] : null;
        $evaluated = !empty($_POST['evaluated']) ? $_POST['evaluated'] : null;
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
            
            // First, middle and last names are already separated from form input
            // No need to split name
            
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
        

        
        <!-- Form Section -->
        <form class="record-form" method="POST" action="" enctype="multipart/form-data">
          <div class="form-grid">
            <label>Control No.<input type="text" name="control_no" required></label>
            <label>First Name<input type="text" name="first_name" placeholder="e.g. Juan" required></label>
            <label>Middle Initial/Name<input type="text" name="middle_initial" placeholder="e.g. Miguel"></label>
            <label>Last Name<input type="text" name="last_name" placeholder="e.g. Dela Cruz" required></label>
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
    const submitButton = document.getElementById('submitButton');
    
    // Form validation for required fields
    const requiredFields = document.querySelectorAll('input[required], select[required]');
    requiredFields.forEach(field => {
      field.addEventListener('input', function() {
        validateForm();
      });
    });
    
    function validateForm() {
      if (submitButton) {
        // Check if all required fields are filled
        let allFilled = true;
        requiredFields.forEach(field => {
          if (!field.value.trim()) {
            allFilled = false;
          }
        });
        submitButton.disabled = !allFilled;
      }
    }
    
    // Run initial validation
    validateForm();
    
    // Initialize date inputs with current date as default
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
      if (!input.value) {
        const today = new Date().toISOString().split('T')[0];
        input.value = today;
      }
    });
  });
</script>
