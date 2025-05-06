<?php
include 'session.php';
require_once 'connection.php';

// Process form submission
$success_message = '';
$error_message = '';

// Check if ID is provided
if (!isset($_GET['bmid']) || empty($_GET['bmid'])) {
    header("Location: balik_manggagawa.php?error=No record ID provided");
    exit();
}

$bmid = $_GET['bmid'];

// Get the record data
try {
    $stmt = $pdo->prepare("SELECT * FROM bm WHERE bmid = :bmid");
    $stmt->bindParam(':bmid', $bmid);
    $stmt->execute();
    
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        header("Location: balik_manggagawa.php?error=Record not found");
        exit();
    }
} catch (PDOException $e) {
    header("Location: balik_manggagawa.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate form data
        $required_fields = ['last_name', 'given_name', 'middle_name', 'sex', 'address', 'destination'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Get form data
        $last_name = $_POST['last_name'];
        $given_name = $_POST['given_name'];
        $middle_name = $_POST['middle_name'];
        $sex = $_POST['sex'];
        $address = $_POST['address'];
        $destination = $_POST['destination'];
        // Preserve the existing remarks value - users cannot edit this field
        $remarks = $record['remarks'];
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update BM table
        $sql = "UPDATE bm SET 
                last_name = :last_name,
                given_name = :given_name,
                middle_name = :middle_name,
                sex = :sex,
                address = :address,
                destination = :destination,
                remarks = :remarks
                WHERE bmid = :bmid";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'last_name' => $last_name,
            'given_name' => $given_name,
            'middle_name' => $middle_name,
            'sex' => $sex,
            'address' => $address,
            'destination' => $destination,
            'remarks' => $remarks,
            'bmid' => $bmid
        ]);
        
        // Add notification for record update
        if (function_exists('addNotification') && isset($_SESSION['user_id'])) {
            $notificationText = "Balik Manggagawa record updated: $last_name, $given_name";
            addNotification($_SESSION['user_id'], $notificationText);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect with success message
        header("Location: balik_manggagawa.php?success=Record updated successfully");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

$pageTitle = "Edit Record - Balik Manggagawa";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    $currentFile = basename($_SERVER['PHP_SELF']);
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
    $pageTitle = 'Balik Manggagawa - Edit Record';
    $currentPage = 'balik_manggagawa.php';
    include '_header.php';
    ?>

    <main class="main-content">
      <div class="add-record-wrapper">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <h2>Edit Balik Manggagawa Record</h2>
          
          <!-- Submit for Approval Button (Top Right) -->
          <form action="submit_bm_for_approval.php" method="GET" id="approvalForm" style="margin: 0;">
            <input type="hidden" name="bmid" value="<?= $record['bmid'] ?>">
            <button type="submit" 
                  class="btn btn-primary"
                  id="submitApprovalBtn"
                  <?php if (isset($record['status']) && ($record['status'] === 'Approved' || $record['status'] === 'approved' || $record['status'] === 'Declined' || $record['status'] === 'declined')): ?>disabled style="pointer-events:none;opacity:0.6;"<?php endif; ?>>
              <i class="fa fa-check"></i> Submit for Approval
            </button>
          </form>
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
        <form class="record-form" method="POST" action="">
          <div class="form-grid">
            <label>Last Name<input type="text" name="last_name" value="<?= htmlspecialchars($record['last_name']) ?>" required></label>
            <label>Given Name<input type="text" name="given_name" value="<?= htmlspecialchars($record['given_name']) ?>" required></label>
            <label>Middle Name<input type="text" name="middle_name" value="<?= htmlspecialchars($record['middle_name']) ?>" required></label>
            
            <label>Sex
              <select name="sex" required>
                <option value="">Select Sex</option>
                <option value="Male" <?= $record['sex'] === 'Male' ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= $record['sex'] === 'Female' ? 'selected' : '' ?>>Female</option>
              </select>
            </label>
            
            <label>Address<input type="text" name="address" value="<?= htmlspecialchars($record['address']) ?>" required></label>
            <label>Destination<input type="text" name="destination" value="<?= htmlspecialchars($record['destination']) ?>" required></label>
            
            <label>Name of the Agency<input type="text" name="nameoftheagency" value="<?= htmlspecialchars($record['nameoftheagency'] ?? '') ?>"></label>
            <label>Name of the Principal<input type="text" name="nameoftheprincipal" value="<?= htmlspecialchars($record['nameoftheprincipal'] ?? '') ?>"></label>
            <label>Name of the New Agency<input type="text" name="nameofthenewagency" value="<?= htmlspecialchars($record['nameofthenewagency'] ?? '') ?>"></label>
            <label>Name of the New Principal<input type="text" name="nameofthenewprincipal" value="<?= htmlspecialchars($record['nameofthenewprincipal'] ?? '') ?>"></label>
            
            <label>Employment Duration Start<input type="date" name="employmentdurationstart" value="<?= htmlspecialchars($record['employmentdurationstart'] ?? '') ?>"></label>
            <label>Employment Duration End<input type="date" name="employmentdurationend" value="<?= htmlspecialchars($record['employmentdurationend'] ?? '') ?>"></label>
            <label>Date of Arrival<input type="date" name="dateofarrival" value="<?= htmlspecialchars($record['dateofarrival'] ?? '') ?>"></label>
            <label>Date of Departure<input type="date" name="dateofdeparture" value="<?= htmlspecialchars($record['dateofdeparture'] ?? '') ?>"></label>
          </div>

          <!-- Action Buttons -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Changes</button>
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
    margin-right: 10px;
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
  
  .record-form {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 20px;
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
