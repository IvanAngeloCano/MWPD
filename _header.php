<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Include our session-based alert system
require_once 'alert_system.php';

// Get unread notifications count and recent notifications
$unread_count = 0;
$notifications = [];
if (isset($_SESSION['user_id'])) {
  // Always use database notifications for persistence across sessions
  require_once 'notifications.php';

  // Ensure the notifications table exists
  ensureNotificationsTableExists();

  // Get notifications from database
  $unread_count = countUnreadNotifications($_SESSION['user_id']);
  $notifications = getUserNotifications($_SESSION['user_id'], true, 10);

  // Debug log for troubleshooting
  error_log("Fetched " . count($notifications) . " notifications for user ID: " . $_SESSION['user_id']);
}
?>
<header class="header">
  <div class="header-left">
    <h1><?= isset($pageTitle) ? $pageTitle : 'Page' ?></h1>
  </div>

  <div class="header-right">
    <button class="quick-add">+ Quick Add</button>

    <div class="notif-icon" id="notificationIcon" style="display: flex; margin: 0 15px; cursor: pointer; position: relative;">
      <i class="fa fa-bell"></i>
      <?php if ($unread_count > 0): ?>
        <span class="notif-dot" id="notificationDot"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
      <?php endif; ?>

      <!-- Notification dropdown -->
      <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
          <h3>Notifications</h3>
          <button class="mark-all-read" id="markAllRead">Mark all as read</button>
        </div>

        <div class="notification-list" id="notificationList">
          <?php if (empty($notifications)): ?>
            <div class="no-notifications">No notifications</div>
          <?php else: ?>
            <?php foreach ($notifications as $index => $notification): ?>
              <?php
              // Format the notification time
              $timeString = isset($notification['created_at']) ? $notification['created_at'] : date('Y-m-d H:i:s');
              $timeStamp = strtotime($timeString);

              // Get read status
              $isRead = isset($notification['is_read']) ? (int)$notification['is_read'] === 1 : false;
              ?>
              <div class="notification-item <?= $isRead ? 'read' : 'unread' ?>"
                data-id="<?= $notification['id'] ?>"
                data-link="<?= htmlspecialchars($notification['link'] ?? '') ?>">
                <div class="notification-content">
                  <p><?= htmlspecialchars($notification['message']) ?></p>
                  <span class="notification-time"><?= date('M j, g:i a', $timeStamp) ?></span>
                </div>
                <button type="button" class="notification-dismiss" data-id="<?= $notification['id'] ?>">×</button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <a href="profile.php" class="user-profile-link user-dropdown" style="text-decoration: none !important; border-bottom: none !important;">
      <div class="user-profile">
        <div class="profile-icon">
          <?php 
          // Force profile picture reload from database
          if (isset($_SESSION['user_id'])) {
            try {
              require_once 'connection.php';
              $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
              $stmt->execute([$_SESSION['user_id']]);
              $user = $stmt->fetch(PDO::FETCH_ASSOC);
              
              if ($user && !empty($user['profile_picture'])) {
                $_SESSION['profile_picture'] = $user['profile_picture'];
              }
            } catch (Exception $e) {
              // Silently fail, use whatever is in session
            }
          }
          
          if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['profile_picture']) ?>" alt="Profile picture" class="header-profile-picture" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: white;">
          <?php else: ?>
            <div class="header-profile-picture" style="width: 40px; height: 40px; border-radius: 50%; background-color: #e0e0e0; display: flex; justify-content: center; align-items: center;">
              <i class="fa fa-user" style="color: #9E9E9E; font-size: 20px;"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="profile-info">
          <span><?= isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : htmlspecialchars($_SESSION['username']) ?></span>
          <span><?= isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'User' ?></span>
        </div>
      </div>
    </a>

    <button class="fullscreen-toggle" id="fullscreenToggle"  title="Toggle Fullscreen">
      <i class="fa fa-expand"></i>
    </button>
  </div>
</header>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const fullscreenBtn = document.getElementById('fullscreenToggle');

    fullscreenBtn.addEventListener('click', () => {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        fullscreenBtn.innerHTML = '<i class="fa fa-compress"></i>';
      } else {
        document.exitFullscreen();
        fullscreenBtn.innerHTML = '<i class="fa fa-expand"></i>';
      }
    });
  });
</script>


<!-- Display alerts -->
<?php displayAlerts(); ?>

