<?php
// Include the floating blacklist button if user is logged in
if (isset($_SESSION['user_id'])) {
    include 'blacklist_button.php';
}
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
