// Script to create and inject a floating transparent blue circle button only on dashboard page
document.addEventListener('DOMContentLoaded', function() {
    // Check if this is the dashboard page
    const currentPage = window.location.pathname.split('/').pop();
    if (currentPage !== 'dashboard.php') {
        return; // Only show on dashboard page
    }
    
    // Only show button for certain roles
    if (typeof userRole === 'undefined' || !['staff', 'regional director', 'division head'].includes(userRole.toLowerCase())) {
        return; // Exit if role not set or not authorized
    }
    
    // Don't create button if it already exists
    if (document.getElementById('blacklistFloatingBtn')) {
        return;
    }
    
    // Create the floating button container
    const buttonContainer = document.createElement('div');
    buttonContainer.id = 'blacklistFloatingBtn';
    buttonContainer.className = 'blacklist-floating-btn';
    buttonContainer.title = 'Blacklist Management';
    
    // Create the icon inside the button
    const icon = document.createElement('i');
    icon.className = 'fas fa-user-slash';
    icon.style.fontSize = '18px'; // Increased icon size
    buttonContainer.appendChild(icon);
    
    // Add notification badge if needed
    if (typeof pendingBlacklistCount !== 'undefined' && pendingBlacklistCount > 0) {
        const badge = document.createElement('span');
        badge.className = 'blacklist-badge';
        badge.textContent = pendingBlacklistCount;
        buttonContainer.appendChild(badge);
    }
    
    // Add CSS styles
    const styles = document.createElement('style');
    styles.textContent = `
        .blacklist-floating-btn {
            position: fixed;
            width: 45px;
            height: 45px;
            bottom: 20px;
            right: 20px;
            background-color: rgba(0, 123, 255, 0.5); /* Less transparent blue */
            color: white;
            border-radius: 50%;
            text-align: center;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .blacklist-floating-btn:hover {
            background-color: rgba(0, 123, 255, 0.9);
            transform: scale(1.1);
        }
        
        .blacklist-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 14px;
            height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: bold;
        }
    `;
    
    // Add click handler to navigate to blacklist page
    buttonContainer.addEventListener('click', function() {
        window.location.href = 'blacklist.php';
    });
    
    // Add elements to the page
    document.head.appendChild(styles);
    document.body.appendChild(buttonContainer);
});
