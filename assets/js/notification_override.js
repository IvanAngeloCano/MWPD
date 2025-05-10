/**
 * Notification Override System - Fixes conflicts between notification systems
 */
(function() {
  // Wait for document to be fully loaded
  document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 Notification Override System activated');
    
    // Store reference to any existing dismissNotification function
    const originalDismissNotification = window.dismissNotification;
    
    // Override the dismissNotification function with our fixed version
    window.dismissNotification = function(id) {
      console.log('💡 Enhanced dismissNotification called with ID:', id);
      
      // Show loading indicator
      const notificationItem = document.querySelector(`.notification-item[data-id="${id}"]`);
      if (notificationItem) {
        const dismissBtn = notificationItem.querySelector('.notification-dismiss');
        if (dismissBtn) dismissBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
      }
      
      // Direct database deletion (bypass all other systems)
      fetch('emergency_delete_notification.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + id
      })
      .then(response => response.text())
      .then(text => {
        console.log('🔍 Raw deletion response:', text);
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('⚠️ JSON parse error:', e);
          // Try direct SQL deletion as last resort
          return fetch('clear_all_notifications.php?action=delete_one&id=' + id)
            .then(r => r.json());
        }
      })
      .then(data => {
        console.log('✅ Deletion result:', data);
        
        if (data && data.success) {
          // Remove notification from UI - force immediate DOM removal
          if (notificationItem) {
            notificationItem.style.opacity = '0';
            setTimeout(() => {
              notificationItem.remove();
              
              // Update notifications display
              const list = document.getElementById('notificationList');
              if (list && (!list.children.length || 
                  (list.children.length === 1 && list.children[0].classList.contains('no-notifications')))) {
                list.innerHTML = '<div class="no-notifications">No notifications</div>';
              }
              
              // Refresh notification count
              const dot = document.getElementById('notificationDot');
              if (dot) {
                if (!data.unread_count || data.unread_count === 0) {
                  dot.style.display = 'none';
                } else {
                  dot.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                }
              }
            }, 300);
          }
        } else {
          // Reset the button if deletion failed
          console.error('❌ Deletion failed:', data?.message || 'Unknown error');
          if (notificationItem) {
            const dismissBtn = notificationItem.querySelector('.notification-dismiss');
            if (dismissBtn) dismissBtn.innerHTML = '×';
          }
        }
      })
      .catch(error => {
        console.error('⚠️ Error in notification deletion:', error);
        if (notificationItem) {
          const dismissBtn = notificationItem.querySelector('.notification-dismiss');
          if (dismissBtn) dismissBtn.innerHTML = '×';
        }
      });
    };
    
    // Re-attach click handlers for notification dismiss buttons
    document.querySelectorAll('.notification-dismiss').forEach(button => {
      // Remove existing handlers to prevent duplicates
      const newButton = button.cloneNode(true);
      button.parentNode.replaceChild(newButton, button);
      
      // Add our enhanced handler
      newButton.addEventListener('click', function(e) {
        e.stopPropagation();
        const id = this.getAttribute('data-id');
        if (id) {
          dismissNotification(id);
        }
      });
    });
    
    console.log('✅ Notification Override System initialized');
  });
})();
