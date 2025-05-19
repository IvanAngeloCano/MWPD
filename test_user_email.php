<?php
/**
 * Test User Email Field
 * This script checks the email field for a specific user in the database
 */

// Include database connection
require_once 'connection.php';

// Check if user ID is provided
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>User Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .card { border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px; }
        h1 { color: #0056b3; }
        .field { margin-bottom: 10px; }
        .label { font-weight: bold; display: inline-block; width: 150px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <h1>User Email Field Test</h1>';

if ($user_id > 0) {
    try {
        // Get PDO connection - using the global $pdo variable
        global $pdo;
        
        // Get user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo '<div class="card">';
            echo '<h2>User #' . $user_id . ' Details</h2>';
            
            // Display all user fields
            foreach ($user as $field => $value) {
                echo '<div class="field">';
                echo '<span class="label">' . htmlspecialchars($field) . ':</span> ';
                
                // Special handling for email field to highlight
                if (strtolower($field) === 'email') {
                    if (!empty($value)) {
                        echo '<span class="success">' . htmlspecialchars($value) . '</span>';
                    } else {
                        echo '<span class="error">Empty/NULL value</span>';
                    }
                } else {
                    // Hide password for security
                    if (strtolower($field) === 'password') {
                        echo '[HIDDEN]';
                    } else {
                        echo htmlspecialchars($value);
                    }
                }
                echo '</div>';
            }
            
            // Test the email field specifically
            $email_field = isset($user['email']) ? $user['email'] : null;
            
            echo '<h3>Email Field Test</h3>';
            echo '<div class="field">';
            echo '<span class="label">isset($user[\'email\']):</span> ';
            echo isset($user['email']) ? '<span class="success">TRUE</span>' : '<span class="error">FALSE</span>';
            echo '</div>';
            
            echo '<div class="field">';
            echo '<span class="label">empty($user[\'email\']):</span> ';
            echo empty($user['email']) ? '<span class="error">TRUE (Email is empty)</span>' : '<span class="success">FALSE (Email has content)</span>';
            echo '</div>';
            
            echo '<div class="field">';
            echo '<span class="label">Value:</span> ';
            echo '<pre>' . var_export($email_field, true) . '</pre>';
            echo '</div>';
            
            // Database field name check
            echo '<h3>Database Field Names</h3>';
            echo '<div class="field">';
            echo '<pre>';
            $fields_query = $pdo->query("DESCRIBE users");
            $fields = $fields_query->fetchAll(PDO::FETCH_ASSOC);
            foreach ($fields as $field) {
                echo htmlspecialchars($field['Field']) . ' - ' . htmlspecialchars($field['Type']) . "\n";
                
                // Highlight if this is an email field
                if (stripos($field['Field'], 'email') !== false) {
                    echo "  ← POSSIBLE EMAIL FIELD FOUND!\n";
                }
            }
            echo '</pre>';
            echo '</div>';
            
            echo '</div>';
            
            // Test form to send an email to this user
            echo '<div class="card">';
            echo '<h2>Send Test Email</h2>';
            
            if (!empty($email_field)) {
                echo '<form method="post" action="test_email_templates.php">';
                echo '<input type="hidden" name="action" value="send_test">';
                echo '<input type="hidden" name="recipient_email" value="' . htmlspecialchars($email_field) . '">';
                echo '<input type="hidden" name="template_type" value="0">'; // Password reset template
                echo '<button type="submit">Send Test Password Reset Email</button>';
                echo '</form>';
            } else {
                echo '<p class="error">Cannot send test email because email field is empty or NULL</p>';
            }
            
            echo '</div>';
            
        } else {
            echo '<div class="card error">';
            echo '<p>User #' . $user_id . ' not found in the database.</p>';
            echo '</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card error">';
        echo '<p>Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
} else {
    echo '<div class="card">';
    echo '<p>Please provide a user ID to test:</p>';
    echo '<form method="get">';
    echo '<input type="number" name="id" placeholder="User ID" required>';
    echo '<button type="submit">Check User</button>';
    echo '</form>';
    echo '</div>';
}

// List all users with IDs and email for convenience
try {
    global $pdo;
    $users = $pdo->query("SELECT id, username, full_name, email FROM users LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo '<div class="card">';
        echo '<h2>Available Users</h2>';
        echo '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">';
        echo '<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Action</th></tr>';
        
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . $user['id'] . '</td>';
            echo '<td>' . htmlspecialchars($user['username']) . '</td>';
            echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
            echo '<td>' . (empty($user['email']) ? '<span class="error">EMPTY</span>' : htmlspecialchars($user['email'])) . '</td>';
            echo '<td><a href="?id=' . $user['id'] . '">Check</a></td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</div>';
    }
} catch (PDOException $e) {
    // Silently fail
}

echo '</body></html>';
?>
