<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: job_fairs.php?error=No job fair ID specified');
    exit();
}

$job_fair_id = (int)$_GET['id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        if (empty($_POST['venue']) || empty($_POST['date'])) {
            throw new Exception("Venue and date are required fields");
        }

        // Update job fair record
        $stmt = $pdo->prepare("
            UPDATE job_fairs SET 
                venue = ?,
                date = ?,
                contact_info = ?,
                note = ?,
                status = ?
            WHERE id = ?
        ");

        $result = $stmt->execute([
            $_POST['venue'],
            $_POST['date'],
            $_POST['contact_info'],
            $_POST['note'],
            $_POST['status'],
            $job_fair_id
        ]);

        if ($result) {
            // Redirect to view page with success message
            header('Location: job_fair_view.php?id=' . $job_fair_id . '&success=Job fair updated successfully');
            exit();
        } else {
            throw new Exception("Failed to update job fair");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get job fair details
try {
    $stmt = $pdo->prepare("SELECT * FROM job_fairs WHERE id = ?");
    $stmt->execute([$job_fair_id]);
    $job_fair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job_fair) {
        header('Location: job_fairs.php?error=Job fair not found');
        exit();
    }
} catch (PDOException $e) {
    header('Location: job_fairs.php?error=' . urlencode($e->getMessage()));
    exit();
}

$pageTitle = "Edit Job Fair - MWPD Filing System";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="job-fair-edit-wrapper">
        <div class="page-header">
          <h1>Edit Job Fair</h1>
          <div class="header-actions">
            <a href="job_fair_view.php?id=<?= $job_fair_id ?>" class="btn btn-secondary">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </div>

        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>

        <form action="job_fair_edit.php?id=<?= $job_fair_id ?>" method="POST" class="job-fair-form">
          <div class="form-section">
            <h2>Job Fair Details</h2>
            
            <div class="form-grid">
              <div class="form-group">
                <label for="venue">Venue <span class="required">*</span></label>
                <input type="text" id="venue" name="venue" value="<?= htmlspecialchars($job_fair['venue']) ?>" required>
              </div>
              
              <div class="form-group">
                <label for="date">Date <span class="required">*</span></label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($job_fair['date']) ?>" required>
              </div>
              
              <div class="form-group">
                <label for="contact_info">Contact Information</label>
                <input type="text" id="contact_info" name="contact_info" value="<?= htmlspecialchars($job_fair['contact_info']) ?>">
              </div>
              
              <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                  <option value="planned" <?= $job_fair['status'] === 'planned' ? 'selected' : '' ?>>Planned</option>
                  <option value="confirmed" <?= $job_fair['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                  <option value="completed" <?= $job_fair['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                  <option value="cancelled" <?= $job_fair['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="form-section">
            <h2>Additional Information</h2>
            
            <div class="form-group">
              <label for="note">Notes</label>
              <textarea id="note" name="note" rows="5"><?= htmlspecialchars($job_fair['note']) ?></textarea>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-save"></i> Save Changes
            </button>
            <a href="job_fair_view.php?id=<?= $job_fair_id ?>" class="btn btn-secondary">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>

<style>
  .job-fair-edit-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
  }
  
  .header-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
  }
  
  .alert-danger {
    background-color: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
  }
  
  .job-fair-form {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }
  
  .form-section {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
  }
  
  .form-section h2 {
    margin-top: 0;
    margin-bottom: 1rem;
    font-size: 1.25rem;
  }
  
  .form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }
  
  .form-group {
    margin-bottom: 1rem;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
  }
  
  .required {
    color: #dc3545;
  }
  
  input[type="text"],
  input[type="date"],
  input[type="email"],
  select,
  textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1rem;
  }
  
  textarea {
    resize: vertical;
  }
  
  .form-actions {
    padding: 1.5rem;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-weight: 500;
  }
  
  .btn-primary {
    background-color: #007bff;
    color: white;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
</style>
</body>
</html>
