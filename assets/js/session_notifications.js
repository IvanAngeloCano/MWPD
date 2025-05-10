/**
 * Session-based Notifications System
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
      fetch('session_mark_all_read.php', {
        method: 'POST'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update UI
          document.querySelectorAll('.notification-item').forEach(item => {
            item.classList.add('read');
            item.classList.remove('unread');
          });
          
          // Hide notification dot
          if (notificationDot) {
            notificationDot.style.display = 'none';
          }
        }
      })
      .catch(error => console.error('Error marking all as read:', error));
    });
  }
  
  // Handle notification clicks
  if (notificationList) {
    notificationList.addEventListener('click', function(e) {
      // Handle dismiss button
      if (e.target.classList.contains('notification-dismiss')) {
        const index = e.target.getAttribute('data-index');
        const item = e.target.closest('.notification-item');
        
        fetch('session_dismiss_notification.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `index=${index}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Remove from UI
            item.remove();
            
            // If no notifications left, show "No notifications" message
            if (notificationList.children.length === 0) {
              notificationList.innerHTML = '<div class="no-notifications">No notifications</div>';
            }
            
            // Update notification dot
            updateNotificationDot(data.unread_count);
          }
        })
        .catch(error => console.error('Error dismissing notification:', error));
        
        e.stopPropagation();
        return;
      }
      
      // Handle notification item click
      const item = e.target.closest('.notification-item');
      if (item && !e.target.classList.contains('notification-dismiss')) {
        const index = item.getAttribute('data-index');
        const link = item.getAttribute('data-link');
        
        // If link is provided, immediately navigate to it
        if (link && link !== '') {
          // Mark as read in the background
          fetch('session_mark_read.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `index=${index}`
          });
          
          // Immediately redirect
          window.location.href = link;
        } else {
          // If no link, just mark as read
          fetch('session_mark_read.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `index=${index}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update UI
              item.classList.add('read');
              item.classList.remove('unread');
              
              // Update notification dot
              updateNotificationDot(data.unread_count);
            }
          })
          .catch(error => console.error('Error marking as read:', error));
        }
      }
    });
  }
  
  // Update notification dot
  function updateNotificationDot(count) {
    if (notificationDot) {
      if (count > 0) {
        notificationDot.textContent = count > 9 ? '9+' : count;
        notificationDot.style.display = 'flex';
      } else {
        notificationDot.style.display = 'none';
      }
    }
  }
});
