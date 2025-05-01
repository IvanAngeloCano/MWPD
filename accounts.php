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
          <div class="page-header">
            <a href="account_add.php" class="btn btn-primary">
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

          <div class="card">
            <div class="card-body">
              <div class="user-table-wrapper">
                <table class="user-table">
                  <thead>
                    <tr>
                      <th>Username</th>
                      <th>Full Name</th>
                      <th>Role</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($users as $user): ?>
                      <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td class="actions">
                          <a href="account_edit.php?id=<?= $user['id'] ?>" class="edit-link">
                            <i class="fa fa-edit"></i> Edit
                          </a>
                          <a href="account_delete.php?id=<?= $user['id'] ?>" class="delete-link">
                            <i class="fa fa-trash"></i> Delete
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (count($users) === 0): ?>
                      <tr>
                        <td colspan="4" class="text-center">No users found</td>
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
</body>
</html>
