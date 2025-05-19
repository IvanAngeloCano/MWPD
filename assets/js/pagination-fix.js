/**
 * MWPD Pagination Fixes
 * Addresses common pagination issues across all modules:
 * - Makes reset buttons work properly
 * - Makes row input automatically submit on change
 * - Properly hides prev/next buttons on first/last pages
 */
document.addEventListener('DOMContentLoaded', function() {
  // Fix for rows per page input and reset button
  const rowsInput = document.getElementById('rowsInput');
  const resetRowsBtn = document.getElementById('resetRowsBtn');
  const rowsPerPageForm = document.getElementById('rowsPerPageForm');

  // Make input changes trigger form submission
  if (rowsInput) {
    rowsInput.addEventListener('change', function() {
      if (rowsPerPageForm) {
        rowsPerPageForm.submit();
      }
    });
  }

  // Make reset button work properly
  if (resetRowsBtn) {
    resetRowsBtn.addEventListener('click', function() {
      if (rowsInput) {
        rowsInput.value = 8; // Default value
        if (rowsPerPageForm) {
          rowsPerPageForm.submit();
        }
      }
    });
  }

  // Fix pagination visibility
  const prevBtn = document.querySelector('.pagination .prev-btn');
  const nextBtn = document.querySelector('.pagination .next-btn');
  
  // Get current page info from page elements
  const activePage = document.querySelector('.pagination .page.active');
  const allPages = document.querySelectorAll('.pagination .page');
  
  if (allPages.length > 0) {
    // Check if we're on the first page
    if (activePage && activePage.textContent.trim() === '1') {
      if (prevBtn) {
        prevBtn.style.display = 'none';
      }
    }
    
    // Check if we're on the last page
    if (activePage && allPages.length > 0) {
      const lastPage = allPages[allPages.length - 1];
      if (activePage.textContent.trim() === lastPage.textContent.trim()) {
        if (nextBtn) {
          nextBtn.style.display = 'none';
        }
      }
    }
  }
  
  // Style reset buttons consistently across all pages
  const allResetButtons = document.querySelectorAll('[id^="resetRowsBtn"]');
  allResetButtons.forEach(function(btn) {
    btn.style.backgroundColor = '#0d6efd';
    btn.style.color = '#fff';
    btn.style.borderRadius = '6px';
    btn.style.padding = '3px 10px';
    btn.style.border = 'none';
    btn.style.cursor = 'pointer';
  });
});
