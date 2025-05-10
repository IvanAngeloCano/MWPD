<?php
include 'session.php';
require_once 'connection.php';

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate form data
        $required_fields = ['date', 'venue'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Get form data
        $date = $_POST['date'];
        $venue = $_POST['venue'];
        $contact_numbers = $_POST['contact_numbers'] ?? '';
        $invitation_contact_email = $_POST['invitation_contact_email'] ?? '';
        $status = $_POST['status'] ?? 'planned';
        $note = $_POST['note'] ?? '';
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception("Invalid date format. Please use YYYY-MM-DD format.");
        }
        
        // Insert into job_fairs table
        $sql = "INSERT INTO job_fairs (date, venue, contact_info, note, status) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $date,
            $venue,
            $contact_numbers,
            $note,
            $status
        ]);
        
        $job_fair_id = $pdo->lastInsertId();
        
        // Redirect based on button clicked
        if (isset($_POST['save_and_add'])) {
            $success_message = "Job fair added successfully. You can add another job fair below.";
        } else {
            // Redirect to the job fairs listing page
            header("Location: job_fairs.php?success=Job fair added successfully");
            exit();
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

$pageTitle = "Add New Job Fair";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    include '_header.php';
    ?>

    <main class="main-content">
      <div class="job-fair-add-wrapper">
        <!-- <div class="page-header">
          <h1>Add New Job Fair</h1>
          <p>Create a new job fair event in the system</p>
        </div> -->
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
          <i class="fa fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <!-- Form Section -->
        <form class="job-fair-form" method="POST" action="">
          <div class="form-grid">
            <div class="form-group">
              <label for="date">Date<span class="required">*</span></label>
              <input type="date" id="date" name="date" required>
            </div>
            
            <div class="form-group">
              <label for="venue">Venue<span class="required">*</span></label>
              <input type="text" id="venue" name="venue" placeholder="Enter job fair venue" required>
            </div>
            
            <div class="form-group">
              <label for="contact_numbers">Contact Numbers</label>
              <input type="text" id="contact_numbers" name="contact_numbers" placeholder="Enter contact numbers">
            </div>
            
            <div class="form-group">
              <label for="invitation_contact_email">Invitation Contact Email</label>
              <input type="email" id="invitation_contact_email" name="invitation_contact_email" placeholder="Enter email for invitation correspondence">
            </div>
            
            <div class="form-group">
              <label for="status">Status</label>
              <select id="status" name="status">
                <option value="planned">Planned</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            
            <div class="form-group full-width">
              <label for="note">Notes</label>
              <textarea id="note" name="note" rows="4" placeholder="Enter any additional notes or information"></textarea>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
            <button type="submit" name="save_and_add" value="1" class="btn btn-outline-primary"><i class="fa fa-plus"></i> Save and Add Another</button>
            <button type="reset" class="btn btn-secondary"><i class="fa fa-undo"></i> Reset</button>
            <a href="job_fairs.php" class="btn btn-link">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<style>
  .job-fair-add-wrapper {
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .page-header {
    margin-bottom: 2rem;
  }
  
  .page-header h1 {
    margin: 0 0 0.5rem 0;
    color: #343a40;
  }
  
  .page-header p {
    margin: 0;
    color: #6c757d;
  }
  
  .alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .alert-success {
    background-color: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
  }
  
  .alert-danger {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
  }
  
  .job-fair-form {
    background-color: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }
  
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .form-group.full-width {
    grid-column: span 2;
  }
  
  .form-group label {
    font-weight: 500;
    color: #495057;
  }
  
  .required {
    color: #dc3545;
    margin-left: 0.25rem;
  }
  
  input[type="text"],
  input[type="email"],
  input[type="date"],
  select,
  textarea {
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.15s ease-in-out;
  }
  
  input[type="text"]:focus,
  input[type="email"]:focus,
  input[type="date"]:focus,
  select:focus,
  textarea:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
  }
  
  .form-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-weight: 500;
    border: none;
    text-align: center;
    font-size: 1rem;
  }
  
  .btn-primary {
    background-color: #007bff;
    color: white;
  }
  
  .btn-outline-primary {
    background-color: transparent;
    border: 1px solid #007bff;
    color: #007bff;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-link {
    background-color: transparent;
    color: #007bff;
    text-decoration: none;
  }
</style> 