<style>
  /* Profile picture styling */
  .header-profile-picture {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    background-color: white;
    border: 2px solid #f8f9fa;
  }

  .notif-icon {
    position: relative;
    font-size: 1.2rem;
    cursor: pointer;
    margin: 0 15px;
  }

  .notif-dot {
    position: absolute;
    top: -5px;
    right: -8px;
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    font-size: 0.75rem;
    min-width: 18px;
    height: 18px;
    text-align: center;
    line-height: 18px;
    font-weight: bold;
  }

  .notification-dropdown {
    position: absolute;
    top: 100%;
    right: -15px;
    width: 320px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: none;
    z-index: 1000;
    max-height: 400px;
    overflow-y: auto;
    margin-top: 10px;
  }

  .notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #e5e7eb;
  }

  .notification-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
  }

  .mark-all-read {
    background: none;
    border: none;
    color: #2563eb;
    font-size: 0.8rem;
    cursor: pointer;
  }

  .notification-list {
    padding: 0;
  }

  .notification-item {
    display: flex;
    padding: 12px 15px;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s;
    align-items: flex-start;
    justify-content: space-between;
    cursor: pointer;
  }

  .notification-item:hover {
    background-color: #f0f7ff;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .notification-item.unread {
    background-color: #e6f3ff;
    border-left: 3px solid #1e88e5;
  }

  .notification-item.read {
    background-color: #ffffff;
    border-left: 3px solid transparent;
  }

  .notification-content {
    flex: 1;
  }

  .notification-content p {
    margin: 0 0 5px 0;
    color: #1e293b;
    font-size: 0.9rem;
  }

  .notification-time {
    color: #94a3b8;
    font-size: 0.75rem;
  }

  .notification-dismiss {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0 0 0 10px;
    align-self: flex-start;
  }

  .notification-dismiss:hover {
    color: #ef4444;
  }

  .no-notifications {
    padding: 15px;
    text-align: center;
    color: #94a3b8;
    font-style: italic;
  }
</style>

<div id="quickAddModal" class="quick-add-modal hidden">
  <div class="modal-content">
    <h2>Select Process</h2>
    <div class="quick-add-cards">
      <a href="direct_hire_add.php" class="quick-card">
        <i class="fa fa-briefcase"></i>
        <span>Direct Hire</span>
      </a>
      <a href="balik_manggagawa_form.php" class="quick-card">
        <i class="fa fa-sign-in-alt"></i>
        <span>Balik Manggagawa</span>
      </a>
      <a href="gov_to_gov_add.php" class="quick-card">
        <i class="fa fa-university"></i>
        <span>Gov-to-Gov</span>
      </a>
      <a href="job_fair_add.php" class="quick-card">
        <i class="fa fa-clipboard-list"></i>
        <span>Job Fairs</span>
      </a>
    </div>
    <button class="modal-close" onclick="closeModal()">Close</button>
  </div>
</div>


