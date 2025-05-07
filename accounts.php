<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Account Management";
include '_head.php';

// Check if user has Division Head role
if ($_SESSION['role'] !== 'div head' && $_SESSION['role'] !== 'Division Head') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Get success or error messages from session
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY full_name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
}
?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="account-management-wrapper">
          <div class="page-header" style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">

            <a href="account_add.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 10px 15px; text-decoration: none; font-weight: 500;">
              <i class="fa fa-plus"></i> Add New User
            </a>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
              <?= htmlspecialchars($success_message) ?>
            </div>
          <?php endif; ?>

          <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="card-body" style="padding: 20px;">
              <div class="user-table-wrapper" style="overflow-x: auto;">
                <table class="user-table" style="width: 100%; border-collapse: collapse;">
                  <thead>
                    <tr>
                      <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Username</th>
                      <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Full Name</th>
                      <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Role</th>
                      <th style="padding: 12px 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($users as $index => $user): ?>
                      <tr style="background-color: <?= $index % 2 === 0 ? '#f8f9fa' : '#ffffff' ?>; border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($user['username']) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($user['full_name']) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;"><?= htmlspecialchars($user['role']) ?></td>
                        <td style="padding: 12px 15px; vertical-align: middle;">
                          <div style="display: flex; gap: 8px;">
                            <a href="account_edit.php?id=<?= $user['id'] ?>" title="Edit User" style="display: inline-flex; align-items: center; color: #28a745; text-decoration: none;">
                              <i class="fa fa-edit" style="font-size: 16px;"></i>
                            </a>
                            <a href="reset_password.php?id=<?= $user['id'] ?>" title="Reset Password" style="display: inline-flex; align-items: center; color: #ffc107; text-decoration: none;">
                              <i class="fa fa-key" style="font-size: 16px;"></i>
                            </a>
                            <a href="javascript:void(0)" onclick="showDeleteModal(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')" title="Delete User" style="display: inline-flex; align-items: center; color: #dc3545; text-decoration: none;">
                              <i class="fa fa-trash" style="font-size: 16px;"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (count($users) === 0): ?>
                      <tr>
                        <td colspan="4" style="padding: 20px; text-align: center; color: #6c757d;">No users found</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
  
  <!-- Delete User Confirmation Modal -->
  <div id="deleteUserModal" class="modal">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Deletion</h5>
          <button type="button" class="close" onclick="closeDeleteModal()" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger">
            <p>Are you sure you want to delete user <strong id="deleteUsername"></strong>?</p>
            <p class="mb-0">This action cannot be undone.</p>
          </div>
        </div>
        <div class="modal-footer">
          <form method="POST" action="process_user_deletion.php" id="deleteForm">
            <input type="hidden" name="user_id" id="deleteUserId" value="">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
              <i class="fa fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-danger">
              <i class="fa fa-trash"></i> Delete User
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 1000;
    }
    
    .modal-dialog {
      position: relative;
      width: auto;
      margin: 1.75rem auto;
      max-width: 500px;
    }
    
    .modal-dialog-centered {
      display: flex;
      align-items: center;
      min-height: calc(100% - 3.5rem);
    }
    
    .modal-content {
      position: relative;
      display: flex;
      flex-direction: column;
      width: 100%;
      background-color: #fff;
      border-radius: 0.3rem;
      box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.5);
      outline: 0;
    }
    
    .modal-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      padding: 1rem;
      border-bottom: 1px solid #dee2e6;
      border-top-left-radius: 0.3rem;
      border-top-right-radius: 0.3rem;
    }
    
    .modal-title {
      margin-bottom: 0;
      line-height: 1.5;
      font-size: 1.25rem;
    }
    
    button.close {
      background-color: transparent;
      border: 0;
      font-size: 1.5rem;
      font-weight: 700;
      color: #000;
      text-shadow: 0 1px 0 #fff;
      opacity: 0.5;
      padding: 0;
      cursor: pointer;
    }
    
    .modal-body {
      position: relative;
      flex: 1 1 auto;
      padding: 1rem;
    }
    
    .modal-footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding: 1rem;
      border-top: 1px solid #dee2e6;
    }
    
    .modal-footer form {
      display: flex;
      width: 100%;
      justify-content: flex-end;
    }
    
    .modal-footer .btn {
      margin-left: 0.5rem;
    }
    
    .btn {
      display: inline-block;
      font-weight: 400;
      text-align: center;
      vertical-align: middle;
      cursor: pointer;
      padding: 0.375rem 0.75rem;
      font-size: 1rem;
      line-height: 1.5;
      border-radius: 0.25rem;
      transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .btn-secondary {
      color: #fff;
      background-color: #6c757d;
      border-color: #6c757d;
    }
    
    .btn-danger {
      color: #fff;
      background-color: #dc3545;
      border-color: #dc3545;
    }
    
    .mb-0 {
      margin-bottom: 0;
    }
    
    /* Table Row Styles */
    .edit-link, .delete-link {
      display: inline-flex;
      align-items: center;
      padding: 5px 10px;
      margin-right: 5px;
      border-radius: 4px;
      text-decoration: none;
      font-weight: 500;
    }
    
    .edit-link {
      background-color: #007bff;
      color: white;
    }
    
    .delete-link {
      background-color: #dc3545;
      color: white;
    }
    
    .edit-link i, .delete-link i {
      margin-right: 5px;
    }
  </style>

  <script>
    // Check if we need to show the delete modal (from redirects)
    document.addEventListener('DOMContentLoaded', function() {
      <?php if (isset($_GET['show_delete_modal']) && $_GET['show_delete_modal'] == 1 && isset($_SESSION['delete_user_id']) && isset($_SESSION['delete_username'])): ?>
        // Show delete modal with data from session
        showDeleteModal(<?= $_SESSION['delete_user_id'] ?>, '<?= htmlspecialchars(addslashes($_SESSION['delete_username'])) ?>');
        <?php 
          // Clear the session variables
          unset($_SESSION['delete_user_id']);
          unset($_SESSION['delete_username']);
        ?>
      <?php endif; ?>
    });
    
    // Add double-click functionality to table rows
    document.addEventListener('DOMContentLoaded', function() {
      const tableRows = document.querySelectorAll('.user-table tbody tr');
      
      tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        
        row.addEventListener('dblclick', function(e) {
          // Make sure we're not clicking on a button or link
          if (e.target.tagName.toLowerCase() !== 'button' && 
              e.target.tagName.toLowerCase() !== 'a' && 
              e.target.tagName.toLowerCase() !== 'i' &&
              !e.target.closest('button') && 
              !e.target.closest('a')) {
            
            // Get the edit link for this row
            const editLink = this.querySelector('a[href*="account_edit.php"]');
            if (editLink) {
              window.location.href = editLink.getAttribute('href');
            }
          }
        });
      });
    });
    
    // Delete modal functions
    function showDeleteModal(userId, username) {
      console.log('Opening delete modal for user:', username, 'with ID:', userId);
      document.getElementById('deleteUserId').value = userId;
      document.getElementById('deleteUsername').textContent = username;
      document.getElementById('deleteUserModal').style.display = 'block';
      document.body.style.overflow = 'hidden'; // Disable scrolling
    }
    
    function closeDeleteModal() {
      console.log('Closing delete modal');
      document.getElementById('deleteUserModal').style.display = 'none';
      document.body.style.overflow = ''; // Re-enable scrolling
    }
    
    // Close the modal if the user clicks outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('deleteUserModal');
      if (event.target === modal) {
        closeDeleteModal();
      }
    };
  </script>
</body>
</html>
