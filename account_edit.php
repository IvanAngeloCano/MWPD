<?php
include 'session.php';
require_once 'connection.php';
include_once 'unified_email_system.php'; // New unified email system with fixed Gmail SMTP
$pageTitle = "Edit User";
include '_head.php';

// Check if user has Division Head or Regional Director role
if ($_SESSION['role'] !== 'div head' && $_SESSION['role'] !== 'Division Head' && $_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
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
    $stmt = $pdo->prepare("SELECT id, username, full_name, role, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Initialize default values to prevent undefined array key errors
    if (!$user) {
        $user = [
            'id' => 0,
            'username' => '',
            'full_name' => '',
            'role' => 'Staff',
            'email' => ''
        ];
    }
    
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
    $email = trim($_POST['email']);
    
    try {
        // Update user information
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, email = ?, password = ? WHERE id = ?");
            $update_stmt->execute([$full_name, $role, $email, $hashed_password, $user_id]);
        } else {
            // Update without changing password
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, email = ? WHERE id = ?");
            $update_stmt->execute([$full_name, $role, $email, $user_id]);
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
          <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0; font-size: 24px; color: #333;">Edit User: <?= htmlspecialchars($user['username'] ?? '') ?></h1>
            <a href="accounts.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-weight: 500;">
              <i class="fa fa-arrow-left"></i> Back to Users
            </a>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <div class="card-body" style="padding: 20px;">
              <form method="POST" action="" class="record-form">
                <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px;">
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Username</label>
                    <input type="text" value="<?= htmlspecialchars($user['username'] ?? '') ?>" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px; background-color: #e9ecef;" disabled>
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px;">
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px;" required>
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Role</label>
                    <select name="role" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px; height: 42px;" required>
                      <option value="Staff" <?= (isset($user['role']) && ($user['role'] === 'Staff' || $user['role'] === 'staff')) ? 'selected' : '' ?>>Staff</option>
                      <option value="Division Head" <?= (isset($user['role']) && ($user['role'] === 'Division Head' || $user['role'] === 'div head')) ? 'selected' : '' ?>>Division Head</option>
                      <option value="Regional Director" <?= (isset($user['role']) && ($user['role'] === 'Regional Director' || $user['role'] === 'regional director')) ? 'selected' : '' ?>>Regional Director</option>
                    </select>
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px;" required>
                  </div>
                </div>
                
                <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                  <button type="reset" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; font-weight: 500;">
                    <i class="fa fa-refresh"></i> Reset
                  </button>
                  <button type="submit" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; font-weight: 500;">
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
