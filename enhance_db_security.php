<?php
// Database Security Enhancement Script
require_once 'connection.php';

// Set up the page
header('Content-Type: text/html; charset=utf-8');
echo "<html><head><title>Database Security Enhancement</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    h1, h2 { color: #333; }
    .success { color: green; background: #f0fff0; padding: 10px; border-left: 4px solid green; margin: 10px 0; }
    .error { color: #721c24; background: #f8d7da; padding: 10px; border-left: 4px solid #721c24; margin: 10px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-left: 4px solid #0c5460; margin: 10px 0; }
    code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
</style>
</head><body>
<div class="container">

<h1>Database Security Enhancement</h1>

<?php
try {
    // 1. Enhance Password Security
    echo "<h2>1. Enhancing Password Security</h2>";
    
    // Check if users table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
    
    if ($tableCheck == 0) {
        echo "<div class='error'>Users table not found. Please run the basic setup first.</div>";
    } else {
        // Get all users with plaintext passwords (not starting with $2y$)
        $stmt = $pdo->query("SELECT id, username, password FROM users WHERE password NOT LIKE '$2y$%'");
        $plainTextUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = count($plainTextUsers);
        if ($count > 0) {
            echo "<div class='info'>Found {$count} users with plaintext passwords. Converting to secure hashes...</div>";
            
            // Use a higher cost factor (12) for better security
            $options = ['cost' => 12];
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            foreach ($plainTextUsers as $user) {
                $secureHash = password_hash($user['password'], PASSWORD_BCRYPT, $options);
                $updateStmt->execute([$secureHash, $user['id']]);
                echo "<div class='success'>Enhanced security for user: {$user['username']}</div>";
            }
        } else {
            echo "<div class='info'>No plaintext passwords found. Checking for outdated hashes...</div>";
            
            // Get all users with older hashing algorithms (lower cost factor)
            $stmt = $pdo->query("SELECT id, username, password FROM users WHERE password LIKE '$2y$10$%'");
            $outdatedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($outdatedUsers) > 0) {
                echo "<div class='info'>Found " . count($outdatedUsers) . " users with standard hashing. Upgrading to enhanced security...</div>";
                
                // Rehash with higher cost factor
                $options = ['cost' => 12];
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                foreach ($outdatedUsers as $user) {
                    // We need the original password to rehash, but we don't have it
                    // Instead, we'll mark these for password reset on next login
                    $updateStmt->execute(['RESET_REQUIRED', $user['id']]);
                    echo "<div class='info'>Marked user {$user['username']} for password reset</div>";
                }
                
                echo "<div class='info'>Users with RESET_REQUIRED will need to reset their password on next login.</div>";
                echo "<div class='info'>You will need to modify your login.php to handle this.</div>";
            } else {
                echo "<div class='success'>All passwords are already using secure hashing.</div>";
            }
        }
    }
    
    // 2. Create a security settings table
    echo "<h2>2. Creating Security Settings</h2>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_name VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    echo "<div class='success'>Security settings table created or verified.</div>";
    
    // Insert default security settings
    $settings = [
        ['password_min_length', '8', 'Minimum password length'],
        ['password_require_special', '1', 'Require special characters in passwords'],
        ['password_require_number', '1', 'Require numbers in passwords'],
        ['password_require_uppercase', '1', 'Require uppercase letters in passwords'],
        ['max_login_attempts', '5', 'Maximum failed login attempts before temporary lockout'],
        ['lockout_time_minutes', '30', 'Time in minutes for account lockout after failed attempts'],
        ['session_timeout_minutes', '30', 'Session timeout in minutes'],
        ['hash_cost_factor', '12', 'Bcrypt cost factor for password hashing']
    ];
    
    $settingStmt = $pdo->prepare("INSERT INTO security_settings (setting_name, setting_value, description) 
                                VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    foreach ($settings as $setting) {
        $settingStmt->execute($setting);
        echo "<div class='success'>Set security setting: {$setting[0]} = {$setting[1]}</div>";
    }
    
    // 3. Create a login_attempts table to track failed logins
    echo "<h2>3. Setting Up Login Attempt Tracking</h2>";
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        success BOOLEAN DEFAULT FALSE,
        INDEX (username),
        INDEX (ip_address),
        INDEX (attempt_time)
    )");
    
    echo "<div class='success'>Login attempts tracking table created.</div>";
    
    // 4. Create a security helper file
    echo "<h2>4. Creating Security Helper File</h2>";
    
    $securityHelperContent = <<<'EOT'
<?php
// security_helper.php - Security functions for MWPD system

/**
 * Log a login attempt
 * @param string $username The username attempted
 * @param bool $success Whether the login was successful
 * @return void
 */
function log_login_attempt($username, $success = false) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
    $stmt->execute([$username, $ip, $success ? 1 : 0]);
}

/**
 * Check if an account or IP is currently locked out due to too many failed attempts
 * @param string $username The username to check
 * @return bool|int False if not locked, or seconds remaining until unlock
 */
function check_lockout($username) {
    global $pdo;
    
    // Get security settings
    $stmt = $pdo->prepare("SELECT setting_value FROM security_settings WHERE setting_name IN ('max_login_attempts', 'lockout_time_minutes')");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $max_attempts = isset($settings['max_login_attempts']) ? (int)$settings['max_login_attempts'] : 5;
    $lockout_minutes = isset($settings['lockout_time_minutes']) ? (int)$settings['lockout_time_minutes'] : 30;
    
    // Check recent failed attempts
    $ip = $_SERVER['REMOTE_ADDR'];
    $lockout_time = date('Y-m-d H:i:s', strtotime("-{$lockout_minutes} minutes"));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts 
                          WHERE (username = ? OR ip_address = ?) 
                          AND success = 0 
                          AND attempt_time > ?");
    $stmt->execute([$username, $ip, $lockout_time]);
    $attempt_count = $stmt->fetchColumn();
    
    if ($attempt_count >= $max_attempts) {
        // Get time of most recent attempt
        $stmt = $pdo->prepare("SELECT attempt_time FROM login_attempts 
                              WHERE (username = ? OR ip_address = ?) 
                              AND success = 0 
                              ORDER BY attempt_time DESC LIMIT 1");
        $stmt->execute([$username, $ip]);
        $last_attempt = $stmt->fetchColumn();
        
        // Calculate time remaining in lockout
        $lockout_until = strtotime($last_attempt) + ($lockout_minutes * 60);
        $time_remaining = $lockout_until - time();
        
        if ($time_remaining > 0) {
            return $time_remaining;
        }
    }
    
    return false;
}

/**
 * Generate a secure random token
 * @param int $length Length of the token
 * @return string The generated token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate password strength against security settings
 * @param string $password The password to validate
 * @return array Array of validation errors, empty if password is valid
 */
function validate_password_strength($password) {
    global $pdo;
    
    // Get security settings
    $stmt = $pdo->prepare("SELECT setting_name, setting_value FROM security_settings 
                          WHERE setting_name LIKE 'password_%'");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $min_length = isset($settings['password_min_length']) ? (int)$settings['password_min_length'] : 8;
    $require_special = isset($settings['password_require_special']) ? (bool)$settings['password_require_special'] : true;
    $require_number = isset($settings['password_require_number']) ? (bool)$settings['password_require_number'] : true;
    $require_uppercase = isset($settings['password_require_uppercase']) ? (bool)$settings['password_require_uppercase'] : true;
    
    $errors = [];
    
    if (strlen($password) < $min_length) {
        $errors[] = "Password must be at least {$min_length} characters long";
    }
    
    if ($require_special && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Password must include at least one special character";
    }
    
    if ($require_number && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must include at least one number";
    }
    
    if ($require_uppercase && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter";
    }
    
    return $errors;
}

/**
 * Hash a password using the current security settings
 * @param string $password The password to hash
 * @return string The hashed password
 */
function hash_password($password) {
    global $pdo;
    
    // Get the cost factor from security settings
    $stmt = $pdo->prepare("SELECT setting_value FROM security_settings WHERE setting_name = 'hash_cost_factor'");
    $stmt->execute();
    $cost = (int)$stmt->fetchColumn();
    
    // Ensure cost is between 10 and 20 (higher is more secure but slower)
    $cost = max(10, min(20, $cost));
    
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
}

/**
 * Regenerate session ID and update session timeout
 * @return void
 */
function regenerate_session() {
    // Regenerate session ID to prevent session fixation
    if (!isset($_SESSION['last_regenerated']) || 
        time() - $_SESSION['last_regenerated'] > 300) { // Every 5 minutes
        
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = time();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Check if the session has timed out
 * @return bool True if session has timed out
 */
function check_session_timeout() {
    global $pdo;
    
    // Get timeout setting
    $stmt = $pdo->prepare("SELECT setting_value FROM security_settings WHERE setting_name = 'session_timeout_minutes'");
    $stmt->execute();
    $timeout_minutes = (int)$stmt->fetchColumn();
    
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > $timeout_minutes * 60) {
        return true;
    }
    
    return false;
}
EOT;
    
    // Write the security helper file
    $securityHelperPath = __DIR__ . '/security_helper.php';
    if (file_put_contents($securityHelperPath, $securityHelperContent)) {
        echo "<div class='success'>Created security_helper.php with enhanced security functions.</div>";
    } else {
        echo "<div class='error'>Failed to create security_helper.php. Please check file permissions.</div>";
    }
    
    // 5. Create a login.php update guide
    echo "<h2>5. Login.php Update Guide</h2>";
    echo "<div class='info'>To fully implement the enhanced security, you need to update your login.php file. Here's how:</div>";
    
    echo "<pre style='background:#f5f5f5;padding:15px;border-radius:5px;overflow:auto;'>
// At the top of login.php, after session_start() and require_once 'connection.php':
require_once 'security_helper.php';

// Inside your login processing code, before checking credentials:
if ($lockout_time = check_lockout($username)) {
    $minutes = ceil($lockout_time / 60);
    $login_error = "Too many failed login attempts. Please try again in {$minutes} minutes.";
} else {
    // Existing login code here...
    
    // After validating credentials, log the attempt:
    log_login_attempt($username, $is_valid);
    
    if ($is_valid) {
        // If login successful, regenerate session
        regenerate_session();
        
        // Check if password reset is required
        if ($user['password'] === 'RESET_REQUIRED') {
            $_SESSION['reset_required'] = true;
            header('Location: reset_password.php');
            exit();
        }
        
        // Rest of your successful login code...
    }
}

// In your session.php or any file that checks for logged-in users:
if (check_session_timeout()) {
    // Session has expired
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
} else {
    // Update activity timestamp
    regenerate_session();
}
</pre>";
    
    echo "<div class='success'><strong>Security Enhancement Complete!</strong></div>";
    echo "<p>Your database now has enhanced security features:</p>";
    echo "<ul>";
    echo "<li>Stronger password hashing with configurable cost factor</li>";
    echo "<li>Failed login attempt tracking and temporary account lockouts</li>";
    echo "<li>Configurable security settings stored in the database</li>";
    echo "<li>Session security improvements with timeout and regeneration</li>";
    echo "<li>Password strength validation</li>";
    echo "</ul>";
    
    echo "<p><a href='login.php'>Return to Login Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

</div>
</body>
</html>
