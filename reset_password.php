<?php
include 'session.php';
require_once 'connection.php';
include_once 'notifications.php';
// include_once 'email_notifications.php'; // Old email system with circular references
include_once 'unified_email_system.php'; // New unified email system with fixed Gmail SMTP
include_once 'alert_system.php';  // Include the session-based alert system
$pageTitle = "Reset User Password";
include '_head.php';

// Check if user has admin or Division Head role
if ($_SESSION['role'] !== 'div head' && $_SESSION['role'] !== 'Division Head' && 
    $_SESSION['role'] !== 'Regional Director' && $_SESSION['role'] !== 'regional director') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to access this page.";
    header('Location: dashboard.php');
    exit();
}

// Process password reset
$success_message = '';
$error_message = '';

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process reset
    $reset_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    if ($reset_user_id <= 0) {
        $error_message = "Invalid user ID.";
    } else {
        try {
            // Generate new password
            $new_password = generateSecurePassword();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Get user info - Get ALL fields to check for potential email column name variations
            $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $user_stmt->execute([$reset_user_id]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Detailed debugging
            $debug_log = "\n" . date('Y-m-d H:i:s') . " - DEBUG: Reset password for user ID {$reset_user_id}\n";
            $debug_log .= "Complete user data from DB: " . print_r($user, true) . "\n";
            
            // Check for variations of email field names
            $possible_email_fields = ['email', 'user_email', 'mail', 'email_address'];
            $debug_log .= "Checking for email fields: " . implode(", ", $possible_email_fields) . "\n";
            
            // Look for any variation of an email field
            $found_email = '';
            foreach ($possible_email_fields as $field) {
                if (isset($user[$field]) && !empty($user[$field])) {
                    $found_email = $user[$field];
                    $debug_log .= "Found email in field '{$field}': {$found_email}\n";
                    break;
                }
            }
            
            // If still no email found, check for any field that might contain an email
            if (empty($found_email)) {
                foreach ($user as $field => $value) {
                    if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $found_email = $value;
                        $debug_log .= "Found email-like value in field '{$field}': {$found_email}\n";
                        break;
                    }
                }
            }
            
            // Add the found email to the user array
            if (!empty($found_email)) {
                $user['email'] = $found_email;
                $debug_log .= "Using email: {$found_email}\n";
            } else {
                $debug_log .= "No valid email field found for this user!\n";
            }
            
            // Save debug log
            file_put_contents('email_debug.txt', $debug_log, FILE_APPEND);
            
            // Initialize default values to prevent undefined array key errors
            if ($user) {
                $user = array_merge([
                    'username' => '',
                    'full_name' => '',
                    'email' => ''
                ], $user);
            }
            
            if (!$user) {
                $error_message = "User not found.";
            } else {
                // Update the password in the database
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $reset_user_id]);
                
                // Send email notification if email is available
                $email_sent = false;
                if (!empty($user['email'])) {
                    // Use our unified email system directly for the most reliable delivery
                    $email_sent = unified_send_password_reset(
                        $user['email'],
                        $user['full_name'],
                        $user['username'],
                        $new_password
                    );
                    
                    // Log this reset action for debugging
                    error_log(date('Y-m-d H:i:s') . " - Password reset for {$user['username']} (ID: {$reset_user_id}), email sent to {$user['email']}, result: " . ($email_sent ? 'SUCCESS' : 'FAILED'));
                }
                
                // Add in-app notification for the user
                addNotification(
                    $reset_user_id, 
                    "Your password has been reset by an administrator. " . 
                    ($email_sent ? "Check your email for the new password." : "Please contact your administrator for the new password."),
                    null,
                    'password_reset',
                    'change_password.php'
                );
                
                // Add session-based alert for immediate feedback
                addAlert(
                    'success',
                    "Password reset successful for " . htmlspecialchars(isset($user['full_name']) ? $user['full_name'] : 'user') . 
                    ($email_sent ? ". The new password has been sent to their email." : ".")
                );
                
                // Set success message
                $_SESSION['success_message'] = "Password reset successful for " . htmlspecialchars(isset($user['full_name']) ? $user['full_name'] : 'user') . 
                                            ($email_sent ? ". The new password has been sent to their email." : ".");
                
                // Record the reset action in the system log
                $log_message = "Password reset for user ID {$reset_user_id} (" . (isset($user['username']) ? $user['username'] : 'unknown') . ") by user ID {$_SESSION['user_id']}";
                file_put_contents('system_log.txt', date('Y-m-d H:i:s') . ": {$log_message}\n", FILE_APPEND);
                
                // Redirect back to accounts page
                header('Location: accounts.php');
                exit();
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
} else {
    // Make sure we have a valid user ID for the form
    if ($user_id <= 0) {
        $_SESSION['error_message'] = "Invalid user ID.";
        header('Location: accounts.php');
        exit();
    }
    
    // Get user info to display
    try {
        $stmt = $pdo->prepare("SELECT username, full_name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $_SESSION['error_message'] = "User not found.";
            header('Location: accounts.php');
            exit();
        }
        
        // Initialize default values to prevent undefined array key errors
        $user = array_merge([
            'username' => '',
            'full_name' => '',
            'email' => ''
        ], $user);
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        header('Location: accounts.php');
        exit();
    }
}