<script>
  const quickAddBtn = document.querySelector('.quick-add');
  const modal = document.getElementById('quickAddModal');

  quickAddBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
  });

  function closeModal() {
    modal.classList.add('hidden');
  }

  // Optional: Close modal on outside click
  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      closeModal();
    }
  });

  // Notification system
  document.addEventListener('DOMContentLoaded', function() {
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const markAllReadBtn = document.getElementById('markAllRead');

    // Toggle notification dropdown
    notificationIcon.addEventListener('click', function(e) {
      e.stopPropagation();
      notificationDropdown.style.display = notificationDropdown.style.display === 'block' ? 'none' : 'block';
      
      // Don't automatically mark notifications as read when dropdown opens
      // This follows the user's request to only mark as read when clicked
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!notificationIcon.contains(e.target)) {
        notificationDropdown.style.display = 'none';
      }
    });

    // Mark all as read - Fix to ensure it works properly
    markAllReadBtn.addEventListener('click', function(e) {
      e.stopPropagation(); // Prevent event bubbling
      e.preventDefault();
      
      const items = document.querySelectorAll('.notification-item');
      const ids = Array.from(items).map(item => item.dataset.id).filter(id => id);
      
      if (ids.length > 0) {
        // Show loading indicator
        markAllReadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing...';
        markAllReadBtn.disabled = true;
        
        // Call the endpoint
        markNotificationsAsRead(ids, function() {
          // Reset button after processing
          markAllReadBtn.innerHTML = 'Mark all as read';
          markAllReadBtn.disabled = false;
        });
      }
    });

    // Handle notification item clicks for navigation AND mark as read when clicked
    document.addEventListener('click', function(e) {
      const notificationItem = e.target.closest('.notification-item');
      if (notificationItem && !e.target.classList.contains('notification-dismiss')) {
        // Mark this specific notification as read when clicked
        const notificationId = notificationItem.dataset.id;
        if (notificationId && notificationItem.classList.contains('unread')) {
          markNotificationsAsRead([notificationId]);
        }
        
        // Navigate to the link if available
        const link = notificationItem.dataset.link;
        if (link) {
          window.location.href = link;
        }
      }
    });

    // Dismiss notification (fix the X button)
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('notification-dismiss')) {
        e.stopPropagation(); // Prevent event bubbling
        e.stopPropagation(); // Prevent triggering the notification item click
        const id = e.target.dataset.id;
        if (id) {
          dismissNotification(id);
        }
      }
    });

    // Mark notifications as read via AJAX - Improved with callback
    function markNotificationsAsRead(ids, callback) {
      if (!ids || !ids.length) {
        if (callback) callback();
        return;
      }

      fetch('notification_actions.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'action=mark_read&ids=' + ids.join(',')
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update UI for each notification
            ids.forEach(id => {
              const item = document.querySelector(`.notification-item[data-id="${id}"]`);
              if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
              }
            });

            // Update the notification dot
            const dot = document.getElementById('notificationDot');
            if (dot) {
              if (data.unread_count === 0) {
                dot.style.display = 'none';
              } else {
                dot.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
              }
            }
            
            // Log success
            console.log('Successfully marked notifications as read:', ids);
          } else {
            console.error('Failed to mark notifications as read:', data.message || 'Unknown error');
          }
          
          // Execute callback if provided
          if (callback) callback();
        })
        .catch(error => {
          console.error('Error marking notifications as read:', error);
          if (callback) callback();
        });
    }

    // Dismiss notification via AJAX - FIXED VERSION
    function dismissNotification(id) {
      if (!id) {
        console.error('Attempted to dismiss notification without ID');
        return;
      }
      console.log('Dismissing notification ID:', id);

      // Show a small loading indicator on the notification
      const item = document.querySelector(`.notification-item[data-id="${id}"]`);
      if (item) {
        const dismissBtn = item.querySelector('.notification-dismiss');
        if (dismissBtn) dismissBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
      }

      // Use the emergency deletion endpoint instead
      fetch('emergency_delete_notification.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'notification_id=' + id
        })
        .then(response => {
          console.log('Dismiss response status:', response.status);
          return response.text();
        })
        .then(text => {
          console.log('Raw response:', text);
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('Failed to parse JSON:', e, text);
            throw new Error('Invalid JSON response');
          }
        })
        .then(data => {
          console.log('Dismissal result:', data);
          if (data && data.success) {
            if (item) {
              // Animate removal
              item.style.height = '0';
              item.style.padding = '0';
              item.style.overflow = 'hidden';
              item.style.opacity = '0';
              item.style.marginBottom = '0';
              
              setTimeout(() => {
                // Actually remove from DOM
                item.remove();

                // Check if no notifications left
                const list = document.getElementById('notificationList');
                if (list && (!list.children.length || (list.children.length === 1 && list.children[0].classList.contains('no-notifications')))) {
                  list.innerHTML = '<div class="no-notifications">No notifications</div>';
                }

                // Update the notification dot
                const dot = document.getElementById('notificationDot');
                if (dot) {
                  if (data.unread_count === 0) {
                    dot.style.display = 'none';
                  } else {
                    dot.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                  }
                }
              }, 300);
            }
          } else {
            console.error('Failed to dismiss notification:', data?.message || 'Unknown error');
            alert('Could not remove notification. Please try again.');
            
            // Reset the dismiss button
            if (item) {
              const dismissBtn = item.querySelector('.notification-dismiss');
              if (dismissBtn) dismissBtn.innerHTML = '×';
            }
          }
        })
        .catch(error => {
          console.error('Error dismissing notification:', error);
          alert('Error removing notification: ' + error.message);
          
          // Reset the dismiss button
          if (item) {
            const dismissBtn = item.querySelector('.notification-dismiss');
            if (dismissBtn) dismissBtn.innerHTML = '×';
          }
        });
    }
  });
</script>

<!-- Notification override script - MUST be loaded last -->
<script src="assets/js/notification_override.js"></script>

