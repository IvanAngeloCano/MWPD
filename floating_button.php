<?php
// This file adds a floating circular button to all pages that appears in the bottom corner
// When the user clicks the X button, a dashboard appears for managing blacklisted individuals
// We output the HTML directly so it can be included via a script tag

// Only proceed if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return empty content if user not logged in
    echo '<!-- User not logged in, no button shown -->';
    exit;
}

// Get count of pending blacklist entries (for badge display)
$blacklist_count = 0;
try {
    // Only check if blacklist table exists and user has right role
    if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'regional director') {
        $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM blacklist WHERE status = 'pending'");
            $blacklist_count = $stmt->fetchColumn();
        }
    }
} catch (Exception $e) {
    // Silently handle error
    $blacklist_count = 0;
}

// We want to output the HTML and JS directly, not via JavaScript injection
// This ensures it works across all pages


<!-- Floating Circular Button -->
<style>
.floating-circle-btn {
    position: fixed;
    width: 60px;
    height: 60px;
    bottom: 30px;
    right: 30px;
    background-color: #ff4081; /* Pink/red color as shown in the image */
    color: white;
    border-radius: 50%;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    cursor: pointer;
    transition: all 0.3s ease;
}

.floating-circle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 12px rgba(0,0,0,0.4);
}

.floating-circle-btn i {
    font-size: 24px;
}

/* Dashboard modal that appears when button is clicked */
.blacklist-dashboard {
    display: none;
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 350px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    z-index: 9998;
    max-height: 500px;
    overflow-y: auto;
}

.blacklist-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.blacklist-dashboard-content {
    padding: 15px;
}
</style>

<!-- Floating Button -->
<div class="floating-circle-btn" id="floatingCircleBtn">
    <i class="fas fa-times"></i>
</div>

<!-- Dashboard modal -->
<div class="blacklist-dashboard" id="blacklistDashboard">
    <div class="blacklist-dashboard-header">
        <h4 style="margin: 0;">Blacklist Dashboard</h4>
        <span style="cursor: pointer;" id="closeDashboardBtn">&times;</span>
    </div>
    <div class="blacklist-dashboard-content">
        <p>This is the blacklist dashboard. You can manage blacklisted persons here.</p>
        
        <!-- Tabs for different sections -->
        <div class="dashboard-tabs" style="display: flex; margin-bottom: 15px; border-bottom: 1px solid #dee2e6;">
            <div class="tab active" style="padding: 8px 12px; cursor: pointer; border-bottom: 2px solid #007bff;">Pending</div>
            <div class="tab" style="padding: 8px 12px; cursor: pointer;">Approved</div>
            <div class="tab" style="padding: 8px 12px; cursor: pointer;">Add New</div>
        </div>
        
        <!-- Pending tab content -->
        <div style="margin-bottom: 15px;">
            <div style="padding: 10px; border-bottom: 1px solid #eee;">
                <div style="font-weight: bold;">John Doe</div>
                <div style="font-size: 0.9em; color: #666;">Submitted on: May 5, 2025</div>
            </div>
            <div style="padding: 10px; border-bottom: 1px solid #eee;">
                <div style="font-weight: bold;">Jane Smith</div>
                <div style="font-size: 0.9em; color: #666;">Submitted on: May 2, 2025</div>
            </div>
        </div>
        
        <a href="blacklist.php" style="display: inline-block; padding: 8px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; text-align: center; width: 100%;">View Full Dashboard</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const floatingBtn = document.getElementById('floatingCircleBtn');
    const dashboard = document.getElementById('blacklistDashboard');
    const closeBtn = document.getElementById('closeDashboardBtn');
    
    // Toggle dashboard when button is clicked
    floatingBtn.addEventListener('click', function() {
        if (dashboard.style.display === 'block') {
            dashboard.style.display = 'none';
        } else {
            dashboard.style.display = 'block';
        }
    });
    
    // Close dashboard when X is clicked
    closeBtn.addEventListener('click', function() {
        dashboard.style.display = 'none';
    });
    
    // Close when clicking outside
    document.addEventListener('click', function(event) {
        if (!dashboard.contains(event.target) && !floatingBtn.contains(event.target) && dashboard.style.display === 'block') {
            dashboard.style.display = 'none';
        }
    });
});
</script>