// Function to generate a secure random password
function generateSecurePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    $char_length = strlen($chars) - 1;
    
    // Ensure at least one uppercase, one lowercase, one number and one special char
    $password .= $chars[rand(26, 51)]; // Uppercase
    $password .= $chars[rand(0, 25)];  // Lowercase
    $password .= $chars[rand(52, 61)]; // Number
    $password .= $chars[rand(62, $char_length)]; // Special char
    
    // Fill the rest randomly
    for ($i = 0; $i < $length - 4; $i++) {
        $password .= $chars[rand(0, $char_length)];
    }
    
    // Shuffle the password to avoid predictable pattern
    $password = str_shuffle($password);
    
    return $password;
}
?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="password-reset-wrapper">
          <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="margin: 0; font-size: 24px; color: #333;">Reset Password for <?= htmlspecialchars(isset($user['full_name']) ? $user['full_name'] : '') ?></h1>
            <a href="accounts.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-weight: 500;">
              <i class="fa fa-arrow-left"></i> Back to Users
            </a>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #28a745;">
              <?= htmlspecialchars($success_message) ?>
            </div>
          <?php endif; ?>

          <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <div class="card-body" style="padding: 20px;">
              <div class="alert alert-warning" style="background-color: #fff3cd; color: #856404; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <p><strong>Warning:</strong> This action will reset the user's password. The new password will be randomly generated.</p>
                <?php if (!empty($user['email'])): ?>
                <p>An email with the new password will be sent to: <strong><?= htmlspecialchars($user['email']) ?></strong></p>
                <?php else: ?>
                <p><strong>Note:</strong> This user does not have an email address in the system. You will need to provide the new password to them manually.</p>
                <?php endif; ?>
              </div>
              
              <form method="POST" action="">
                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                
                <div class="user-info" style="margin-bottom: 20px;">
                  <div style="display: grid; grid-template-columns: 120px 1fr; margin-bottom: 10px;">
                    <span style="font-weight: 500;">Username:</span>
                    <span><?= htmlspecialchars(isset($user['username']) ? $user['username'] : '') ?></span>
                  </div>
                  <div style="display: grid; grid-template-columns: 120px 1fr; margin-bottom: 10px;">
                    <span style="font-weight: 500;">Full Name:</span>
                    <span><?= htmlspecialchars(isset($user['full_name']) ? $user['full_name'] : '') ?></span>
                  </div>
                  <div style="display: grid; grid-template-columns: 120px 1fr; margin-bottom: 10px;">
                    <span style="font-weight: 500;">Email:</span>
                    <span><?= htmlspecialchars(isset($user['email']) && !empty($user['email']) ? $user['email'] : 'No email address') ?></span>
                  </div>
                </div>
                
                <div class="form-actions" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                  <a href="accounts.php" style="display: inline-flex; align-items: center; gap: 5px; background-color: #6c757d; color: white; border: none; border-radius: 4px; padding: 10px 15px; text-decoration: none; font-weight: 500; cursor: pointer;">
                    <i class="fa fa-times"></i> Cancel
                  </a>
                  <button type="submit" style="display: inline-flex; align-items: center; gap: 5px; background-color: #dc3545; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; font-weight: 500;">
                    <i class="fa fa-key"></i> Reset Password
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
