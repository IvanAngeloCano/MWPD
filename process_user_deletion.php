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

// Check if user ID is provided in POST
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    $_SESSION['error_message'] = "No user specified for deletion.";
    header('Location: accounts.php');
    exit();
}

$user_id = $_POST['user_id'];

// Don't allow deletion of own account
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "You cannot delete your own account.";
    header('Location: accounts.php');
    exit();
}

// Check if user exists
try {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header('Location: accounts.php');
        exit();
    }
    
    // Process the deletion
    $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $delete_stmt->execute([$user_id]);
    
    // Set success message
    $_SESSION['success_message'] = "User deleted successfully.";
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

// Redirect back to accounts page
header('Location: accounts.php');
exit();
?>
