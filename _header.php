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
    
    <div class="notif-icon" id="notificationIcon">
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

    <a href="profile.php" class="user-profile-link" style="text-decoration: none !important; border-bottom: none !important;">
      <div class="user-profile">
        <div class="profile-icon">
          <?php if(isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['profile_picture']) ?>" alt="Profile picture" class="header-profile-picture">
          <?php else: ?>
            <i class="fa fa-user-circle"></i>
          <?php endif; ?>
        </div>
        <div class="profile-info">
          <span><?= isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : htmlspecialchars($_SESSION['username']) ?></span>
          <span><?= isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'User' ?></span>
        </div>
      </div>
    </a>
    
    <button class="fullscreen-toggle" id="fullscreenToggle">
      <i class="fa fa-expand"></i>
    </button>
  </div>
</header>

<!-- Display alerts -->
<?php displayAlerts(); ?>

<style>
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
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
      <a href="gov_to_gov_form.php" class="quick-card">
        <i class="fa fa-university"></i>
        <span>Gov-to-Gov</span>
      </a>
      <a href="job_fair_form.php" class="quick-card">
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
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!notificationIcon.contains(e.target)) {
        notificationDropdown.style.display = 'none';
      }
    });
    
    // Mark all as read
    markAllReadBtn.addEventListener('click', function() {
      const items = document.querySelectorAll('.notification-item');
      const ids = Array.from(items).map(item => item.dataset.id).filter(id => id);
      if (ids.length > 0) {
        markNotificationsAsRead(ids);
      }
    });
    
    // Handle notification item clicks for navigation
    document.addEventListener('click', function(e) {
      const notificationItem = e.target.closest('.notification-item');
      if (notificationItem && !e.target.classList.contains('notification-dismiss')) {
        const link = notificationItem.dataset.link;
        if (link) {
          window.location.href = link;
        }
      }
    });
    
    // Dismiss notification
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('notification-dismiss')) {
        const id = e.target.dataset.id;
        if (id) {
          dismissNotification(id);
        }
      }
    });
    
    // Mark notifications as read via AJAX
    function markNotificationsAsRead(ids) {
      if (!ids || !ids.length) return;
      
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
          ids.forEach(id => {
            const item = document.querySelector(`.notification-item[data-id="${id}"]`);
            if (item) item.classList.remove('unread');
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
        }
      })
      .catch(error => console.error('Error marking notifications as read:', error));
    }
    
    // Dismiss notification via AJAX
    function dismissNotification(id) {
      if (!id) return;
      
      fetch('notification_actions.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&id=' + id
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          const item = document.querySelector(`.notification-item[data-id="${id}"]`);
          if (item) {
            item.style.height = '0';
            item.style.padding = '0';
            item.style.overflow = 'hidden';
            setTimeout(() => {
              item.remove();
              
              // Check if no notifications left
              const list = document.getElementById('notificationList');
              if (list && list.children.length === 0) {
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
        }
      })
      .catch(error => console.error('Error dismissing notification:', error));
    }
    
    // Fullscreen toggle functionality
    const fullscreenToggle = document.getElementById('fullscreenToggle');
    if (fullscreenToggle) {
      fullscreenToggle.addEventListener('click', function() {
        toggleFullscreen();
      });
      
      function toggleFullscreen() {
        if (!document.fullscreenElement && 
            !document.mozFullScreenElement && 
            !document.webkitFullscreenElement && 
            !document.msFullscreenElement) {
          // Enter fullscreen
          if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
          } else if (document.documentElement.msRequestFullscreen) {
            document.documentElement.msRequestFullscreen();
          } else if (document.documentElement.mozRequestFullScreen) {
            document.documentElement.mozRequestFullScreen();
          } else if (document.documentElement.webkitRequestFullscreen) {
            document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
          }
          fullscreenToggle.innerHTML = '<i class="fa fa-compress"></i>';
        } else {
          // Exit fullscreen
          if (document.exitFullscreen) {
            document.exitFullscreen();
          } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
          } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
          } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
          }
          fullscreenToggle.innerHTML = '<i class="fa fa-expand"></i>';
        }
      }
      
      // Update icon when fullscreen state changes
      document.addEventListener('fullscreenchange', updateFullscreenIcon);
      document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
      document.addEventListener('mozfullscreenchange', updateFullscreenIcon);
      document.addEventListener('MSFullscreenChange', updateFullscreenIcon);
      
      function updateFullscreenIcon() {
        if (document.fullscreenElement || 
            document.webkitFullscreenElement || 
            document.mozFullScreenElement || 
            document.msFullscreenElement) {
          fullscreenToggle.innerHTML = '<i class="fa fa-compress"></i>';
        } else {
          fullscreenToggle.innerHTML = '<i class="fa fa-expand"></i>';
        }
      }
    }
  });
</script>