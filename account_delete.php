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
    
    // Instead of showing the delete confirmation page,
    // store the data in session and redirect to accounts page with a flag to show modal
    $_SESSION['delete_user_id'] = $user_id;
    $_SESSION['delete_username'] = $user['username'];
    header('Location: accounts.php?show_delete_modal=1');
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error fetching user data: " . $e->getMessage();
    header('Location: accounts.php');
    exit();
}

// We've already redirected to the accounts page with modal,
// so none of the following code should run
?>

