<?php
include 'session.php';
require_once 'connection.php';
require_once 'notifications.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notification System Debug</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container">
        <h1>Notification System Debug</h1>
        
        <div class="card">
            <div class="card-header">
                <h2>Console Errors</h2>
            </div>
            <div class="card-body">
                <p>Check the browser console for JavaScript errors</p>
                <div id="errorOutput"></div>
                <button id="testNotifications" class="btn btn-primary">Test Notifications</button>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Current Notifications</h2>
            </div>
            <div class="card-body">
                <?php
                $user_id = $_SESSION['user_id'] ?? 0;
                if ($user_id) {
                    $notifications = getUserNotifications($user_id, true, 10);
                    if (empty($notifications)) {
                        echo "<p>No notifications found for user ID: $user_id</p>";
                    } else {
                        echo "<table class='table'>";
                        echo "<thead><tr><th>ID</th><th>Message</th><th>Read</th><th>Created</th></tr></thead>";
                        echo "<tbody>";
                        foreach ($notifications as $notification) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($notification['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
                            echo "<td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td>";
                            echo "<td>" . htmlspecialchars($notification['created_at']) . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody></table>";
                    }
                } else {
                    echo "<p>User not logged in</p>";
                }
                ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h2>Fix Notifications</h2>
            </div>
            <div class="card-body">
                <button id="fixNotifications" class="btn btn-warning">Fix Notification System</button>
                <div id="fixOutput" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Capture and display JavaScript errors
        window.onerror = function(message, source, lineno, colno, error) {
            const errorDiv = document.getElementById('errorOutput');
            const errorMessage = `<div class="alert alert-danger">
                <strong>Error:</strong> ${message}<br>
                <strong>Source:</strong> ${source}<br>
                <strong>Line:</strong> ${lineno}, <strong>Column:</strong> ${colno}
            </div>`;
            errorDiv.innerHTML += errorMessage;
            console.error(message, source, lineno, colno, error);
            return false;
        };
        
        // Test the notification system
        document.getElementById('testNotifications').addEventListener('click', function() {
            console.log('Testing notification mark as read...');
            
            // Get all notifications on page
            const items = document.querySelectorAll('.notification-item');
            if (items.length === 0) {
                console.log('No notifications found to test');
                alert('No notifications found to test');
                return;
            }
            
            // Try to mark first notification as read
            const firstId = items[0].dataset.id;
            if (!firstId) {
                console.log('No notification ID found');
                alert('No notification ID found');
                return;
            }
            
            console.log('Testing mark as read for notification ID:', firstId);
            
            // Test the AJAX call
            fetch('notification_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_read&ids=' + firstId
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    throw new Error('Invalid JSON response: ' + text);
                }
            })
            .then(data => {
                console.log('Parsed data:', data);
                alert('Test completed. Check console for details.');
            })
            .catch(error => {
                console.error('Error testing notification:', error);
                alert('Error: ' + error.message);
            });
        });
        
        // Fix the notification system
        document.getElementById('fixNotifications').addEventListener('click', function() {
            const fixOutput = document.getElementById('fixOutput');
            fixOutput.innerHTML = '<div class="alert alert-info">Fixing notification system...</div>';
            
            fetch('fix_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fixOutput.innerHTML = `<div class="alert alert-success">
                        <strong>Success!</strong> ${data.message}<br>
                        Fixed ${data.fixed_count} issues.
                    </div>`;
                } else {
                    fixOutput.innerHTML = `<div class="alert alert-danger">
                        <strong>Error:</strong> ${data.message}
                    </div>`;
                }
            })
            .catch(error => {
                fixOutput.innerHTML = `<div class="alert alert-danger">
                    <strong>Error:</strong> ${error.message}
                </div>`;
            });
        });
    });
    </script>
</body>
</html>
