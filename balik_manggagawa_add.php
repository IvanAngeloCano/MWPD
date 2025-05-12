<?php
include 'session.php';
require_once 'connection.php';

// Initialize variables
$error_message = '';
$success_message = '';
$override_blacklist_check = isset($_POST['override_blacklist_check']) && $_POST['override_blacklist_check'] === 'true';

// Check if we need to show blacklist warning modal on page load
if (isset($_SESSION['show_blacklist_modal']) && $_SESSION['show_blacklist_modal']) {
    $show_blacklist_modal = true;
    $blacklist_data = $_SESSION['blacklist_data'] ?? [];
    
    // Clear the session flag
    $_SESSION['show_blacklist_modal'] = false;
    unset($_SESSION['blacklist_data']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate form data
        $required_fields = ['full_name', 'sex', 'address', 'destination'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Get name for blacklist check
        $full_name = $_POST['full_name'] ?? '';
        
        // Only do server-side blacklist check if not overridden by real-time check
        if (!$override_blacklist_check) {
            // Parse the name into First Middle Last format for blacklist check and database insertion
            $name_parts = explode(' ', trim($full_name));
            
            if (count($name_parts) < 2) {
                throw new Exception("Full name must contain at least first and last name");
            }
            
            // Extract first, middle, and last name components
            $given_name = $name_parts[0]; // First name is the first part
            $last_name = end($name_parts); // Last name is the last part
            
            // Middle name is everything between first and last (if any)
            $middle_parts = array_slice($name_parts, 1, count($name_parts) - 2);
            $middle_name = !empty($middle_parts) ? implode(' ', $middle_parts) : '';
            
            // Format name for blacklist check - Last, First Middle
            $blacklist_name = $last_name . ', ' . $given_name . (!empty($middle_name) ? ' ' . $middle_name : '');
            
            // Check for blacklist match
            $blacklist_record = checkBlacklist($pdo, $blacklist_name);
            if ($blacklist_record) {
                // Log that blacklist was found during server check
                error_log("Blacklist found during server check for: $blacklist_name");
                
                // Person is blacklisted - set up modal display
                $_SESSION['show_blacklist_modal'] = true;
                $_SESSION['blacklist_data'] = [
                    'name' => $blacklist_record['name'] ?? $blacklist_name,
                    'reason' => $blacklist_record['reason'] ?? 'Not specified',
                    'details' => $blacklist_record['details'] ?? '',
                    'reference_no' => $blacklist_record['reference_no'] ?? '',
                    'date_added' => $blacklist_record['date_added'] ?? 'Unknown',
                    'full_name' => $full_name,
                    'given_name' => $given_name,
                    'middle_name' => $middle_name,
                    'last_name' => $last_name
                ];
                
                // Redirect back to the form to show the modal
                header("Location: balik_manggagawa_add.php?blacklisted=true");
                exit;
            }
        } else {
            // Log that blacklist check was overridden
            error_log("Blacklist check was overridden by client-side check for: $full_name");
        }
        
        // Parse full name for database insertion if not done in blacklist check
        if ($override_blacklist_check) {
            // Need to parse the name again since we skipped blacklist check
            $name_parts = explode(' ', trim($full_name));
            
            if (count($name_parts) < 2) {
                throw new Exception("Full name must contain at least first and last name");
            }
            
            // Extract name components
            $given_name = $name_parts[0]; // First name is the first part
            $last_name = end($name_parts); // Last name is the last part
            
            // Middle name is everything between first and last (if any)
            $middle_parts = array_slice($name_parts, 1, count($name_parts) - 2);
            $middle_name = !empty($middle_parts) ? implode(' ', $middle_parts) : '';
        }
        
        // Get other form data
        $sex = $_POST['sex'];
        $address = $_POST['address'];
        $destination = $_POST['destination'];
        // Automatically set remarks to 'Pending' for new records
        $remarks = 'Pending';
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert into BM table
        // Omit bmid field to let MySQL handle auto-increment
        $sql = "INSERT INTO BM (last_name, given_name, middle_name, sex, address, destination, remarks) 
                VALUES (:last_name, :given_name, :middle_name, :sex, :address, :destination, :remarks)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'last_name' => $last_name,
            'given_name' => $given_name,
            'middle_name' => $middle_name ?? '',
            'sex' => $sex,
            'address' => $address,
            'destination' => $destination,
            'remarks' => $remarks
        ]);
        
        // Get the ID of the inserted record
        $bmid = $pdo->lastInsertId();
        
        // Add notification for record creation
        if (function_exists('addNotification') && isset($_SESSION['user_id'])) {
            $notificationText = "New Balik Manggagawa record added: $last_name, $given_name";
            addNotification($_SESSION['user_id'], $notificationText);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect with success message based on button clicked
        if (isset($_POST['save_and_add'])) {
            $success_message = "Record added successfully. You can add another record below.";
        } else {
            // Redirect to the balik manggagawa listing page
            header("Location: balik_manggagawa.php?success=Record added successfully");
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

$pageTitle = "Add New Record - Balik Manggagawa";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    $currentFile = basename($_SERVER['PHP_SELF']);
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
    $pageTitle = 'Balik Manggagawa - Add New Record';
    $currentPage = 'balik_manggagawa.php';
    include '_header.php';
    ?>

    <main class="main-content">
      <div class="add-record-wrapper">
        <h2>Add New Balik Manggagawa Record</h2>
        
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
        <form class="record-form" method="POST" action="">
          <input type="hidden" name="mode" value="add">
          <div class="form-grid">
            <div class="form-group">
              <label for="full_name">Full Name (Format: First Middle Last)</label>
              <div class="name-input-container">
                <div class="input-with-icon">
                  <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g. Juan Miguel Dela Cruz" required>
                  <span id="blacklistCheckStatus" class="input-icon"></span>
                </div>
              </div>
              <small class="form-text text-muted">Please enter the full name in the format: First Middle Last (system will automatically separate components)</small>
              <input type="hidden" name="override_blacklist_check" id="override_blacklist_check" value="false">
            </div>
            
            <label>Sex
              <select name="sex" required>
                <option value="">Select Sex</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </label>
            
            <label>Address<input type="text" name="address" required></label>
            <label>Destination<input type="text" name="destination" required></label>
            <!-- Remarks field removed and set to Pending automatically -->
            
            <label>Name of the Agency<input type="text" name="nameoftheagency"></label>
            <label>Name of the Principal<input type="text" name="nameoftheprincipal"></label>
            <label>Name of the New Agency<input type="text" name="nameofthenewagency"></label>
            <label>Name of the New Principal<input type="text" name="nameofthenewprincipal"></label>
            
            <label>Employment Duration Start<input type="date" name="employmentdurationstart"></label>
            <label>Employment Duration End<input type="date" name="employmentdurationend"></label>
            <label>Date of Arrival<input type="date" name="dateofarrival"></label>
            <label>Date of Departure<input type="date" name="dateofdeparture"></label>
          </div>

          <!-- Action Buttons -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
            <button type="submit" name="save_and_add" value="1" class="btn btn-outline-primary"><i class="fa fa-plus"></i> Save and Add Another</button>
            <button type="reset" class="btn btn-reset">
              <i class="fa fa-undo"></i> Reset
            </button>
            <a href="balik_manggagawa.php" class="btn btn-cancel">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<!-- Custom Blacklist Popup (not using Bootstrap modal) -->
<div id="customBlacklistPopup" class="custom-popup">
  <div class="custom-popup-content">
    <div class="custom-popup-header">
      <h3><i class="fa fa-exclamation-triangle"></i> WARNING: BLACKLISTED PERSON</h3>
      <span class="custom-popup-close" onclick="closeCustomPopup()">&times;</span>
    </div>
    <div class="custom-popup-body">
      <div class="blacklist-warning">
        <p><strong>WARNING:</strong> This person is <strong>BLACKLISTED</strong>!</p>
        <p>Processing this individual may violate POEA regulations.</p>
      </div>
      <div id="blacklistMatchDetails" class="blacklist-details">
        <!-- Blacklist match details will be populated here -->
      </div>
    </div>
    <div class="custom-popup-footer">
      <button onclick="closeCustomPopup()" class="popup-btn popup-btn-cancel">Close</button>
    </div>
  </div>
</div>

<style>
  .add-record-wrapper {
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
  }
  
  .add-record-wrapper h2 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.5rem;
  }
  
  /* Custom Popup Styles */
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

  .custom-popup-content {
    position: relative;
    background-color: #fefefe;
    margin: 10% auto;
    padding: 0;
    border: 1px solid #888;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
    animation: slideDown 0.4s;
  }
  
  @keyframes slideDown {
    from {transform: translateY(-300px); opacity: 0}
    to {transform: translateY(0); opacity: 1}
  }

  .custom-popup-header {
    padding: 12px 16px;
    background-color: #dc3545;
    color: white;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .custom-popup-header h3 {
    margin: 0;
    font-size: 1.25rem;
  }

  .custom-popup-close {
    color: white;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }

  .custom-popup-body {
    padding: 16px;
  }

  .blacklist-warning {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 12px 15px;
    margin-bottom: 15px;
    border-radius: 4px;
  }

  .blacklist-details {
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
  }

  .blacklist-match-item {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 10px;
  }

  .blacklist-match-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
  }

  .custom-popup-footer {
    padding: 12px 16px;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    text-align: right;
  }

  .popup-btn {
    padding: 8px 16px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
  }

  .popup-btn-cancel {
    background-color: #6c757d;
    color: white;
    border: none;
  }
  
  .error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
  }
  
  .error-message i {
    margin-right: 8px;
    font-size: 18px;
  }
  
  .success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
  }
  
  .success-message i {
    margin-right: 10px;
    font-size: 18px;
  }
  
  /* Status indicator for name field */
  .input-with-status {
    display: flex;
    align-items: center;
    width: 100%;
  }
  
  .form-group {
    margin-bottom: 15px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
  }
  
  .form-control {
    display: block;
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: border-color 0.15s ease-in-out;
  }
  
  .input-status {
    display: inline-block;
    width: 20px;
    margin-left: 10px;
    text-align: center;
  }
