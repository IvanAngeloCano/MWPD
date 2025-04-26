<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Add New User - MWPD Filing System";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    
    // Validate input
    if (empty($username) || empty($password) || empty($full_name) || empty($role)) {
        $error_message = "All fields are required.";
    } else {
        try {
            // Check if username already exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check_stmt->execute([$username]);
            $user_exists = $check_stmt->fetchColumn() > 0;
            
            if ($user_exists) {
                $error_message = "Username already exists. Please choose another username.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $insert_stmt->execute([$username, $hashed_password, $full_name, $role]);
                
                $_SESSION['success_message'] = "User added successfully.";
                header('Location: accounts.php');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
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
            <h2>Add New User</h2>
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
                    <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
                  </label>
                  
                  <label>
                    Password
                    <input type="password" name="password" required>
                  </label>
                  
                  <label>
                    Full Name
                    <input type="text" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" required>
                  </label>
                  
                  <label>
                    Role
                    <select name="role" required>
                      <option value="">Select Role</option>
                      <option value="Staff" <?= isset($role) && $role === 'Staff' ? 'selected' : '' ?>>Staff</option>
                      <option value="Division Head" <?= isset($role) && $role === 'Division Head' ? 'selected' : '' ?>>Division Head</option>
                      <option value="Regional Director" <?= isset($role) && $role === 'Regional Director' ? 'selected' : '' ?>>Regional Director</option>
                    </select>
                  </label>
                </div>
                
                <div class="form-actions">
                  <a href="accounts.php" class="btn btn-cancel">
                    <i class="fa fa-times"></i> Cancel
                  </a>
                  <button type="reset" class="btn btn-reset">
                    <i class="fa fa-refresh"></i> Reset
                  </button>
                  <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save User
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
