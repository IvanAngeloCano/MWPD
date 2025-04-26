<?php
include 'session.php';
require_once 'connection.php';

$success_message = '';
$error_message = '';

// Handle form submission for adding/editing signatories
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new signatory
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $position = trim($_POST['position']);
            $position_order = (int)$_POST['position_order'];
            
            // Upload signature file if provided
            $signature_file = null;
            if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($_FILES['signature_file']['name']);
                $extension = strtolower($file_info['extension']);
                
                // Check if file is an image
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $error_message = 'Only image files (JPG, JPEG, PNG, GIF) are allowed for signatures.';
                } else {
                    // Generate a unique filename
                    $signature_file = 'signature_' . time() . '_' . uniqid() . '.' . $extension;
                    $target_path = 'signatures/' . $signature_file;
                    
                    if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $target_path)) {
                        // File uploaded successfully
                    } else {
                        $error_message = 'Failed to upload signature file.';
                        $signature_file = null;
                    }
                }
            }
            
            if (empty($error_message)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO signatories (name, position, position_order, signature_file) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $position, $position_order, $signature_file]);
                    $success_message = 'Signatory added successfully.';
                } catch (PDOException $e) {
                    $error_message = 'Error adding signatory: ' . $e->getMessage();
                }
            }
        }
        // Update existing signatory
        elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $position = trim($_POST['position']);
            $position_order = (int)$_POST['position_order'];
            $active = isset($_POST['active']) ? 1 : 0;
            
            // Get current signature file
            $stmt = $pdo->prepare("SELECT signature_file FROM signatories WHERE id = ?");
            $stmt->execute([$id]);
            $current_file = $stmt->fetchColumn();
            
            // Upload new signature file if provided
            $signature_file = $current_file;
            if (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($_FILES['signature_file']['name']);
                $extension = strtolower($file_info['extension']);
                
                // Check if file is an image
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $error_message = 'Only image files (JPG, JPEG, PNG, GIF) are allowed for signatures.';
                } else {
                    // Generate a unique filename
                    $signature_file = 'signature_' . time() . '_' . uniqid() . '.' . $extension;
                    $target_path = 'signatures/' . $signature_file;
                    
                    if (move_uploaded_file($_FILES['signature_file']['tmp_name'], $target_path)) {
                        // Delete old file if exists
                        if ($current_file && file_exists('signatures/' . $current_file)) {
                            unlink('signatures/' . $current_file);
                        }
                    } else {
                        $error_message = 'Failed to upload signature file.';
                        $signature_file = $current_file; // Keep current file if upload fails
                    }
                }
            }
            
            if (empty($error_message)) {
                try {
                    $stmt = $pdo->prepare("UPDATE signatories SET name = ?, position = ?, position_order = ?, signature_file = ?, active = ? WHERE id = ?");
                    $stmt->execute([$name, $position, $position_order, $signature_file, $active, $id]);
                    $success_message = 'Signatory updated successfully.';
                } catch (PDOException $e) {
                    $error_message = 'Error updating signatory: ' . $e->getMessage();
                }
            }
        }
        // Delete signatory
        elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            // Get signature file to delete
            $stmt = $pdo->prepare("SELECT signature_file FROM signatories WHERE id = ?");
            $stmt->execute([$id]);
            $signature_file = $stmt->fetchColumn();
            
            try {
                $stmt = $pdo->prepare("DELETE FROM signatories WHERE id = ?");
                $stmt->execute([$id]);
                
                // Delete signature file if exists
                if ($signature_file && file_exists('signatures/' . $signature_file)) {
                    unlink('signatures/' . $signature_file);
                }
                
                $success_message = 'Signatory deleted successfully.';
            } catch (PDOException $e) {
                $error_message = 'Error deleting signatory: ' . $e->getMessage();
            }
        }
    }
}

// Get all signatories
try {
    $stmt = $pdo->query("SELECT * FROM signatories ORDER BY position_order, name");
    $signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Error retrieving signatories: ' . $e->getMessage();
    $signatories = [];
}

