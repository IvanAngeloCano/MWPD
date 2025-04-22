<?php
session_start();
require_once 'connection.php';
$pageTitle = "Login - MWPD Filing System";
$login_error = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $login_error = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, password, full_name, role FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $login_error = 'Invalid username or password.';
            } else {
                // Check if password is a hash (starts with $2y$) or plain text
                $stored_password = $user['password'];
                $is_valid = false;
                
                // Option 1: Password is stored as a hash
                if (strpos($stored_password, '$2y$') === 0) {
                    $is_valid = password_verify($password, $stored_password);
                } 
                // Option 2: Password is stored as plain text (temporary fallback)
                else {
                    $is_valid = ($password === $stored_password);
                    
                    // Upgrade to hashed password if match is found
                    if ($is_valid) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $update->execute([$hash, $user['id']]);
                    }
                }
                
                if ($is_valid) {
                    // Store user data in session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    
                    // Make sure we're getting the actual values from the database
                    // If full_name is null or empty, use the actual value from database
                    $_SESSION['full_name'] = !empty($user['full_name']) ? $user['full_name'] : $username;
                    $_SESSION['role'] = !empty($user['role']) ? $user['role'] : 'User';
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $login_error = 'Invalid username or password.';
                }
            }
        } catch (PDOException $e) {
            $login_error = 'Database error. Please try again later.';
        }
    }
}
include '_head.php';
?>

<body>
  <div class="main-grid">
    <div class="upper-half-bg"></div>
    <div class="lower-half-bg"></div>

    <div class="login-wrapper">
      <div class="login-box">
        <img src="assets\images\DMW Logo.png" alt="DMW Logo" class="dmw-logo">
        <h2>Login to system</h2>
       
        <form method="POST" action="login.php">
          <div class="username-box">
            <label>Enter your Username</label>
            <div class="input-group">
              <i class="fa fa-user"></i>
              <input type="text" name="username" placeholder="Username" required>
            </div>
          </div>


          <div class="password-box">
            <label>Enter your Password</label>
            <div class="input-group">
              <i class="fa fa-lock"></i>
              <input type="password" name="password" placeholder="Password" id="password" required>
              <i class="fa fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>
              <!-- Error message container with fixed height -->
          <div class="error-container">
            <?php if ($login_error): ?>
              <div class="login-error">
                <?= htmlspecialchars($login_error) ?>
              </div>
            <?php endif; ?>
          </div>
          </div>
          
        
         
          <button type="submit" class="login-button text-md">Login</button>
        </form>
      </div>
    </div>

    <div class="info-upper-half">
      <p class="text-md font-medium">Republic of the Philippines</p>
      <p class="text-lg font-semibold">DEPARTMENT OF MIGRANT WORKERS</p>
      <p class="text-md font-medium italic">Kagawaran ng Manggagawang Pandarayuhan</p>
    </div>

    <div class="info-lower-half">
      <p class="text-lg font-semibold text-primary">Migrant Workers Processing Division (MWPD) Filing System</p>
      <p class="text-md text-black">For MWPD staff to efficiently manage, track, and archive migrant worker application records.</p>
    </div>
  </div>


  <img src="assets\images\bagong-pilipinas-logo.png" class="bagong-logo" alt="Bagong Pilipinas">

  <script>
    function togglePassword() {
      var passwordField = document.getElementById("password");
      if (passwordField.type === "password") {
        passwordField.type = "text";
      } else {
        passwordField.type = "password";
      }
    }
  </script>
  

</body>

</html>