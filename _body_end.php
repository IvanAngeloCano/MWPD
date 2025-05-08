<?php
// Blacklist button has been removed and replaced with the floating action menu in dashboard.php
?>

<script>
// Close any modals when clicking outside
document.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.blacklist-dashboard');
    const blacklistBtn = document.getElementById('blacklistBtn');
    
    // Check if clicked outside modal and outside button
    if (blacklistBtn && !blacklistBtn.contains(event.target)) {
        modals.forEach(modal => {
            if (modal.style.display === 'block' && !modal.contains(event.target)) {
                modal.style.display = 'none';
            }
        });
    }
});
</script>
