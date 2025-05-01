/**
 * Notifications System
 * Handles real-time notifications for the MWPD system
 */

document.addEventListener('DOMContentLoaded', function() {
  // Elements
  const notificationIcon = document.getElementById('notificationIcon');
  const notificationDropdown = document.getElementById('notificationDropdown');
  const notificationList = document.getElementById('notificationList');
  const notificationDot = document.getElementById('notificationDot');
  const markAllReadBtn = document.getElementById('markAllRead');
  
  // Toggle notification dropdown
  if (notificationIcon) {
    notificationIcon.addEventListener('click', function(e) {
      e.stopPropagation();
      notificationDropdown.classList.toggle('show');
      
      // If opening the dropdown, mark notifications as seen
      if (notificationDropdown.classList.contains('show')) {
        markNotificationsAsSeen();
      }
    });
  }
  
  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (notificationDropdown && notificationDropdown.classList.contains('show') && !notificationIcon.contains(e.target)) {
      notificationDropdown.classList.remove('show');
    }
  });
  
  // Mark all notifications as read
  if (markAllReadBtn) {
    markAllReadBtn.addEventListener('click', function() {
      markAllNotificationsAsRead();
    });
  }
  
  // Handle individual notification dismiss
  if (notificationList) {
    notificationList.addEventListener('click', function(e) {
      if (e.target.classList.contains('notification-dismiss')) {
        const notificationId = e.target.getAttribute('data-id');
        dismissNotification(notificationId);
      } else if (e.target.closest('.notification-item')) {
        const notificationItem = e.target.closest('.notification-item');
        const notificationId = notificationItem.getAttribute('data-id');
        const isRead = notificationItem.classList.contains('read');
        
        if (!isRead) {
          markNotificationAsRead(notificationId);
        }
        
        // If there's a link, navigate to it
        const link = notificationItem.getAttribute('data-link');
        if (link) {
          window.location.href = link;
        }
      }
    });
  }
  
  // Poll for new notifications every 30 seconds
  setInterval(fetchNotifications, 30000);
  
  /**
   * Fetch notifications from the server
   */
  function fetchNotifications() {
    fetch('get_notifications.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateNotificationUI(data.notifications, data.unread_count);
        }
      })
      .catch(error => console.error('Error fetching notifications:', error));
  }
  
  /**
   * Update the notification UI with new data
   */
  function updateNotificationUI(notifications, unreadCount) {
    // Update notification dot
    if (notificationDot) {
      if (unreadCount > 0) {
        notificationDot.textContent = unreadCount > 9 ? '9+' : unreadCount;
        notificationDot.style.display = 'flex';
      } else {
        notificationDot.style.display = 'none';
      }
    }
    
    // Update notification list
    if (notificationList) {
      if (notifications.length === 0) {
        notificationList.innerHTML = '<div class="no-notifications">No notifications</div>';
      } else {
        let html = '';
        notifications.forEach(notification => {
          const readClass = notification.is_read ? 'read' : 'unread';
          const date = new Date(notification.created_at);
          const formattedDate = date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
          });
          
          html += `
            <div class="notification-item ${readClass}" data-id="${notification.id}" data-link="${notification.link || ''}">
              <div class="notification-content">
                <p>${notification.message}</p>
                <span class="notification-time">${formattedDate}</span>
              </div>
              <button class="notification-dismiss" data-id="${notification.id}">×</button>
            </div>
          `;
        });
        
        notificationList.innerHTML = html;
      }
    }
  }
  
  /**
   * Mark notifications as seen when dropdown is opened
   */
  function markNotificationsAsSeen() {
    fetch('mark_notifications_seen.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update UI to reflect seen status
        document.querySelectorAll('.notification-item.unread').forEach(item => {
          item.classList.add('seen');
        });
      }
    })
    .catch(error => console.error('Error marking notifications as seen:', error));
  }
  
  /**
   * Mark all notifications as read
   */
  function markAllNotificationsAsRead() {
    fetch('mark_all_notifications_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update UI to reflect read status
        document.querySelectorAll('.notification-item').forEach(item => {
          item.classList.add('read');
          item.classList.remove('unread');
        });
        
        // Update notification dot
        if (notificationDot) {
          notificationDot.style.display = 'none';
        }
      }
    })
    .catch(error => console.error('Error marking all notifications as read:', error));
  }
  
  /**
   * Mark a single notification as read
   */
  function markNotificationAsRead(notificationId) {
    fetch('mark_notification_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update UI to reflect read status
        const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
        if (notificationItem) {
          notificationItem.classList.add('read');
          notificationItem.classList.remove('unread');
        }
        
        // Update unread count
        fetchNotifications();
      }
    })
    .catch(error => console.error('Error marking notification as read:', error));
  }
  
  /**
   * Dismiss a notification
   */
  function dismissNotification(notificationId) {
    fetch('dismiss_notification.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove notification from UI
        const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
        if (notificationItem) {
          notificationItem.remove();
        }
        
        // If no notifications left, show "No notifications" message
        if (notificationList.children.length === 0) {
          notificationList.innerHTML = '<div class="no-notifications">No notifications</div>';
        }
        
        // Update unread count
        fetchNotifications();
      }
    })
    .catch(error => console.error('Error dismissing notification:', error));
  }
});
