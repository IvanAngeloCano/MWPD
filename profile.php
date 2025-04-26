<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'connection.php';

// Ensure the profile_picture column exists in the users table with correct type
try {
    // Check if profile_picture column exists and has the right type
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'profile_picture'");
    $stmt->execute();
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$column) {
        // Add profile_picture column to users table
        $stmt = $pdo->prepare("ALTER TABLE users ADD COLUMN profile_picture LONGTEXT");
        $stmt->execute();
        error_log("Added profile_picture column to users table");
    } else {
        // Check if column type is LONGTEXT
        if (strpos(strtolower($column['Type']), 'text') === false) {
            // Change column type to LONGTEXT
            $stmt = $pdo->prepare("ALTER TABLE users MODIFY COLUMN profile_picture LONGTEXT");
            $stmt->execute();
            error_log("Changed profile_picture column type to LONGTEXT");
        }
    }
} catch (PDOException $e) {
    error_log("Database error checking/modifying profile_picture column: " . $e->getMessage());
}

// Get user data
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Debug user data
error_log("User data retrieved, profile picture exists: " . (!empty($user['profile_picture']) ? 'Yes' : 'No'));
if (!empty($user['profile_picture'])) {
    error_log("Profile picture length: " . strlen($user['profile_picture']));
}

$errorMessage = '';
$successMessage = '';

// Add a fallback test image - this is a simple gray image with a person icon
$defaultImageBase64 = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlMGUwZTAiLz48Y2lyY2xlIGN4PSI1MCIgY3k9IjM1IiByPSIyMCIgZmlsbD0iIzlFOUU5RSIvPjxwYXRoIGQ9Ik0yNSw4NSBDMjUsNjUgNzUsNjUgNzUsODUiIGZpbGw9IiM5RTlFOUUiLz48L3N2Zz4=';

// Add debugging display for database profile picture
echo "<!-- Debug: Checking profile picture data -->";
if (!empty($user['profile_picture'])) {
    echo "<!-- Profile picture exists with length: " . strlen($user['profile_picture']) . " -->";
    echo "<!-- First 100 chars: " . substr($user['profile_picture'], 0, 100) . " -->";
} else {
    echo "<!-- No profile picture in database -->";
}

// Use the default if none found
if (empty($user['profile_picture'])) {
    $user['profile_picture'] = $defaultImageBase64;
}

// Process profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_picture' && isset($_FILES['profile_picture'])) {
    $uploadSuccess = false;

    if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        // Handle upload errors
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
        ];

        $errorCode = $_FILES['profile_picture']['error'];
        $errorMessage = "Upload error: " . ($uploadErrors[$errorCode] ?? "Unknown error");
        error_log("Profile picture upload failed: " . $errorMessage);
    } else {
        try {
            // Get the file and convert to base64
            $imageData = file_get_contents($_FILES['profile_picture']['tmp_name']);
            $imageType = $_FILES['profile_picture']['type'];
            $base64Image = 'data:' . $imageType . ';base64,' . base64_encode($imageData);

            // Log info
            error_log("Image type: " . $imageType . ", Base64 length: " . strlen($base64Image));

            // First, try a separate query to update just the profile picture
            $updateStmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $updateResult = $updateStmt->execute([$base64Image, $userId]);

            if ($updateResult && $updateStmt->rowCount() > 0) {
                // Success!
                $successMessage = "Profile picture updated successfully.";
                error_log("Profile picture updated in database");
                $uploadSuccess = true;

                // Save to session
                $_SESSION['profile_picture'] = $base64Image;
            } else {
                // Query executed but no rows affected
                $errorMessage = "Failed to update profile picture. No records were updated.";
                error_log("Profile picture update query didn't affect any rows");
            }
        } catch (PDOException $e) {
            // Database error
            $errorMessage = "Database error: " . $e->getMessage();
            error_log("Profile picture database error: " . $e->getMessage());
        } catch (Exception $e) {
            // Other error
            $errorMessage = "Error processing profile picture: " . $e->getMessage();
            error_log("Profile picture processing error: " . $e->getMessage());
        }
    }

    // Refresh user data if successful
    if ($uploadSuccess) {
        // Get fresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Debug the retrieved data
        if (!empty($user['profile_picture'])) {
            error_log("Retrieved profile_picture from DB, length: " . strlen($user['profile_picture']));
            error_log("First 100 chars: " . substr($user['profile_picture'], 0, 100));
        } else {
            error_log("Retrieved user has no profile_picture!");
        }
    }
}

