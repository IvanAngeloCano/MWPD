/**
 * Dashboard Scrolling JavaScript Helper - Fixed Version
 * Ensures mouse wheel scrolling works properly in all containers
 */
document.addEventListener('DOMContentLoaded', function() {
  // Get all scrollable containers
  const scrollContainers = document.querySelectorAll('.scrollable-container');
  
  // Add special handling for mouse wheel events
  scrollContainers.forEach(container => {
    container.addEventListener('wheel', function(event) {
      // Only if the container's scrollHeight is greater than its clientHeight
      if (this.scrollHeight > this.clientHeight) {
        const scrollTop = this.scrollTop;
        const scrollHeight = this.scrollHeight;
        const height = this.clientHeight;
        const delta = event.deltaY;
        
        // Check if we're at the boundaries
        const atTop = scrollTop <= 0;
        const atBottom = scrollTop + height >= scrollHeight - 1; // -1 for rounding errors
        
        // If we're not at the top or bottom, or we're scrolling in the right direction
        // when at a boundary, prevent default to allow custom scrolling
        if (
          (atTop && delta > 0) || // At top and scrolling down
          (atBottom && delta < 0) || // At bottom and scrolling up
          (!atTop && !atBottom) // Not at either boundary
        ) {
          event.preventDefault();
          this.scrollTop += delta;
        }
        // Otherwise, let the default browser behavior occur
      }
    }, { passive: false });
  });
  
  // Initialize Bootstrap tooltips
  if (typeof $ !== 'undefined' && typeof $.fn.tooltip !== 'undefined') {
    $('[title]').tooltip({
      trigger: 'hover',
      placement: 'top',
      container: 'body'
    });
  }
});
