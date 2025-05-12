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
        const link = notificationItem.getAttribute('data-link');
        
        // Force marking as read before any redirection
        if (!isRead) {
          // Add read class immediately for visual feedback
          notificationItem.classList.add('read');
          notificationItem.classList.remove('unread');
          
          if (link) {
            // Create a form element to submit directly to mark_notification_read.php with redirect info
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'mark_notification_read.php';
            form.style.display = 'none';
            
            // Add notification ID as input
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = notificationId;
            form.appendChild(idInput);
            
            // Add redirect URL as input
            const redirectInput = document.createElement('input');
            redirectInput.type = 'hidden';
            redirectInput.name = 'redirect_url';
            redirectInput.value = link;
            form.appendChild(redirectInput);
            
            // Append form to body and submit it
            document.body.appendChild(form);
            form.submit();
          } else {
            // Just mark as read, no redirect
            markNotificationAsRead(notificationId);
          }
        } else if (link) {
          // Already read, just redirect
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
  function markNotificationAsRead(notificationId, redirectAfter = false, redirectUrl = '') {
    console.log(`Marking notification ${notificationId} as read, redirectAfter=${redirectAfter}, redirectUrl=${redirectUrl}`);
    
    // Create a loading indicator
    const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
    if (notificationItem) {
      notificationItem.classList.add('loading');
      const loadingIndicator = document.createElement('div');
      loadingIndicator.className = 'notification-loading';
      loadingIndicator.innerHTML = '<div class="spinner"></div>';
      notificationItem.appendChild(loadingIndicator);
    }
    
    fetch('mark_notification_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `id=${notificationId}`
    })
    .then(response => {
      console.log('Got response from mark_notification_read.php:', response.status);
      return response.json();
    })
    .then(data => {
      console.log('Mark as read response data:', data);
      
      if (data.success) {
        // Update UI to reflect read status
        if (notificationItem) {
          notificationItem.classList.add('read');
          notificationItem.classList.remove('unread');
          notificationItem.classList.remove('loading');
          
          // Remove loading indicator
          const loadingIndicator = notificationItem.querySelector('.notification-loading');
          if (loadingIndicator) {
            notificationItem.removeChild(loadingIndicator);
          }
        }
        
        // Update unread count (don't wait for this to complete before redirecting)
        fetchNotifications();
        
        // Redirect if needed - wait a short time to ensure the UI updates are visible
        if (redirectAfter && redirectUrl) {
          console.log(`Redirecting to ${redirectUrl}`);
          setTimeout(() => {
            window.location.href = redirectUrl;
          }, 100);
        }
      }
    })
    .catch(error => {
      console.error('Error marking notification as read:', error);
      // Remove loading state on error
      if (notificationItem) {
        notificationItem.classList.remove('loading');
        const loadingIndicator = notificationItem.querySelector('.notification-loading');
        if (loadingIndicator) {
          notificationItem.removeChild(loadingIndicator);
        }
      }
    });
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
