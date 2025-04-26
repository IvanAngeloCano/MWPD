<?php
session_start();
require_once 'connection.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Role Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .card { background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Role Check and Fix</h1>
        
        <div class="card">
            <h2>Current Session Information</h2>
            <?php if(isset($_SESSION['user_id'])): ?>
                <p><strong>User ID:</strong> <?= $_SESSION['user_id'] ?></p>
                <p><strong>Username:</strong> <?= $_SESSION['username'] ?></p>
                <p><strong>Full Name:</strong> <?= $_SESSION['full_name'] ?></p>
                <p><strong>Role:</strong> "<?= $_SESSION['role'] ?>" (exact value including case)</p>
                <p><strong>Role Type:</strong> <?= gettype($_SESSION['role']) ?></p>
            <?php else: ?>
                <p class="error">Not logged in</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Database Roles</h2>
            <p>These are the actual roles stored in the database:</p>
            
            <?php
            try {
                $stmt = $pdo->query("SELECT DISTINCT role FROM users ORDER BY role");
                $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if(count($roles) > 0) {
                    echo "<ul>";
                    foreach($roles as $role) {
                        echo "<li>\"$role\" (" . gettype($role) . ")</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='error'>No roles found in database</p>";
                }
            } catch (PDOException $e) {
                echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <div class="card">
            <h2>All Users</h2>
            
            <?php
            try {
                $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY role, username");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if(count($users) > 0) {
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
                    foreach($users as $user) {
                        echo "<tr>";
                        echo "<td>{$user['id']}</td>";
                        echo "<td>{$user['username']}</td>";
                        echo "<td>{$user['full_name']}</td>";
                        echo "<td>\"{$user['role']}\"</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>No users found in database</p>";
                }
            } catch (PDOException $e) {
                echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <div class="card">
            <h2>Fix Sidebar Menu</h2>
            <p>Based on the information above, I'll update the sidebar to correctly show menu items based on roles.</p>
            
            <?php
            // Get the actual role values from the database
            try {
                $divHeadRole = $pdo->query("SELECT role FROM users WHERE username = 'carlos_reyes' LIMIT 1")->fetchColumn();
                $regionalDirectorRole = $pdo->query("SELECT role FROM users WHERE username = 'elena_cruz' LIMIT 1")->fetchColumn();
                
                // Update the sidebar file
                $sidebarFile = __DIR__ . '/_sidebar.php';
                $sidebarContent = file_get_contents($sidebarFile);
                
                // Replace the role checks with the actual values from the database
                $sidebarContent = preg_replace(
                    "/(\\$_SESSION\['role'\] === ')([^']*)(')\s*\):\s*\?>/", 
                    "\\1" . $divHeadRole . "\\3) || \\1Division Head\\3)): ?>", 
                    $sidebarContent, 
                    1
                );
                
                $sidebarContent = preg_replace(
                    "/(\\$_SESSION\['role'\] === ')([^']*)(')\s*\):\s*\?>/", 
                    "\\1" . $regionalDirectorRole . "\\3) || \\1Regional Director\\3)): ?>", 
                    $sidebarContent, 
                    1
                );
                
                if(file_put_contents($sidebarFile, $sidebarContent)) {
                    echo "<p class='success'>Sidebar updated successfully to check for both \"$divHeadRole\" and \"Division Head\" for Accounts menu.</p>";
                    echo "<p class='success'>Sidebar updated successfully to check for both \"$regionalDirectorRole\" and \"Regional Director\" for Approvals menu.</p>";
                } else {
                    echo "<p class='error'>Failed to update sidebar file. Please check file permissions.</p>";
                }
                
            } catch (PDOException $e) {
                echo "<p class='error'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
        
        <p><a href="dashboard.php">Return to Dashboard</a></p>
    </div>
</body>
</html>
