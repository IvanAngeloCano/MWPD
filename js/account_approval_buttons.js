/**
 * Direct handlers for account approval buttons
 * This script fixes the approve and deny buttons in the account_dashboard.php approvals tab
 */

// Direct form submission handlers for Approve/Deny buttons in the table
function handleDirectApproval(approvalId) {
  // Create FormData for approval
  const formData = new FormData();
  formData.append('action', 'approve');
  formData.append('approval_id', approvalId);
  formData.append('no_redirect', '1');
  
  // Show a loading overlay
  const loadingOverlay = document.createElement('div');
  loadingOverlay.style.position = 'fixed';
  loadingOverlay.style.top = '0';
  loadingOverlay.style.left = '0';
  loadingOverlay.style.width = '100%';
  loadingOverlay.style.height = '100%';
  loadingOverlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
  loadingOverlay.style.display = 'flex';
  loadingOverlay.style.alignItems = 'center';
  loadingOverlay.style.justifyContent = 'center';
  loadingOverlay.style.zIndex = '9999';
  
  const loadingSpinner = document.createElement('div');
  loadingSpinner.innerHTML = '<i class="fas fa-spinner fa-spin" style="color: white; font-size: 48px;"></i>';
  loadingOverlay.appendChild(loadingSpinner);
  document.body.appendChild(loadingOverlay);
  
  // Send AJAX request
  fetch('process_account_approval.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    // Remove loading overlay
    document.body.removeChild(loadingOverlay);
    
    if (data && data.success) {
      // Show success message
      alert(data.message || 'Account approved successfully!');
      
      // Reload the page after a delay
      setTimeout(function() {
        window.location.reload();
      }, 500);
    } else {
      // Show error message
      alert(data.message || 'An error occurred during account approval.');
    }
  })
  .catch(error => {
    // Remove loading overlay
    document.body.removeChild(loadingOverlay);
    
    console.error('Error:', error);
    alert('An error occurred during account approval.');
  });
}

function handleDirectDenial(approvalId) {
  // Ask for reason using a prompt
  const reason = prompt('Please enter a reason for denying this account request:', '');
  if (reason === null) {
    // User canceled
    return;
  }
  
  if (reason.trim() === '') {
    alert('A reason is required to deny an account request.');
    return;
  }
  
  // Create FormData for denial
  const formData = new FormData();
  formData.append('action', 'reject');
  formData.append('approval_id', approvalId);
  formData.append('rejection_reason', reason);
  formData.append('no_redirect', '1');
  
  // Show a loading overlay
  const loadingOverlay = document.createElement('div');
  loadingOverlay.style.position = 'fixed';
  loadingOverlay.style.top = '0';
  loadingOverlay.style.left = '0';
  loadingOverlay.style.width = '100%';
  loadingOverlay.style.height = '100%';
  loadingOverlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
  loadingOverlay.style.display = 'flex';
  loadingOverlay.style.alignItems = 'center';
  loadingOverlay.style.justifyContent = 'center';
  loadingOverlay.style.zIndex = '9999';
  
  const loadingSpinner = document.createElement('div');
  loadingSpinner.innerHTML = '<i class="fas fa-spinner fa-spin" style="color: white; font-size: 48px;"></i>';
  loadingOverlay.appendChild(loadingSpinner);
  document.body.appendChild(loadingOverlay);
  
  // Send AJAX request
  fetch('process_account_approval.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    // Remove loading overlay
    document.body.removeChild(loadingOverlay);
    
    if (data && data.success) {
      // Show success message
      alert(data.message || 'Account denied successfully!');
      
      // Reload the page after a delay
      setTimeout(function() {
        window.location.reload();
      }, 500);
    } else {
      // Show error message
      alert(data.message || 'An error occurred during account denial.');
    }
  })
  .catch(error => {
    // Remove loading overlay
    document.body.removeChild(loadingOverlay);
    
    console.error('Error:', error);
    alert('An error occurred during account denial.');
  });
}
