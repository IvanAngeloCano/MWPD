<?php
// Include database connection if not already included
if (!isset($pdo)) {
    include_once 'db/connection.php';
}

// Only show to certain roles
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['staff', 'regional director', 'division head'])) {
    return;
}

// Prevent duplicate buttons
if (defined('FLOATING_MENU_LOADED')) {
    return;
}
define('FLOATING_MENU_LOADED', true);

// Inject the button with JavaScript to ensure it works on all pages
register_shutdown_function(function() {
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create floating menu if it doesn't exist yet
        if (!document.querySelector('.floating-action-menu')) {
            const menuHTML = document.createElement('div');
            menuHTML.innerHTML = document.querySelector('script[data-floating-menu]').innerHTML;
            document.body.appendChild(menuHTML);
            
            // Initialize the floating menu functionality
            const mainButton = document.querySelector('.main-button');
            const menuOptions = document.querySelector('.menu-options');
            const activityLogsButton = document.getElementById('activityLogsButton');
            const reportGenButton = document.getElementById('reportGenButton');
            
            // Toggle menu on main button click
            mainButton.addEventListener('click', function() {
                menuOptions.classList.toggle('show');
            });
            
            // Hide menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.floating-action-menu')) {
                    menuOptions.classList.remove('show');
                }
            });
            
            // Activity Logs button
            if (activityLogsButton) {
                activityLogsButton.addEventListener('click', function() {
                    window.location.href = 'activity_logs.php';
                });
            }
            
            // Report Generation button
            if (reportGenButton) {
                reportGenButton.addEventListener('click', function() {
                    window.location.href = 'reports.php';
                });
            }
        }
    });
    </script>
    <script data-floating-menu type='text/template'>";
});

// Get unprocessed blacklist count for Regional Directors
$blacklist_count = 0;
if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'regional director') {
    try {
        // Check if blacklist table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
        if ($stmt->rowCount() > 0) {
            // Count pending blacklist entries
            $stmt = $pdo->query("SELECT COUNT(*) FROM blacklist WHERE status = 'pending'");
            $blacklist_count = $stmt->fetchColumn();
        }
    } catch (PDOException $e) {
        // Silently handle error
    }
}
?>

<!-- Floating Action Menu Styles -->
<style>
.floating-action-menu {
    position: fixed;
    bottom: 40px;
    left: 40px;
    z-index: 1000;
}

.main-button {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: rgba(0, 123, 255, 0.7);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.main-button:hover {
    background-color: rgba(0, 123, 255, 0.9);
    transform: scale(1.1);
}

.menu-options {
    position: absolute;
    bottom: 60px;
    left: 0;
    list-style: none;
    padding: 0;
    margin: 0;
    display: none;
}

.menu-options.show {
    display: block;
}

.menu-option {
    margin-bottom: 10px;
}

.option-button {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    font-size: 18px;
    cursor: pointer;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    opacity: 0.7;
}

.option-button:hover {
    transform: scale(1.1);
    opacity: 0.9;
}

/* Button Colors */
#activityLogsButton {
    background-color: rgba(40, 167, 69, 0.7); /* Green with opacity */
}

#activityLogsButton:hover {
    background-color: rgba(40, 167, 69, 0.9);
}

#blacklistLogsButton {
    background-color: rgba(220, 53, 69, 0.7); /* Red with opacity */
    color: white;
    text-decoration: none;
}

#blacklistLogsButton:hover {
    background-color: rgba(220, 53, 69, 0.9);
    color: white;
    text-decoration: none;
}

#reportGenButton {
    background-color: rgba(23, 162, 184, 0.7); /* Teal with opacity */
}

#reportGenButton:hover {
    background-color: rgba(23, 162, 184, 0.9);
}

.blacklist-badge {
    position: absolute;
    top: -5px;
    left: -5px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}
</style>

<!-- Floating Action Menu HTML -->
<div class="floating-action-menu">
    <div class="main-button">
        <i class="fa fa-bars"></i>
        <?php if ($blacklist_count > 0): ?>
        <span class="blacklist-badge"><?= $blacklist_count ?></span>
        <?php endif; ?>
    </div>
    <ul class="menu-options">
        <li class="menu-option" data-toggle="tooltip" title="Activity Logs">
            <div class="option-button" id="activityLogsButton">
                <i class="fa fa-history"></i>
            </div>
        </li>
        <li class="menu-option" data-toggle="tooltip" title="Blacklist Management">
            <a href="blacklist.php" class="option-button" id="blacklistLogsButton">
                <i class="fa fa-ban"></i>
            </a>
        </li>
        <li class="menu-option" data-toggle="tooltip" title="Generate Reports">
            <div class="option-button" id="reportGenButton">
                <i class="fa fa-file-export"></i>
            </div>
        </li>
    </ul>
</div>
</script>
