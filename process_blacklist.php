<?php
require_once 'session.php';
require_once 'connection.php';
require_once 'notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isAjaxRequest()) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit();
    }
    header('Location: login.php');
    exit();
}

// Function to check if the request is an AJAX request
function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
        || isset($_GET['ajax']) 
        || isset($_POST['ajax']);
}

$user_id = $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : null;
$redirect_url = 'blacklist.php';

try {
    // Check if the blacklist table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
    if ($stmt->rowCount() === 0) {
        // Create the blacklist table if it doesn't exist
        $sql = "CREATE TABLE blacklist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            passport_number VARCHAR(50),
            email VARCHAR(255),
            phone VARCHAR(50),
            reason TEXT NOT NULL,
            submitted_by INT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            notes TEXT,
            approved_by INT,
            approved_date DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (submitted_by) REFERENCES users(id),
            FOREIGN KEY (approved_by) REFERENCES users(id)
        )";
        $pdo->exec($sql);
    }
    
    // Handle adding a new blacklist entry (Staff or Division Head)
    if ($action === 'add' && in_array(strtolower($user_role), ['staff', 'division head'])) {
        $full_name = trim($_POST['full_name']);
        $passport_number = trim($_POST['passport_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $reason = trim($_POST['reason']);
        
        if (empty($full_name) || empty($reason)) {
            $_SESSION['error_message'] = "Full name and reason are required fields.";
            header("Location: $redirect_url");
            exit();
        }
        
        // Insert the new blacklist record
        $stmt = $pdo->prepare("INSERT INTO blacklist (full_name, passport_number, email, phone, reason, submitted_by, status) 
                             VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$full_name, $passport_number, $email, $phone, $reason, $user_id]);
        $blacklist_id = $pdo->lastInsertId();
        
        // Send notification to regional directors
        $title = "New Blacklist Request";
        $message = "A new blacklist request has been submitted for $full_name by " . $_SESSION['user_fullname'] . ".";
        $link = "blacklist.php?tab=pending";
        
        // Get all regional directors
        $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(role) = 'regional director'");
        $stmt->execute();
        $directors = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Send notification to each director
        foreach ($directors as $director_id) {
            addNotification($pdo, $director_id, $title, $message, $link);
        }
        
        $success_message = "Blacklist request submitted successfully. It will be reviewed by a Regional Director.";
        
        // Check if this is an AJAX request
        if (isAjaxRequest()) {
            echo json_encode(['success' => true, 'message' => $success_message]);
            exit();
        }
        
        $_SESSION['success_message'] = $success_message;
        header("Location: $redirect_url");
        exit();
    }
    
    // Handle approving a blacklist entry (Regional Director only)
    elseif ($action === 'approve' && strtolower($user_role) === 'regional director') {
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $ids = isset($_POST['ids']) ? explode(',', $_POST['ids']) : [];
        $notes = trim($_POST['notes'] ?? '');
        
        // Process single approval
        if (!empty($id)) {
            $stmt = $pdo->prepare("UPDATE blacklist SET status = 'approved', notes = ?, approved_by = ?, approved_date = NOW() 
                                 WHERE id = ? AND status = 'pending'");
            $stmt->execute([$notes, $user_id, $id]);
            
            // Get the approved record details
            $stmt = $pdo->prepare("SELECT full_name, submitted_by FROM blacklist WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                // Notify the person who submitted the request
                $title = "Blacklist Request Confirmed";
                $message = "Your request to blacklist {$record['full_name']} has been confirmed.";
                $link = "blacklist.php?tab=blacklisted";
                
                addNotification($record['submitted_by'], $title, $message, $link);
            }
            
            $_SESSION['success_message'] = "Person has been successfully blacklisted.";
        }
        // Process batch approval
        elseif (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$notes, $user_id], $ids);
            
            $stmt = $pdo->prepare("UPDATE blacklist SET status = 'approved', notes = ?, approved_by = ?, approved_date = NOW() 
                                 WHERE id IN ($placeholders) AND status = 'pending'");
            $stmt->execute($params);
            
            // Get the approved records details
            $stmt = $pdo->prepare("SELECT full_name, submitted_by FROM blacklist WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group notifications by submitted_by
            $submitters = [];
            foreach ($records as $record) {
                if (!isset($submitters[$record['submitted_by']])) {
                    $submitters[$record['submitted_by']] = [];
                }
                $submitters[$record['submitted_by']][] = $record['full_name'];
            }
            
            // Notify each submitter
            foreach ($submitters as $submitter_id => $names) {
                $title = "Blacklist Requests Confirmed";
                $message = "Your request to blacklist " . count($names) . " individuals has been confirmed.";
                $link = "blacklist.php?tab=blacklisted";
                
                addNotification($pdo, $submitter_id, $title, $message, $link);
            }
            
            $_SESSION['success_message'] = count($ids) . " individuals have been successfully blacklisted.";
            header("Location: $redirect_url?tab=pending");
            exit();
        }
        
        header("Location: $redirect_url?tab=pending");
        exit();
    }
    
    // Handle rejecting a blacklist entry (Regional Director only) - DELETE rejected entries
    elseif ($action === 'reject' && strtolower($user_role) === 'regional director') {
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $ids = isset($_POST['ids']) ? explode(',', $_POST['ids']) : [];
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($notes)) {
            $_SESSION['error_message'] = "Reason for rejection is required.";
            header("Location: $redirect_url?tab=pending");
            exit();
        }
        
        // Process single rejection - DELETE instead of update
        if (!empty($id)) {
            // Get the record details before deleting for notification
            $stmt = $pdo->prepare("SELECT full_name, submitted_by FROM blacklist WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete the record instead of updating status
            $stmt = $pdo->prepare("DELETE FROM blacklist WHERE id = ? AND status = 'pending'");
            $stmt->execute([$id]);
            
            if ($record) {
                // Notify the person who submitted the request
                $title = "Blacklist Request Rejected";
                $message = "Your request to blacklist {$record['full_name']} has been rejected. Reason: $notes";
                $link = "blacklist.php";
                
                addNotification($record['submitted_by'], $title, $message, $link);
            }
            
            $_SESSION['success_message'] = "Blacklist request rejected and removed successfully.";
        }
        // Process batch rejection - DELETE instead of update
        elseif (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // Get the rejected records details before deleting
            $stmt = $pdo->prepare("SELECT full_name, submitted_by FROM blacklist WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete the records instead of updating status
            $stmt = $pdo->prepare("DELETE FROM blacklist WHERE id IN ($placeholders) AND status = 'pending'");
            $stmt->execute($ids);
            
            // Group notifications by submitted_by
            $submitters = [];
            foreach ($records as $record) {
                if (!isset($submitters[$record['submitted_by']])) {
                    $submitters[$record['submitted_by']] = [];
                }
                $submitters[$record['submitted_by']][] = $record['full_name'];
            }
            
            // Notify each submitter
            foreach ($submitters as $submitter_id => $names) {
                $title = "Blacklist Requests Rejected";
                $message = "Your request to blacklist " . count($names) . " individuals has been rejected. Reason: $notes";
                $link = "blacklist.php";
                
                addNotification($pdo, $submitter_id, $title, $message, $link);
            }
            
            $_SESSION['success_message'] = count($ids) . " blacklist requests rejected and removed successfully.";
        }
        
        header("Location: $redirect_url?tab=pending");
        exit();
    }
    
    // Handle unblocking a blacklisted entry (Regional Director only)
    elseif ($action === 'unblock' && strtolower($user_role) === 'regional director') {
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($id)) {
            $_SESSION['error_message'] = "Invalid blacklist record.";
            header("Location: $redirect_url?tab=blacklisted");
            exit();
        }
        
        if (empty($password)) {
            $_SESSION['error_message'] = "Password is required for unblocking.";
            header("Location: $redirect_url?tab=blacklisted");
            exit();
        }
        
        // Verify the user's password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error_message'] = "Incorrect password. Unblock operation cancelled.";
            header("Location: $redirect_url?tab=blacklisted");
            exit();
        }
        
        // Get the record details before deleting for notification
        $stmt = $pdo->prepare("SELECT full_name, submitted_by FROM blacklist WHERE id = ? AND status = 'approved'");
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            $_SESSION['error_message'] = "Blacklist record not found or already unblocked.";
            header("Location: $redirect_url?tab=blacklisted");
            exit();
        }
        
        // Delete the record to unblock
        $stmt = $pdo->prepare("DELETE FROM blacklist WHERE id = ? AND status = 'approved'");
        $stmt->execute([$id]);
        
        // Notify the person who submitted the request
        if (isset($record['submitted_by']) && $record['submitted_by']) {
            $title = "Blacklist Entry Unblocked";
            $message = "{$record['full_name']} has been removed from the blacklist by a Regional Director.";
            $link = "blacklist.php";
            
            addNotification($pdo, $record['submitted_by'], $title, $message, $link);
        }
        
        // Log the unblocking action
        $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $details = "Unblocked {$record['full_name']} from blacklist";
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_stmt->execute([$user_id, 'unblock_blacklist', $details, $ip]);
        
        $_SESSION['success_message'] = "{$record['full_name']} has been successfully unblocked and removed from the blacklist.";
        header("Location: $redirect_url?tab=blacklisted");
        exit();
    }
    
    // Invalid action or role
    else {
        $_SESSION['error_message'] = "Invalid action or insufficient permissions.";
        header("Location: $redirect_url");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    header("Location: $redirect_url");
    exit();
}
?>
