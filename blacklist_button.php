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
if (defined('BLACKLIST_BUTTON_LOADED')) {
    return;
}
define('BLACKLIST_BUTTON_LOADED', true);

// Inject the button with JavaScript to ensure it works on all pages
register_shutdown_function(function() {
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create blacklist button if it doesn't exist yet
        if (!document.getElementById('blacklistBtn')) {
            const buttonHTML = document.createElement('div');
            buttonHTML.innerHTML = document.querySelector('script[data-blacklist-button]').innerHTML;
            document.body.appendChild(buttonHTML);
        }
    });
    </script>
    <script data-blacklist-button type='text/template'>";
});


// This file will be included at the end of the page via _head.php
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

<!-- Blacklist Floating Action Button -->
<style>
.blacklist-floating-btn {
    position: fixed;
    width: 50px;
    height: 50px;
    bottom: 40px;
    left: 40px;
    background-color: rgba(0, 123, 255, 0.7);
    color: #FFF;
    border-radius: 50%;
    text-align: center;
    box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    transition: all 0.3s ease;
    cursor: pointer;
}

.blacklist-floating-btn:hover {
    background-color: rgba(0, 123, 255, 0.9);
    transform: scale(1.1);
}

.blacklist-close {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
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

.blacklist-dashboard {
    display: none;
    position: fixed;
    bottom: 95px;
    left: 20px;
    z-index: 999;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    width: 350px;
    max-height: 500px;
    overflow-y: auto;
}

.blacklist-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.blacklist-dashboard-content {
    padding: 15px;
}

.blacklist-dashboard-footer {
    padding: 10px 15px;
    text-align: right;
    border-top: 1px solid #eee;
}

.blacklist-item {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
}

.blacklist-item:last-child {
    border-bottom: none;
}

.blacklist-item-name {
    font-weight: bold;
    margin-bottom: 3px;
}

.blacklist-item-reason {
    font-size: 0.85em;
    color: #666;
}

.blacklist-status {
    display: inline-block;
    padding: 2px 6px;
    font-size: 11px;
    border-radius: 3px;
    margin-left: 5px;
}

.blacklist-status.pending {
    background-color: #ffc107;
    color: #212529;
}

.blacklist-status.approved {
    background-color: #28a745;
    color: white;
}

.blacklist-dashboard-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
}

.blacklist-tab {
    padding: 8px 15px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
}

.blacklist-tab.active {
    border-bottom-color: #007bff;
    font-weight: bold;
}

.blacklist-tab-content {
    display: none;
}

.blacklist-tab-content.active {
    display: block;
}
</style>

<div class="blacklist-floating-btn" id="blacklistBtn" title="Blacklist Management">
    <i class="fas fa-user-slash"></i>
    <?php if (strtolower($_SESSION['role']) === 'regional director' && $blacklist_count > 0): ?>
    <span class="blacklist-badge"><?= $blacklist_count ?></span>
    <?php endif; ?>
</div>

