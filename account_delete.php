<?php
include 'session.php';
require_once 'connection.php';

// Check if user has Division Head role
if ($_SESSION['role'] !== 'div head' && $_SESSION['role'] !== 'Division Head') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "No user specified for deletion.";
    header('Location: accounts.php');
    exit();
}

$user_id = $_GET['id'];

// Don't allow deletion of own account
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot delete your own account.";
    header('Location: accounts.php');
    exit();
}

// Get user data for confirmation
try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
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

// Process deletion if confirmed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->execute([$user_id]);
        
        $_SESSION['success_message'] = "User deleted successfully.";
        header('Location: accounts.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        header('Location: accounts.php');
        exit();
    }
}

$pageTitle = "Delete User - MWPD Filing System";
include '_head.php';
?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="account-management-wrapper">
          <div class="page-header">
            <h2>Delete User</h2>
          </div>

          <div class="card">
            <div class="card-body">
              <div class="alert alert-danger">
                <h3>Confirm Deletion</h3>
                <p>Are you sure you want to delete user <strong><?= htmlspecialchars($user['username']) ?></strong>?</p>
                <p>This action cannot be undone.</p>
              </div>
              
              <form method="POST" action="">
                <div class="form-actions">
                  <a href="accounts.php" class="btn btn-primary">
                    <i class="fa fa-arrow-left"></i> Cancel
                  </a>
                  <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Delete User
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
