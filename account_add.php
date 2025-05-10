<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Add New User";
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
    $email = trim($_POST['email']);
    
    // Validate input
    if (empty($username) || empty($password) || empty($full_name) || empty($role) || empty($email)) {
        $error_message = "All fields are required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if username already exists in users table
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $check_stmt->execute([$username]);
            $user_exists = $check_stmt->fetchColumn() > 0;
            
            // Check if username already exists in pending approvals
            $check_approval_stmt = $pdo->prepare("SELECT COUNT(*) FROM account_approvals WHERE username = ? AND status = 'pending'");
            $check_approval_stmt->execute([$username]);
            $approval_exists = $check_approval_stmt->fetchColumn() > 0;
            
            if ($user_exists) {
                $error_message = "Username already exists. Please choose another username.";
            } else if ($approval_exists) {
                $error_message = "Username is already pending approval. Please choose another username.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user request for approval
                $insert_stmt = $pdo->prepare("INSERT INTO account_approvals (username, password, full_name, role, email, submitted_by) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$username, $hashed_password, $full_name, $role, $email, $_SESSION['user_id']]);
                
                // Get the submitter's name
                $submitter_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                $submitter_stmt->execute([$_SESSION['user_id']]);
                $submitter = $submitter_stmt->fetch(PDO::FETCH_ASSOC);
                $submitter_name = $submitter ? $submitter['full_name'] : 'Unknown';
                
                // Check if email column exists in users table
                $email_column_exists = false;
                try {
                    $check_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
                    $email_column_exists = $check_stmt->rowCount() > 0;
                } catch (PDOException $e) {
                    // If there's an error, assume the column doesn't exist
                    $email_column_exists = false;
                }
                
                // Notify Regional Directors about the new account request via in-app notification
                include_once 'notifications.php';
                notifyNewUserRequest($username, $full_name);
                
                // Send email notifications to all Regional Directors (if email column exists)
                if ($email_column_exists) {
                    include_once 'email_notifications.php';
                    
                    try {
                        // Find all Regional Directors with email
                        $rd_stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE (role = 'Regional Director' OR role = 'regional director') AND email IS NOT NULL");
                        $rd_stmt->execute();
                        $directors = $rd_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Send email to each director
                        foreach ($directors as $director) {
                            if (!empty($director['email'])) {
                                sendNewAccountRequestEmail(
                                    $director['email'],
                                    $submitter_name,
                                    $username,
                                    $full_name,
                                    $role
                                );
                            }
                        }
                    } catch (PDOException $e) {
                        // Log the error but continue with success
                        file_put_contents('email_error_log.txt', date('Y-m-d H:i:s') . ": Error sending account request emails: " . $e->getMessage() . "\n", FILE_APPEND);
                    }
                } else {
                    // Email column doesn't exist - show a message in the add_email_column.php script
                    $_SESSION['email_column_missing'] = true;
                }
                
                $_SESSION['success_message'] = "User account request submitted for approval.";
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
          <div class="page-header" style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 20px;">
            <a href="account_dashboard.php?tab=users" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-weight: 500;">
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
                    <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px;" required>
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Password</label>
                    <input type="password" name="password" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px;" required>
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px;" required>
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px;" required>
                  </div>
                  
                  <div style="display: flex; flex-direction: column;">
                    <label style="font-weight: 500; margin-bottom: 8px;">Role</label>
                    <select name="role" style="padding: 10px; border: 1px solid #ced4da; border-radius: 4px; height: 42px;" required>
                      <option value="">Select Role</option>
                      <option value="Staff" <?= isset($role) && $role === 'Staff' ? 'selected' : '' ?>>Staff</option>
                      <option value="Division Head" <?= isset($role) && $role === 'Division Head' ? 'selected' : '' ?>>Division Head</option>
                      <option value="Regional Director" <?= isset($role) && $role === 'Regional Director' ? 'selected' : '' ?>>Regional Director</option>
                    </select>
                  </div>
                </div>
                
                <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                  <button type="reset" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; font-weight: 500;">
                    <i class="fa fa-refresh"></i> Reset
                  </button>
                  <button type="submit" style="display: inline-flex; align-items: center; gap: 5px; background-color: #28a745; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; font-weight: 500;">
                    <i class="fa fa-save"></i> Submit for Approval
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