</style>

<script>
// Function to close the custom popup
function closeCustomPopup() {
  document.getElementById('customBlacklistPopup').style.display = 'none';
}

// Main initialization function - runs when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
  // ===== DISPLAY BLACKLIST MODAL IF NEEDED =====
  <?php if ($show_blacklist_modal && !empty($blacklist_data)): ?>
  // Get modal element
  const blacklistDetails = document.getElementById('blacklistMatchDetails');
  let detailsHTML = '<div class="blacklist-match-item">';
  
  <?php
  // Convert single record to array for consistency
  if (!is_array($blacklist_data) || !isset($blacklist_data[0])) {
      $blacklist_data = [$blacklist_data];
  }
  
  // Generate HTML for each record
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
  
  // Show the modal
  document.getElementById('customBlacklistPopup').style.display = 'block';
  <?php endif; ?>
  
  // ===== REAL-TIME BLACKLIST CHECKING =====
  // Get required elements
  const nameField = document.getElementById('full_name');
  const statusDiv = document.getElementById('blacklistCheckStatus');
  const overrideField = document.getElementById('override_blacklist_check');
  
  // Exit if any required elements are missing
  if (!nameField || !statusDiv || !overrideField) {
    console.error('Missing elements for blacklist checking');
    return;
  }
  
  // Utility: Debounce function to prevent excessive API calls
  function debounce(func, delay) {
    let timerId;
    return function() {
      const context = this;
      const args = arguments;
      clearTimeout(timerId);
      timerId = setTimeout(function() {
        func.apply(context, args);
      }, delay);
    };
  }
  
  // Main blacklist checking function
  function checkNameBlacklist() {
    const fullName = nameField.value.trim();
    
    // Clear indicators
    statusDiv.innerHTML = '';
    overrideField.value = 'false';
    
    // Skip if name is too short
    if (fullName.length < 5) return;
    
    // Format name (First Middle Last → Last, First Middle)
    const parts = fullName.split(' ');
    if (parts.length < 2) return; // Need at least first and last name
    
    const lastName = parts[parts.length - 1];
    const firstName = parts[0];
    const middleParts = parts.slice(1, parts.length - 1);
    const middleName = middleParts.join(' ');
    
    // Create multiple name formats for more thorough checking
    const formattedName = lastName + ', ' + firstName + (middleName ? ' ' + middleName : '');
    const originalName = fullName; // Original input format
    
    // Show loading spinner
    statusDiv.innerHTML = '<i class="fa fa-spinner fa-spin" style="color: #007bff;"></i>';
    console.log('Checking blacklist for names:', formattedName, 'and', originalName);
    
    // First check with formatted name (Last, First Middle)
    fetch('basic_blacklist_check.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'name=' + encodeURIComponent(formattedName)
    })
    .then(response => response.json())
    .then(data => {
      console.log('Blacklist response for formatted name:', data);
      
      if (data.blacklisted) {
        // Found in blacklist - show warning and display modal
        statusDiv.innerHTML = '<i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i>';
        overrideField.value = 'false';
        
        // Create blacklist details for modal
        let blacklistDetails = document.getElementById('blacklistMatchDetails');
        if (blacklistDetails) {
          let detailsHTML = '<div class="blacklist-match-item">';
          
          const blacklistData = data.details || {};
          detailsHTML += '<p><strong>Name:</strong> ' + (blacklistData.name || fullName) + '</p>';
          
          if (blacklistData.reason) {
            detailsHTML += '<p><strong>Reason:</strong> ' + blacklistData.reason + '</p>';
          }
          
          if (blacklistData.details) {
            detailsHTML += '<p><strong>Details:</strong> ' + blacklistData.details + '</p>';
          }
          
          if (blacklistData.reference_no) {
            detailsHTML += '<p><strong>Reference #:</strong> ' + blacklistData.reference_no + '</p>';
          }
          
          if (blacklistData.date_added) {
            detailsHTML += '<p><strong>Date Added:</strong> ' + blacklistData.date_added + '</p>';
          }
          
          detailsHTML += '</div>';
          blacklistDetails.innerHTML = detailsHTML;
          
          // Show the modal immediately
          document.getElementById('customBlacklistPopup').style.display = 'block';
        }
      } else {
        // Try with original name format as a backup check
        fetch('basic_blacklist_check.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'name=' + encodeURIComponent(originalName)
        })
        .then(response => response.json())
        .then(data => {
          console.log('Blacklist response for original name:', data);
          
          if (data.blacklisted) {
            // Found in blacklist - show warning and display modal
            statusDiv.innerHTML = '<i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i>';
            overrideField.value = 'false';
            
            // Create blacklist details for modal
            let blacklistDetails = document.getElementById('blacklistMatchDetails');
            if (blacklistDetails) {
              let detailsHTML = '<div class="blacklist-match-item">';
              
              const blacklistData = data.details || {};
              detailsHTML += '<p><strong>Name:</strong> ' + (blacklistData.name || fullName) + '</p>';
              
              if (blacklistData.reason) {
                detailsHTML += '<p><strong>Reason:</strong> ' + blacklistData.reason + '</p>';
              }
              
              if (blacklistData.details) {
                detailsHTML += '<p><strong>Details:</strong> ' + blacklistData.details + '</p>';
              }
              
              if (blacklistData.reference_no) {
                detailsHTML += '<p><strong>Reference #:</strong> ' + blacklistData.reference_no + '</p>';
              }
              
              if (blacklistData.date_added) {
                detailsHTML += '<p><strong>Date Added:</strong> ' + blacklistData.date_added + '</p>';
              }
              
              detailsHTML += '</div>';
              blacklistDetails.innerHTML = detailsHTML;
              
              // Show the modal immediately
              document.getElementById('customBlacklistPopup').style.display = 'block';
            }
          } else {
            // Not found in either check - show green checkmark
            statusDiv.innerHTML = '<i class="fa fa-check-circle" style="color: #28a745;"></i>';
            overrideField.value = 'true';
          }
        })
        .catch(error => {
          console.error('Error in secondary blacklist check:', error);
          statusDiv.innerHTML = '<i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i>';
          overrideField.value = 'false';
        });
      }
    })
    .catch(error => {
      console.error('Error in primary blacklist check:', error);
      statusDiv.innerHTML = '<i class="fa fa-exclamation-triangle" style="color: #dc3545;"></i>';
      overrideField.value = 'false';
    });
  }
  
  // Set up event listeners with debouncing
  const debouncedCheck = debounce(checkNameBlacklist, 500);
  nameField.addEventListener('input', debouncedCheck);
  nameField.addEventListener('blur', function() {
    if (this.value.trim().length >= 5 && statusDiv.innerHTML === '') {
      checkNameBlacklist();
    }
  });
  
  // Prevent form submission until verification is complete
  const form = document.querySelector('form.record-form');
  if (form) {
    form.addEventListener('submit', function(event) {
      // If the name field has content but no status icon (verification incomplete) or
      // if the override_blacklist_check value is 'false' (blacklisted)
      if ((nameField.value.trim().length >= 5 && statusDiv.innerHTML === '') || 
          overrideField.value === 'false') {
        // Always prevent form submission if blacklisted or verification incomplete
        event.preventDefault();
        
        // If verification is still running, wait and check
        if (statusDiv.innerHTML === '') {
          // Don't show alert - just start the check
          checkNameBlacklist(); // Start check immediately
        } else if (overrideField.value === 'false') {
          // If blacklisted, only show the modal (no alert)
          const blacklistModal = document.getElementById('customBlacklistPopup');
          if (blacklistModal) {
            blacklistModal.style.display = 'block';
          }
        }
      }
    });
  }
});
</script>

