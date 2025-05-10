// Gov-to-Gov form submission and double-click fixes

document.addEventListener('DOMContentLoaded', function() {
  console.log('Applying Gov-to-Gov fixes');
  
  // FIX 1: Improve double-click functionality on table rows
  const tbody = document.getElementById('g2g-tbody');
  if (tbody) {
    // Use event delegation for optimal performance
    tbody.addEventListener('dblclick', function(e) {
      // Get the closest tr parent from the clicked element
      const row = e.target.closest('tr');
      if (!row) return; // Not on a row
      
      // Don't activate when clicking on checkboxes or action buttons
      if (e.target.closest('input[type="checkbox"]') || 
          e.target.closest('.action-icons') || 
          e.target.closest('button')) {
        return;
      }
      
      // Get the record ID from the checkbox value
      const checkbox = row.querySelector('input[type="checkbox"]');
      if (checkbox && checkbox.value) {
        // Navigate to view page
        window.location.href = 'gov_to_gov_view.php?id=' + checkbox.value;
      }
    });
    
    // Add a style to indicate rows are clickable
    const style = document.createElement('style');
    style.textContent = '#g2g-tbody tr { cursor: pointer; }';
    document.head.appendChild(style);
  }
  
  // FIX 2: Fix Submit for Approval functionality
  const generateMemoBtn = document.getElementById('generateMemoBtn');
  if (generateMemoBtn) {
    // Replace the button's click handler to fix the submission issues
    generateMemoBtn.addEventListener('click', function(e) {
      // Prevent default behavior
      e.preventDefault();
      console.log('Submit for Approval button clicked (fixed handler)');
      
      // Close the popup form
      const popupForm = document.getElementById('popupMemoForm');
      if (popupForm) {
        popupForm.style.display = 'none';
      }
      
      // Get all selected IDs from hidden container
      const hiddenIdsContainer = document.getElementById('hiddenIdsContainer');
      const selectedIdsInputs = hiddenIdsContainer.querySelectorAll('input[name="selected_ids[]"]');
      const selectedIds = Array.from(selectedIdsInputs).map(input => input.value);
      
      if (selectedIds.length === 0) {
        if (typeof showError === 'function') {
          showError('Please select records to submit for approval');
        } else {
          alert('Please select records to submit for approval');
        }
        return;
      }
      
      console.log('Selected IDs for approval:', selectedIds);
      
      // Show loading indicator
      const loadingIndicator = document.createElement('div');
      loadingIndicator.className = 'loading-indicator';
      loadingIndicator.innerHTML = '<i class="fa fa-spinner fa-spin fa-3x"></i> Processing...';
      loadingIndicator.style.position = 'fixed';
      loadingIndicator.style.top = '50%';
      loadingIndicator.style.left = '50%';
      loadingIndicator.style.transform = 'translate(-50%, -50%)';
      loadingIndicator.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
      loadingIndicator.style.padding = '20px';
      loadingIndicator.style.borderRadius = '5px';
      loadingIndicator.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.3)';
      loadingIndicator.style.zIndex = '9999';
      document.body.appendChild(loadingIndicator);
      
      // Prepare form data
      const formData = new URLSearchParams();
      
      // Get memo reference and employer values
      const memoReferenceInput = document.getElementById('memo_reference');
      const employerInput = document.getElementById('employer');
      const forceUpdateCheckbox = document.getElementById('force_update');
      
      const memoRef = memoReferenceInput ? memoReferenceInput.value : 'Memo-' + new Date().toISOString().slice(0, 10);
      const employer = employerInput ? employerInput.value : 'Not specified';
      const forceUpdate = forceUpdateCheckbox && forceUpdateCheckbox.checked ? '1' : '0';
      
      formData.append('memo_reference', memoRef);
      formData.append('employer', employer);
      formData.append('force_update', forceUpdate);
      
      // Add all selected IDs
      selectedIds.forEach(id => {
        formData.append('selected_ids[]', id);
      });
      
      console.log('Sending data:', formData.toString());
      
      // Send AJAX request
      fetch('g2g_submit_for_approval.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        // Remove loading indicator
        document.body.removeChild(loadingIndicator);
        
        if (data.success) {
          // Show success message
          if (typeof showSuccess === 'function') {
            showSuccess(data.message || 'Records successfully submitted for approval');
          } else {
            alert(data.message || 'Records successfully submitted for approval');
          }
          
          // Redirect after success
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          // Show error message
          if (typeof showError === 'function') {
            showError(data.message || 'Error submitting records for approval');
          } else {
            alert(data.message || 'Error submitting records for approval');
          }
        }
      })
      .catch(error => {
        // Remove loading indicator
        document.body.removeChild(loadingIndicator);
        
        console.error('Error:', error);
        
        // Show error message
        if (typeof showError === 'function') {
          showError('Network error occurred while submitting records');
        } else {
          alert('Network error occurred while submitting records');
        }
      });
    });
  }
});
