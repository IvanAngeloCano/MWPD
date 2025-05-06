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
                            <a href="account_delete.php?id=<?= $user['id'] ?>" title="Delete User" style="display: inline-flex; align-items: center; color: #dc3545; text-decoration: none;">
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

  <style>
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
  </script>
</body>
</html>