<div class="blacklist-dashboard" id="blacklistDashboard">
    <div class="blacklist-dashboard-header">
        <h4 style="margin: 0;">Blacklist Management</h4>
        <span class="close-dashboard" style="cursor: pointer;">×</span>
    </div>
    
    <div class="blacklist-dashboard-tabs">
        <div class="blacklist-tab active" data-tab="pending">Pending</div>
        <div class="blacklist-tab" data-tab="approved">Approved</div>
        <?php if (strtolower($_SESSION['role']) !== 'regional director'): ?>
        <div class="blacklist-tab" data-tab="add">Add New</div>
        <?php endif; ?>
    </div>
    
    <div class="blacklist-dashboard-content">
        <!-- Pending Tab Content -->
        <div class="blacklist-tab-content active" id="tab-pending">
            <?php
            // Get recent pending entries
            $pending_records = [];
            try {
                // Check if blacklist table exists
                $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
                if ($stmt->rowCount() > 0) {
                    // Get pending records, limited to 5
                    $sql = "SELECT b.*, u.full_name as submitted_by_name 
                            FROM blacklist b 
                            LEFT JOIN users u ON b.submitted_by = u.id 
                            WHERE b.status = 'pending' 
                            ORDER BY b.submitted_date DESC LIMIT 5";
                    $stmt = $pdo->query($sql);
                    $pending_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                // Silently handle error
            }
            
            if (empty($pending_records)): ?>
                <p>No pending blacklist entries.</p>
            <?php else: ?>
                <?php foreach ($pending_records as $record): ?>
                <div class="blacklist-item">
                    <div class="blacklist-item-name">
                        <?= htmlspecialchars($record['full_name']) ?>
                        <span class="blacklist-status pending">Pending</span>
                    </div>
                    <div class="blacklist-item-reason">
                        <?= htmlspecialchars(substr($record['reason'], 0, 100)) ?><?= strlen($record['reason']) > 100 ? '...' : '' ?>
                    </div>
                    <div style="font-size: 0.8em; color: #888; margin-top: 5px;">
                        Submitted by: <?= htmlspecialchars($record['submitted_by_name'] ?: 'Unknown') ?> · <?= date('M d, Y', strtotime($record['submitted_date'])) ?>
                    </div>
                    <?php if (strtolower($_SESSION['role']) === 'regional director'): ?>
                    <div style="margin-top: 8px;">
                        <button class="blacklist-action-btn approve" data-id="<?= $record['id'] ?>" style="background-color: #28a745; color: white; border: none; border-radius: 3px; padding: 3px 8px; margin-right: 5px; font-size: 12px; cursor: pointer;">Approve</button>
                        <button class="blacklist-action-btn reject" data-id="<?= $record['id'] ?>" style="background-color: #dc3545; color: white; border: none; border-radius: 3px; padding: 3px 8px; font-size: 12px; cursor: pointer;">Reject</button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 10px; text-align: center;">
                    <a href="blacklist.php?tab=pending" style="color: #007bff; text-decoration: none; font-size: 0.85em;">View all pending entries</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Approved Tab Content -->
        <div class="blacklist-tab-content" id="tab-approved">
            <?php
            // Get recent approved entries
            $approved_records = [];
            try {
                // Check if blacklist table exists
                $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
                if ($stmt->rowCount() > 0) {
                    // Get approved records, limited to 5
                    $sql = "SELECT b.* 
                            FROM blacklist b 
                            WHERE b.status = 'approved' 
                            ORDER BY b.approved_date DESC LIMIT 5";
                    $stmt = $pdo->query($sql);
                    $approved_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                // Silently handle error
            }
            
            if (empty($approved_records)): ?>
                <p>No approved blacklist entries.</p>
            <?php else: ?>
                <?php foreach ($approved_records as $record): ?>
                <div class="blacklist-item">
                    <div class="blacklist-item-name">
                        <?= htmlspecialchars($record['full_name']) ?>
                        <span class="blacklist-status approved">Approved</span>
                    </div>
                    <div class="blacklist-item-reason">
                        <?= htmlspecialchars(substr($record['reason'], 0, 100)) ?><?= strlen($record['reason']) > 100 ? '...' : '' ?>
                    </div>
                    <div style="font-size: 0.8em; color: #888; margin-top: 5px;">
                        <?= !empty($record['approved_date']) ? 'Approved on: ' . date('M d, Y', strtotime($record['approved_date'])) : '' ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div style="margin-top: 10px; text-align: center;">
                    <a href="blacklist.php?tab=approved" style="color: #007bff; text-decoration: none; font-size: 0.85em;">View all approved entries</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (strtolower($_SESSION['role']) !== 'regional director'): ?>
        <!-- Add New Tab Content -->
        <div class="blacklist-tab-content" id="tab-add">
            <form id="blacklistAddForm" action="process_blacklist.php" method="post">
                <input type="hidden" name="action" value="add">
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Full Name *</label>
                    <input type="text" name="full_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Passport Number</label>
                    <input type="text" name="passport_number" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email Address</label>
                    <input type="email" name="email" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Phone Number</label>
                    <input type="text" name="phone" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Reason for Blacklisting *</label>
                    <textarea name="reason" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 80px;"></textarea>
                </div>
                
                <div style="text-align: right;">
                    <button type="submit" style="background-color: #007bff; color: white; border: none; border-radius: 4px; padding: 8px 15px; cursor: pointer;">Submit for Approval</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const blacklistBtn = document.getElementById('blacklistBtn');
    const blacklistDashboard = document.getElementById('blacklistDashboard');
    const closeDashboard = document.querySelector('.close-dashboard');
    const tabs = document.querySelectorAll('.blacklist-tab');
    
    // Toggle dashboard visibility
    blacklistBtn.addEventListener('click', function() {
        blacklistDashboard.style.display = blacklistDashboard.style.display === 'block' ? 'none' : 'block';
    });
    
    // Close dashboard when X is clicked
    closeDashboard.addEventListener('click', function() {
        blacklistDashboard.style.display = 'none';
    });
    
    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs and content
            document.querySelectorAll('.blacklist-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.blacklist-tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });
    
    // Handle approve/reject buttons
    document.querySelectorAll('.blacklist-action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.classList.contains('approve') ? 'approve' : 'reject';
            const id = this.dataset.id;
            
            if (action === 'approve') {
                if (confirm('Are you sure you want to approve this blacklist entry?')) {
                    window.location.href = 'process_blacklist.php?action=approve&id=' + id;
                }
            } else {
                const reason = prompt('Please provide a reason for rejection:');
                if (reason) {
                    window.location.href = 'process_blacklist.php?action=reject&id=' + id + '&notes=' + encodeURIComponent(reason);
                }
            }
        });
    });
    
    // Handle form submission
    const blacklistAddForm = document.getElementById('blacklistAddForm');
    if (blacklistAddForm) {
        blacklistAddForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('process_blacklist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Blacklist entry submitted successfully!');
                    // Reset form and switch to pending tab
                    blacklistAddForm.reset();
                    document.querySelector('.blacklist-tab[data-tab="pending"]').click();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred while submitting the form.');
                console.error(error);
            });
        });
    }
});
</script>
