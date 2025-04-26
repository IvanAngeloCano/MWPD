<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Edit User - MWPD Filing System";
include '_head.php';

// Check if user has Division Head role
if ($_SESSION['role'] !== 'div head' && $_SESSION['role'] !== 'Division Head') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Process form submission
$success_message = '';
$error_message = '';

// Check if user ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "No user specified for editing.";
    header('Location: accounts.php');
    exit();
}

$user_id = $_GET['id'];

// Get user data
try {
    $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header('Location: accounts.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching user data: " . $e->getMessage();
    header('Location: accounts.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Edit existing user
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);
    
    try {
        // Update user information
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, password = ? WHERE id = ?");
            $update_stmt->execute([$full_name, $role, $hashed_password, $user_id]);
        } else {
            // Update without changing password
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ? WHERE id = ?");
            $update_stmt->execute([$full_name, $role, $user_id]);
        }
        
        $_SESSION['success_message'] = "User updated successfully.";
        header('Location: accounts.php');
        exit();
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
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
            <h2>Edit User: <?= htmlspecialchars($user['username']) ?></h2>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <div class="card">
            <div class="card-body">
              <form method="POST" action="" class="record-form">
                <div class="form-grid">
                  <label>
                    Username
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                  </label>
                  
                  <label>
                    Password
                    <input type="password" name="password" placeholder="Leave blank to keep current password">
                  </label>
                  
                  <label>
                    Full Name
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                  </label>
                  
                  <label>
                    Role
                    <select name="role" required>
                      <option value="Staff" <?= $user['role'] === 'Staff' || $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                      <option value="Division Head" <?= $user['role'] === 'Division Head' || $user['role'] === 'div head' ? 'selected' : '' ?>>Division Head</option>
                      <option value="Regional Director" <?= $user['role'] === 'Regional Director' || $user['role'] === 'regional director' ? 'selected' : '' ?>>Regional Director</option>
                    </select>
                  </label>
                </div>
                
                <div class="form-actions">
                  <a href="accounts.php" class="btn btn-cancel">
                    <i class="fa fa-times"></i> Cancel
                  </a>
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