<style>
  /* Name input with blacklist status styling */
  .name-input-container {
    display: flex;
    align-items: center;
    position: relative;
    width: 100%;
  }
  
  .input-with-icon {
    position: relative;
    width: 100%;
    display: flex;
    align-items: center;
  }
  
  .input-with-icon .form-control {
    width: 100%;
    padding-right: 35px;
  }
  
  .input-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    z-index: 10;
  }
</style>

<style>
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
  
  .record-form {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 20px;
  }
  
  .form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }
  
  .form-grid .form-group {
    min-width: 0;
  }
  
  .form-grid label {
    display: flex;
    flex-direction: column;
    font-weight: 500;
    color: #555;
    font-size: 14px;
  }
  
  .form-grid input,
  .form-grid select,
  .form-grid textarea {
    margin-top: 5px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
  }
  
  .form-grid textarea {
    resize: vertical;
    min-height: 80px;
  }
  
  .form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
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
</style>

<?php include 'includes/blacklist_popup.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Add blacklist checking to name fields
  const lastNameField = document.getElementById('last_name');
  const givenNameField = document.getElementById('given_name');
  
  // Add status indicator next to the name field
  if (givenNameField) {
    const statusDiv = document.createElement('div');
    statusDiv.id = 'checkStatus';
    statusDiv.style.display = 'inline-block';
    statusDiv.style.marginLeft = '10px';
    statusDiv.style.width = '20px';
    statusDiv.style.height = '20px';
    givenNameField.parentNode.appendChild(statusDiv);
  }
  
  // Function to check blacklist when both names are filled
  function checkFullName() {
    const lastName = lastNameField ? lastNameField.value.trim() : '';
    const givenName = givenNameField ? givenNameField.value.trim() : '';
    
    if (lastName && givenName) {
      const fullName = givenName + ' ' + lastName;
      const nameInput = document.createElement('input');
      nameInput.value = fullName;
      
      // Use the reusable blacklist check function
      checkBlacklistAndShowPopup(nameInput, document.getElementById('checkStatus'));
    }
  }
  
  // Add input event listeners with debounce
  if (lastNameField && givenNameField) {
    const debounce = (func, delay) => {
      let debounceTimer;
      return function() {
        const context = this;
        const args = arguments;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => func.apply(context, args), delay);
      };
    };
    
    const debouncedCheck = debounce(checkFullName, 1000);
    
    lastNameField.addEventListener('input', debouncedCheck);
    givenNameField.addEventListener('input', debouncedCheck);
  }
});



