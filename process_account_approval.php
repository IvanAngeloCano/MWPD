<?php
include 'session.php';
require_once 'connection.php';
include_once 'notifications.php';
include_once 'email_notifications.php'; // Include email notifications system
include_once 'account_approval_email_log.php'; // Include our fallback logging system

// Check if user has Regional Director role
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    // Redirect to dashboard with error message
    $_SESSION['error_message'] = "You don't have permission to perform this action.";
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $approval_id = $_POST['approval_id'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($action) || empty($approval_id)) {
        $_SESSION['error_message'] = "Missing required parameters.";
        header('Location: account_approvals.php');
        exit();
    }
    
    try {
        // Get the approval details
        $stmt = $pdo->prepare("SELECT * FROM account_approvals WHERE id = ? AND status = 'pending'");
        $stmt->execute([$approval_id]);
        $approval = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$approval) {
            $_SESSION['error_message'] = "Invalid approval request or already processed.";
            header('Location: account_approvals.php');
            exit();
        }
        
        // Get the submitter's email for notification
        $submitter_stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $submitter_stmt->execute([$approval['submitted_by']]);
        $submitter = $submitter_stmt->fetch(PDO::FETCH_ASSOC);
        $submitter_email = $submitter ? $submitter['email'] : null;
        $submitter_name = $submitter ? $submitter['full_name'] : null;
        
        // Process based on action
        if ($action === 'approve') {
            // Get the user's email from the approval record
            $user_email = $approval['email'];
            
            // Generate a readable password for the user
            $generated_password = generateSecurePassword();
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
            
            // Check if required columns exist in users table
            $status_column_exists = false;
            $email_column_exists = false;
            $created_at_column_exists = false;
            
            try {
                // Check status column
                $check_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
                $status_column_exists = $check_stmt->rowCount() > 0;
                
                // Check email column
                $check_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
                $email_column_exists = $check_stmt->rowCount() > 0;
                
                // Check created_at column
                $check_stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
                $created_at_column_exists = $check_stmt->rowCount() > 0;
                
                // If created_at column doesn't exist, try to add it
                if (!$created_at_column_exists) {
                    try {
                        $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
                        $created_at_column_exists = true;
                    } catch (PDOException $e) {
                        // If we can't add it, we'll handle this in the queries below
                    }
                }
            } catch (PDOException $e) {
                // If there's an error, assume the columns don't exist
                $status_column_exists = false;
                $email_column_exists = false;
                $created_at_column_exists = false;
            }
            
            // Insert the approved user into the users table with the newly generated password
            if ($status_column_exists && $email_column_exists && $created_at_column_exists) {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status, email, created_at) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role'], $user_email]);
            } else if ($status_column_exists && $email_column_exists) {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status, email) VALUES (?, ?, ?, ?, 'active', ?)");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role'], $user_email]);
            } else if ($status_column_exists && $created_at_column_exists) {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role']]);
            } else if ($email_column_exists && $created_at_column_exists) {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role'], $user_email]);
            } else if ($status_column_exists) {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, status) VALUES (?, ?, ?, ?, 'active')");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role']]);
            } else if ($email_column_exists) {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, email) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role'], $user_email]);
            } else if ($created_at_column_exists) {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role']]);
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $insert_stmt->execute([$approval['username'], $hashed_password, $approval['full_name'], $approval['role']]);
            }
            
            // Update the approval record
            $update_stmt = $pdo->prepare("UPDATE account_approvals SET status = 'approved', approved_by = ?, approved_date = NOW(), notes = ? WHERE id = ?");
            $update_stmt->execute([$_SESSION['user_id'], $notes, $approval_id]);
            
            try {
                // First, create database notification (this works reliably)
                notifyAccountDecision($approval['submitted_by'], $approval['username'], 'approved', $notes);
                
                // Send email to the approved user
                if (!empty($user_email)) {
                    $email_sent = sendAccountApprovalEmail(
                        $user_email,
                        $approval['full_name'],
                        $approval['username'],
                        $generated_password
                    );
                    
                    // Log result to debug log
                    error_log(date('Y-m-d H:i:s') . " - Approval email to {$user_email}: " . ($email_sent ? 'SUCCESS' : 'FAILED'));
                }
                
                // Send email notification to the submitter
                if ($submitter_email) {
                    $submitter_email_sent = sendSubmitterNotificationEmail(
                        $submitter_email,
                        $submitter_name,
                        $approval['username'],
                        $approval['full_name'],
                        'approved',
                        $notes
                    );
                    
                    // Log result to debug log
                    error_log(date('Y-m-d H:i:s') . " - Submitter email to {$submitter_email}: " . 
                        ($submitter_email_sent ? 'SUCCESS' : 'FAILED'));
                }
                
                // Build success message with email status
                $email_status = '';
                if (!empty($user_email) && isset($email_sent)) {
                    $email_status = $email_sent ? 
                        " Email notification sent successfully." : 
                        " Email delivery attempted but may have failed.";
                }
                
                // Add success message with link to view approval logs
                $_SESSION['success_message'] = "User account approved successfully." . $email_status .
                    " <a href='view_approval_logs.php' style='color:#007bff;'>View approval logs</a> for details.";
                
            } catch (Exception $e) {
                // Log the error but continue
                error_log('Error in notification system: ' . $e->getMessage());
            }
            
            // Check if we should skip the redirect
            $no_redirect = isset($_POST['no_redirect']) && $_POST['no_redirect'] == '1';
        } else if ($action === 'reject') {
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            
            // First, check if rejection_reason column exists and add it if needed
            try {
                $column_check = $pdo->query("SHOW COLUMNS FROM account_approvals LIKE 'rejection_reason'");
                $column_exists = $column_check->rowCount() > 0;
                
                if (!$column_exists) {
                    // Add the column if it doesn't exist
                    $pdo->exec("ALTER TABLE account_approvals ADD COLUMN rejection_reason TEXT NULL AFTER notes");
                    error_log("Added missing rejection_reason column to account_approvals table");
                }
            } catch (PDOException $column_e) {
                error_log("Error checking/adding rejection_reason column: " . $column_e->getMessage());
            }
            
            // Update the approval status (using notes as fallback if rejection_reason column doesn't exist)
            try {
                $update_stmt = $pdo->prepare("UPDATE account_approvals SET status = 'rejected', approved_by = ?, approved_date = NOW(), rejection_reason = ? WHERE id = ?");
                $update_stmt->execute([$_SESSION['user_id'], $rejection_reason, $approval_id]);
            } catch (PDOException $update_e) {
                // If update fails because column doesn't exist, try updating without the rejection_reason
                if (strpos($update_e->getMessage(), "rejection_reason") !== false) {
                    error_log("Falling back to notes field for rejection reason");
                    $update_stmt = $pdo->prepare("UPDATE account_approvals SET status = 'rejected', approved_by = ?, approved_date = NOW(), notes = ? WHERE id = ?");
                    $update_stmt->execute([$_SESSION['user_id'], $rejection_reason, $approval_id]);
                } else {
                    // If it's some other error, rethrow it
                    throw $update_e;
                }
            }
            
            // Send email and notifications
            try {
                // Add extra error checking for the notification process
                error_log("Starting notification process for denial, submitter ID: {$approval['submitted_by']}");
                
                // Safely notify the submitter via in-app notification
                try {
                    notifyAccountDecision($approval['submitted_by'], $approval['username'], 'rejected', $rejection_reason);
                    error_log("In-app notification sent successfully");
                } catch (Exception $notifyError) {
                    error_log("Error in notifyAccountDecision: " . $notifyError->getMessage());
                    // Continue processing, don't let notification failure stop the process
                }
                
                // Send email notification to the rejected user
                $user_email_sent = false;
                if (!empty($approval['email'])) {
                    // Create and send a rejection email
                    $user_email_sent = sendAccountRejectionEmail(
                        $approval['email'],
                        $approval['full_name'],
                        $approval['username'],
                        $rejection_reason
                    );
                    
                    // Log result to debug log
                    error_log(date('Y-m-d H:i:s') . " - Rejection email to {$approval['email']}: " . 
                        ($user_email_sent ? 'SUCCESS' : 'FAILED'));
                }
                
                // Also notify the submitter via email if available
                $submitter_email_sent = false;
                if ($submitter_email) {
                    $submitter_email_sent = sendSubmitterNotificationEmail(
                        $submitter_email,
                        $submitter_name,
                        $approval['username'],
                        $approval['full_name'],
                        'rejected',
                        $rejection_reason
                    );
                    
                    // Log result to debug log
                    error_log(date('Y-m-d H:i:s') . " - Submitter rejection email to {$submitter_email}: " . 
                        ($submitter_email_sent ? 'SUCCESS' : 'FAILED'));
                }
                
                // Build success message with email status
                $email_status = '';
                if (!empty($approval['email']) && $user_email_sent) {
                    $email_status = " Email notification sent to the applicant.";
                }
                if ($submitter_email && $submitter_email_sent) {
                    $email_status .= " Submitter was notified by email.";
                }
                
                $_SESSION['success_message'] = "User account request rejected." . $email_status;
            } catch (Exception $e) {
                // Log the error but continue
                error_log('Error in rejection notification: ' . $e->getMessage());
                $_SESSION['success_message'] = "User account request rejected, but there was an error with the notification system.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid action.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
    
    // Prepare the response message
    $response_message = '';
    if (isset($_SESSION['success_message'])) {
        $response_message = $_SESSION['success_message'];
        // Clear the message from session to avoid showing it twice
        unset($_SESSION['success_message']);
    } elseif (isset($_SESSION['error_message'])) {
        $response_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    } else {
        $response_message = 'Action completed successfully';
    }
    
    // Check if this is an AJAX request
    $no_redirect = isset($_POST['no_redirect']) && $_POST['no_redirect'] == '1';
    
    if ($no_redirect) {
        // Return a JSON response for AJAX requests
        header('Content-Type: application/json');
        // Log what we're sending back (for debugging)
        error_log("Sending AJAX response: success=true, message=" . $response_message);
        
        // Make sure to properly handle any potential JSON encoding errors
        $json_response = json_encode([
            'success' => true,
            'message' => $response_message
        ]);
        
        if ($json_response === false) {
            // If JSON encoding failed, send a simpler response
            error_log("JSON encoding error: " . json_last_error_msg());
            echo json_encode([
                'success' => true,
                'message' => 'Action completed. ' . json_last_error_msg()
            ]);
        } else {
            echo $json_response;
        }
        exit();
    } else {
        // Normal redirect with message in session
        $_SESSION['success_message'] = $response_message;
        $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : 'account_dashboard.php?tab=approvals';
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Function to generate a secure password
function generateSecurePassword($length = 12) {
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