// Check for profile picture upload messages from session
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Process form submission for password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Verify current password
    if (empty($currentPassword)) {
        $errorMessage = "Current password is required";
    } elseif (empty($newPassword)) {
        $errorMessage = "New password is required";
    } elseif (strlen($newPassword) < 8) {
        $errorMessage = "New password must be at least 8 characters long";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "New password and confirm password do not match";
    } else {
        // Verify the current password matches the stored password
        if (password_verify($currentPassword, $user['password'])) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $passwordStmt->execute([$hashedPassword, $userId]);

            $successMessage = "Password has been updated successfully";
        } else {
            $errorMessage = "Current password is incorrect";
        }
    }
}

// Process form submission for name change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_name') {
    $fullName = $_POST['full_name'] ?? '';
    $password = $_POST['verify_password'] ?? '';

    if (empty($fullName)) {
        $errorMessage = "Full name cannot be empty";
    } elseif (empty($password)) {
        $errorMessage = "Please enter your password to verify this change";
    } else {
        // Verify password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $storedPassword = $stmt->fetchColumn();

        $passwordCorrect = false;
        if (strpos($storedPassword, '$2y$') === 0) {
            // Bcrypt hashed password
            $passwordCorrect = password_verify($password, $storedPassword);
        } else {
            // Plain text password (fallback)
            $passwordCorrect = ($password === $storedPassword);
        }

        if ($passwordCorrect) {
            // Update name
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$fullName, $userId]);
            $_SESSION['full_name'] = $fullName;
            $successMessage = "Your name has been updated successfully";
        } else {
            $errorMessage = "Incorrect password";
        }
    }
}

// Update profile picture in session
if (!empty($user['profile_picture'])) {
    $_SESSION['profile_picture'] = $user['profile_picture'];
}

$pageTitle = "Profile";
include '_head.php';
?>

