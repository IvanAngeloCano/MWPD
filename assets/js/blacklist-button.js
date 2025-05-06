// Blacklist Button Injector
document.addEventListener('DOMContentLoaded', function() {
    // Check if user has appropriate role (this relies on a global variable set in PHP)
    if (typeof userRole === 'undefined') {
        // If userRole is not defined, we can't determine if button should be shown
        return;
    }
    
    const allowedRoles = ['staff', 'regional director', 'division head'];
    if (!allowedRoles.includes(userRole.toLowerCase())) {
        return;
    }
    
    // Create the button element
    const button = document.createElement('div');
    button.className = 'blacklist-floating-btn';
    button.id = 'blacklistBtn';
    button.title = 'Blacklist Management';
    
    // Add badge if count exists
    if (typeof pendingBlacklistCount !== 'undefined' && pendingBlacklistCount > 0) {
        const badge = document.createElement('span');
        badge.className = 'blacklist-badge';
        badge.textContent = pendingBlacklistCount;
        button.appendChild(badge);
    }
    
    // Add icon to button
    const icon = document.createElement('i');
    icon.className = 'fas fa-user-slash';
    button.appendChild(icon);
    
    // Add styles
    const style = document.createElement('style');
    style.textContent = `
        .blacklist-floating-btn {
            position: fixed;
            width: 50px;
            height: 50px;
            bottom: 40px;
            left: 40px;
            background-color: rgba(0, 123, 255, 0.7); /* Semi-transparent blue */
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
        
        .blacklist-badge {
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
            font-size: 12px;
            font-weight: bold;
        }
    `;
    
    // Add click handler
    button.addEventListener('click', function() {
        window.location.href = 'blacklist.php';
    });
    
    // Append to document
    document.head.appendChild(style);
    document.body.appendChild(button);
});