$pageTitle = "Manage Signatories";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="container">
        <h1>Manage Signatories</h1>
        
        <?php if (!empty($success_message)): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($success_message) ?>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
          </div>
        <?php endif; ?>
        
        <div class="card">
          <div class="card-header">
            <h2>Add New Signatory</h2>
          </div>
          <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="add">
              
              <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" class="form-control" required>
              </div>
              
              <div class="form-group">
                <label for="position">Position:</label>
                <input type="text" id="position" name="position" class="form-control" required>
              </div>
              
              <div class="form-group">
                <label for="position_order">Display Order:</label>
                <input type="number" id="position_order" name="position_order" class="form-control" value="0" min="0">
              </div>
              
              <div class="form-group">
                <label for="signature_file">Signature Image (Optional)</label>
                <input type="file" class="form-control-file" id="signature_file" name="signature_file" accept="image/jpeg,image/png,image/gif">
                <small class="form-text text-muted">Upload an image file of the signature (JPG, PNG, GIF).</small>
              </div>
              
              <button type="submit" class="btn btn-primary">Add Signatory</button>
            </form>
          </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-header">
            <h2>Current Signatories</h2>
          </div>
          <div class="card-body">
            <?php if (count($signatories) > 0): ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Position</th>
                      <th>Order</th>
                      <th>Status</th>
                      <th>Signature</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($signatories as $signatory): ?>
                      <tr>
                        <td><?= htmlspecialchars($signatory['name']) ?></td>
                        <td><?= htmlspecialchars($signatory['position']) ?></td>
                        <td><?= $signatory['position_order'] ?></td>
                        <td>
                          <span class="badge <?= $signatory['active'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $signatory['active'] ? 'Active' : 'Inactive' ?>
                          </span>
                        </td>
                        <td>
                          <?php if (!empty($signatory['signature_file']) && file_exists('signatures/' . $signatory['signature_file'])): ?>
                            <img src="signatures/<?= htmlspecialchars($signatory['signature_file']) ?>" alt="Signature" style="max-height: 50px;">
                          <?php else: ?>
                            <span class="text-muted">No signature</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal<?= $signatory['id'] ?>">
                            <i class="fa fa-edit"></i> Edit
                          </button>
                          <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?= $signatory['id'] ?>">
                            <i class="fa fa-trash"></i> Delete
                          </button>
                        </td>
                      </tr>
                      
                      <!-- Edit Modal -->
                      <div class="modal fade" id="editModal<?= $signatory['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?= $signatory['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="editModalLabel<?= $signatory['id'] ?>">Edit Signatory</h5>
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                            <form action="" method="POST" enctype="multipart/form-data">
                              <div class="modal-body">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?= $signatory['id'] ?>">
                                
                                <div class="form-group">
                                  <label for="edit_name<?= $signatory['id'] ?>">Name:</label>
                                  <input type="text" id="edit_name<?= $signatory['id'] ?>" name="name" class="form-control" value="<?= htmlspecialchars($signatory['name']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                  <label for="edit_position<?= $signatory['id'] ?>">Position:</label>
                                  <input type="text" id="edit_position<?= $signatory['id'] ?>" name="position" class="form-control" value="<?= htmlspecialchars($signatory['position']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                  <label for="edit_position_order<?= $signatory['id'] ?>">Display Order:</label>
                                  <input type="number" id="edit_position_order<?= $signatory['id'] ?>" name="position_order" class="form-control" value="<?= $signatory['position_order'] ?>" min="0">
                                </div>
                                
                                <div class="form-group">
                                  <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="edit_active<?= $signatory['id'] ?>" name="active" <?= $signatory['active'] ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="edit_active<?= $signatory['id'] ?>">Active</label>
                                  </div>
                                </div>
                                
                                <div class="form-group">
                                  <label for="edit_signature_file<?= $signatory['id'] ?>">Signature Image:</label>
                                  <?php if (!empty($signatory['signature_file']) && file_exists('signatures/' . $signatory['signature_file'])): ?>
                                    <div class="mb-2">
                                      <img src="signatures/<?= htmlspecialchars($signatory['signature_file']) ?>" alt="Current Signature" style="max-height: 100px;">
                                      <p class="text-muted">Current signature</p>
                                    </div>
                                  <?php endif; ?>
                                  <input type="file" id="edit_signature_file<?= $signatory['id'] ?>" name="signature_file" class="form-control">
                                  <small class="form-text text-muted">Upload a new signature image to replace the current one (JPG, PNG, GIF).</small>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                              </div>
                            </form>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Delete Modal -->
                      <div class="modal fade" id="deleteModal<?= $signatory['id'] ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?= $signatory['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="deleteModalLabel<?= $signatory['id'] ?>">Confirm Delete</h5>
                              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                              </button>
                            </div>
                            <div class="modal-body">
                              <p>Are you sure you want to delete the signatory <strong><?= htmlspecialchars($signatory['name']) ?></strong>?</p>
                              <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                              <form action="" method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $signatory['id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                              </form>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">No signatories found. Add some using the form above.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<style>
.container {
  padding: 20px;
}

.card {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 20px;
}

.card-header {
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
  background-color: #f8f9fa;
}

.card-header h2 {
  margin: 0;
  font-size: 18px;
}

.card-body {
  padding: 20px;
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
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
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

.alert {
  padding: 12px 15px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.table-striped tbody tr:nth-of-type(odd) {
  background-color: rgba(0,0,0,0.02);
}

.badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.badge-success {
  background-color: #d4edda;
  color: #155724;
}

.badge-secondary {
  background-color: #e9ecef;
  color: #6c757d;
}

.mt-4 {
  margin-top: 1.5rem;
}

.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.4);
}

.modal-dialog {
  margin: 30px auto;
  max-width: 500px;
}

.modal-content {
  position: relative;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.modal-header {
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-title {
  margin: 0;
  font-size: 18px;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.close {
  background: none;
  border: none;
  font-size: 24px;
  font-weight: bold;
  cursor: pointer;
  color: #888;
}

.text-muted {
  color: #6c757d;
}

.text-danger {
  color: #dc3545;
}

.custom-control {
  position: relative;
  display: block;
  min-height: 1.5rem;
  padding-left: 1.5rem;
}

.custom-control-input {
  position: absolute;
  z-index: -1;
  opacity: 0;
}

.custom-control-label {
  position: relative;
  margin-bottom: 0;
  vertical-align: top;
}

.custom-control-label::before {
  position: absolute;
  top: 0.25rem;
  left: -1.5rem;
  display: block;
  width: 1rem;
  height: 1rem;
  content: "";
  background-color: #fff;
  border: 1px solid #adb5bd;
  border-radius: 0.25rem;
}

.custom-control-input:checked ~ .custom-control-label::before {
  color: #fff;
  border-color: #007bff;
  background-color: #007bff;
}

.custom-checkbox .custom-control-label::before {
  border-radius: 0.25rem;
}

.custom-checkbox .custom-control-input:checked ~ .custom-control-label::after {
  position: absolute;
  top: 0.25rem;
  left: -1.5rem;
  display: block;
  width: 1rem;
  height: 1rem;
  content: "";
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%23fff' d='M6.564.75l-3.59 3.612-1.538-1.55L0 4.26l2.974 2.99L8 2.193z'/%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 50% 50%;
}
</style>

<script>
  // Initialize Bootstrap modals
  document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for modal triggers
    const modalTriggers = document.querySelectorAll('[data-toggle="modal"]');
    modalTriggers.forEach(function(trigger) {
      trigger.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target').substring(1);
        const modal = document.getElementById(targetId);
        if (modal) {
          modal.style.display = 'block';
        }
      });
    });
    
    // Add click handlers for modal close buttons
    const closeButtons = document.querySelectorAll('.close, [data-dismiss="modal"]');
    closeButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        const modal = this.closest('.modal');
        if (modal) {
          modal.style.display = 'none';
        }
      });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
      }
    });
  });
</script>