<body>
    <div class="layout-wrapper">
        <?php include '_sidebar.php'; ?>

        <div class="content-wrapper">
            <?php include '_header.php';?>
            <main class="main-content main-profile">
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-header-content">
                            <div class="header-title-wrapper">
                                <h2>Account Settings</h2>
                                <h3 class="profile-user-title"><?= htmlspecialchars($user['full_name']) ?></h3>
                            </div>
                            <p class="profile-subtitle">Manage your account information and security settings</p>
                        </div>
                        <div class="profile-actions">
                            <button type="button" class="profile-action-btn" onclick="window.location.reload()">
                                <i class="fa fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($successMessage)): ?>
                        <div class="success-message">
                            <i class="fa fa-check-circle"></i> <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="error-message">
                            <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>

                    <div class="profile-content">
                        <div class="profile-sidebar">
                            <div class="user-info-card">
                                <div class="profile-picture-container">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="<?= $user['profile_picture'] ?>" alt="Profile picture" class="profile-picture">
                                    <?php else: ?>
                                        <div class="profile-picture-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="change-picture-overlay">
                                        <button type="button" id="change-picture-btn" class="change-picture-btn">
                                            <i class="fas fa-camera"></i>
                                            Change
                                        </button>
                                    </div>
                                </div>
                                <h3 class="user-name"><?= htmlspecialchars($user['full_name']) ?></h3>
                                <span class="user-role"><?= htmlspecialchars($user['role']) ?></span>

                                <div class="user-details">
                                    <div class="user-detail-item">
                                        <i class="fa fa-user"></i>
                                        <div>
                                            <span class="detail-label">Username</span>
                                            <span class="detail-value"><?= htmlspecialchars($user['username']) ?></span>
                                        </div>
                                    </div>

                                    <div class="user-detail-item">
                                        <i class="fa fa-shield-alt"></i>
                                        <div>
                                            <span class="detail-label">Account Status</span>
                                            <span class="detail-value status-active">Active</span>
                                        </div>
                                    </div>

                                    <div class="user-detail-item">
                                        <i class="fa fa-calendar-alt"></i>
                                        <div>
                                            <span class="detail-label">Last Login</span>
                                            <span class="detail-value"><?= date('M d, Y') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-form-container">
                            <!-- Personal Information Card -->
                            <div class="form-card">
                                <h3 class="form-card-title">Personal Information</h3>

                                <div class="form-group">
                                    <label for="display_full_name">Full Name</label>
                                    <div class="input-with-icon">
                                        <i class="fa fa-user"></i>
                                        <input type="text" id="display_full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" readonly>
                                        <button type="button" class="input-action-btn" onclick="openNameModal()">
                                            <i class="fa fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </div>

                                <div class="form-divider"></div>

                                <!-- Change Password Section -->
                                <h3 class="form-card-title">Change Password</h3>
                                <form action="profile.php" method="POST" class="profile-form" id="password-form">
                                    <input type="hidden" name="action" value="change_password">

                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <div class="input-with-icon">
                                            <i class="fa fa-lock"></i>
                                            <input type="password" id="current_password" name="current_password" placeholder="Enter your current password">
                                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <div class="input-with-icon">
                                            <i class="fa fa-key"></i>
                                            <input type="password" id="new_password" name="new_password" placeholder="Enter your new password">
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                        <p class="form-help-text">Password must be at least 8 characters long</p>
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <div class="input-with-icon">
                                            <i class="fa fa-key"></i>
                                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password">
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn-save">
                                            <i class="fa fa-save"></i> Update Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Name Change Modal -->
    <div id="nameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Your Name</h3>
                <button class="modal-close" onclick="closeNameModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form action="profile.php" method="POST" id="name-form">
                    <input type="hidden" name="action" value="change_name">

                    <div class="form-group">
                        <label for="full_name">New Full Name</label>
                        <div class="input-with-icon">
                            <i class="fa fa-user"></i>
                            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Enter your new name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="verify_password">Verify with Password</label>
                        <div class="input-with-icon">
                            <i class="fa fa-lock"></i>
                            <input type="password" id="verify_password" name="verify_password" placeholder="Enter your password to confirm">
                            <button type="button" class="password-toggle" onclick="togglePassword('verify_password')">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <p class="form-help-text">For security, please enter your current password to confirm this change</p>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeNameModal()">Cancel</button>
                        <button type="submit" class="btn-save">
                            <i class="fa fa-check"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Profile Picture Modal -->
    <div id="profile-picture-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Profile Picture</h3>
                <button class="modal-close" id="close-picture-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="profile-picture-form" action="profile.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="change_picture">
                    <div class="form-group">
                        <label for="profile_picture">Upload a new picture</label>
                        <div class="file-upload-container">
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                            <div class="file-upload-preview">
                                <img id="picture-preview" src="#" alt="Preview">
                            </div>
                        </div>
                        <p class="form-help-text">Recommended size: 500x500 pixels. Max file size: 2MB.</p>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" id="cancel-picture-upload">Cancel</button>
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Picture</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .file-upload-container {
            margin-bottom: 15px;
        }

        .file-upload-preview {
            margin-top: 15px;
            text-align: center;
        }

        #picture-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 5px;
            display: none;
            margin: 0 auto;
        }
    </style>

    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const passwordToggle = passwordInput.nextElementSibling;
            const icon = passwordToggle.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Name change modal
        function openNameModal() {
            document.getElementById('nameModal').style.display = 'flex';
            // Prevent body scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }

        function closeNameModal() {
            document.getElementById('nameModal').style.display = 'none';
            // Re-enable body scrolling
            document.body.style.overflow = '';
        }

        // Function to close picture modal
        function closePictureModal() {
            const profilePictureModal = document.getElementById('profile-picture-modal');
            const profilePictureInput = document.getElementById('profile_picture');
            const picturePreview = document.getElementById('picture-preview');

            profilePictureModal.style.display = 'none';
            profilePictureInput.value = '';
            picturePreview.style.display = 'none';

            // Re-enable body scrolling
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const nameModal = document.getElementById('nameModal');
            const pictureModal = document.getElementById('profile-picture-modal');

            if (event.target === nameModal) {
                closeNameModal();
            }

            if (event.target === pictureModal) {
                closePictureModal();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Profile picture functionality
            const changePictureBtn = document.getElementById('change-picture-btn');
            const profilePictureModal = document.getElementById('profile-picture-modal');
            const closeModalBtn = document.getElementById('close-picture-modal');
            const cancelPictureUpload = document.getElementById('cancel-picture-upload');
            const profilePictureInput = document.getElementById('profile_picture');
            const picturePreview = document.getElementById('picture-preview');

            changePictureBtn.addEventListener('click', function() {
                profilePictureModal.style.display = 'flex';
                // Prevent body scrolling when modal is open
                document.body.style.overflow = 'hidden';
            });

            closeModalBtn.addEventListener('click', function() {
                profilePictureModal.style.display = 'none';
                profilePictureInput.value = '';
                picturePreview.style.display = 'none';
                document.body.style.overflow = '';
            });

            cancelPictureUpload.addEventListener('click', function() {
                profilePictureModal.style.display = 'none';
                profilePictureInput.value = '';
                picturePreview.style.display = 'none';
                document.body.style.overflow = '';
            });

            profilePictureInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        picturePreview.src = e.target.result;
                        picturePreview.style.display = 'block';
                    }

                    reader.readAsDataURL(this.files[0]);
                }
            });

            // Make sure modals are closed when page loads
            profilePictureModal.style.display = 'none';
            document.getElementById('nameModal').style.display = 'none';
            document.body.style.overflow = '';

            // Fix for iOS to ensure the file input opens the file picker
            if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
                const fileInputs = document.querySelectorAll('input[type="file"]');
                fileInputs.forEach(input => {
                    input.setAttribute('capture', 'camera');
                });
            }
        });
    </script>

</body>

</html>