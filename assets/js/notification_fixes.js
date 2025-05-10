
// Fix notification system issues
document.addEventListener('DOMContentLoaded', function() {
    // 1. Fix dismiss buttons if they're not working
    const dismissButtons = document.querySelectorAll('.notification-dismiss');
    if (dismissButtons) {
        dismissButtons.forEach(button => {
            // Remove existing event handlers
            const clone = button.cloneNode(true);
            button.parentNode.replaceChild(clone, button);
            
            // Add new event handler
            clone.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.getAttribute('data-id');
                if (id) {
                    console.log('Dismissing notification ID:', id);
                    dismissNotification(id);
                }
            });
        });
    }
    
    // 2. Fix notification onClick if needed
    const notificationItems = document.querySelectorAll('.notification-item');
    if (notificationItems) {
        notificationItems.forEach(item => {
            // Make sure clicking on notification content works
            item.style.cursor = 'pointer';
            
            // Fix links if they exist
            const link = item.getAttribute('data-link');
            if (link) {
                item.addEventListener('click', function(e) {
                    if (!e.target.classList.contains('notification-dismiss')) {
                        window.location.href = link;
                    }
                });
            }
        });
    }
});
