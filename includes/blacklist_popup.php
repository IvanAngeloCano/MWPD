<?php
// Blacklist popup component - include this file in any module that needs blacklist checking
?>
<!-- Custom Blacklist Popup (not using Bootstrap modal) -->
<div id="customBlacklistPopup" class="custom-popup">
  <div class="custom-popup-content">
    <div class="custom-popup-header">
      <h3><i class="fa fa-exclamation-triangle"></i> WARNING: BLACKLISTED PERSON</h3>
      <span class="custom-popup-close" onclick="closeCustomPopup()">&times;</span>
    </div>
    <div class="custom-popup-body">
      <div class="blacklist-warning">
        <p><strong>WARNING:</strong> This person is <strong>BLACKLISTED</strong>!</p>
        <p>Processing this individual may violate POEA regulations.</p>
      </div>
      <div id="blacklistMatchDetails" class="blacklist-details">
        <!-- Blacklist match details will be populated here -->
      </div>
    </div>
    <div class="custom-popup-footer">
      <button onclick="closeCustomPopup()" class="popup-btn popup-btn-cancel">Close</button>
    </div>
  </div>
</div>

<!-- CSS for custom popup -->
<style>
.custom-popup {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  overflow: auto;
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from {opacity: 0}
  to {opacity: 1}
}

.custom-popup-content {
  position: relative;
  background-color: #fefefe;
  margin: 10% auto;
  padding: 0;
  border: 1px solid #888;
  width: 500px;
  max-width: 90%;
  box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2), 0 6px 20px 0 rgba(0,0,0,0.19);
  animation: slideDown 0.4s;
}

@keyframes slideDown {
  from {transform: translateY(-300px); opacity: 0}
  to {transform: translateY(0); opacity: 1}
}

.custom-popup-header {
  padding: 12px 16px;
  background-color: #dc3545;
  color: white;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.custom-popup-header h3 {
  margin: 0;
  font-size: 1.25rem;
}

.custom-popup-close {
  color: white;
  float: right;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.custom-popup-body {
  padding: 16px;
}

.blacklist-warning {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
  padding: 12px 15px;
  margin-bottom: 15px;
  border-radius: 4px;
}

.blacklist-details {
  background-color: #f8f9fa;
  border: 1px solid #ddd;
  padding: 15px;
  border-radius: 4px;
}

.custom-popup-footer {
  padding: 12px 16px;
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
  text-align: right;
}

.popup-btn {
  padding: 8px 16px;
  margin-left: 8px;
  border: none;
  cursor: pointer;
  border-radius: 4px;
  font-weight: 500;
}

.popup-btn-cancel {
  background-color: #6c757d;
  color: white;
}

.blacklist-details table {
  width: 100%;
  border-collapse: collapse;
}

.blacklist-details table th,
.blacklist-details table td {
  padding: 8px 12px;
  border: 1px solid #ddd;
  text-align: left;
}

.blacklist-details table th {
  background-color: #f2f2f2;
  width: 120px;
}

.blacklist-badge {
  display: inline-block;
  padding: 3px 7px;
  font-size: 12px;
  font-weight: 700;
  background-color: #dc3545;
  color: white;
  border-radius: 4px;
}

.blacklist-name {
  font-size: 24px;
  font-weight: bold;
  color: #dc3545;
  text-align: center;
  margin-bottom: 15px;
  padding: 10px;
  border: 2px solid #dc3545;
  border-radius: 5px;
  background-color: #f8d7da;
}
</style>

<script>
// Function to close the custom popup
function closeCustomPopup() {
  document.getElementById('customBlacklistPopup').style.display = 'none';
}

// Function to check blacklist and show popup
function checkBlacklistAndShowPopup(nameField, statusDiv) {
  const name = nameField.value.trim();
  if (!name) return;
  
  // Show checking indicator
  if (statusDiv) {
    statusDiv.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    nameField.classList.remove('input-valid', 'input-invalid');
  }
  
  // Call the blacklist check endpoint
  fetch('basic_blacklist_check.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'name=' + encodeURIComponent(name)
  })
  .then(response => response.json())
  .then(data => {
    console.log('Blacklist check result:', data);
    
    if (data.blacklisted) {
      // Person is blacklisted - show immediate browser alert
      console.log('BLACKLISTED PERSON DETECTED!', data.details);
      
      // Visual indicator in the input field
      if (statusDiv) {
        statusDiv.innerHTML = '<i class="fa fa-exclamation-triangle" style="color: red;"></i>';
        nameField.classList.add('input-invalid');
        nameField.classList.remove('input-valid');
        nameField.style.borderColor = 'red';
        nameField.style.borderWidth = '2px';
      }
      
      // Get details from the response
      const details = data.details || {};
      
      // Use the name from the input field if the blacklist record doesn't have a name
      const inputName = nameField.value.trim();
      const displayName = details.name || 
                     (details.first_name ? (details.first_name + ' ' + (details.last_name || '')) : inputName);
      const reason = details.reason || details.remarks || 'Not specified';
      
      // Update the popup header to include the name
      document.querySelector('.custom-popup-header h3').innerHTML = 
        `<i class="fa fa-exclamation-triangle"></i> WARNING: ${displayName} IS BLACKLISTED`;
      
      // Create details table
      const detailsHtml = `
        <div class="blacklist-name">${displayName}</div>
        <table>
          <tr>
            <th>Name:</th>
            <td><strong>${displayName}</strong></td>
          </tr>
          <tr>
            <th>Reason:</th>
            <td>${reason}</td>
          </tr>
          <tr>
            <th>Status:</th>
            <td><span class="blacklist-badge">BLACKLISTED</span></td>
          </tr>
        </table>
      `;
      
      // Update the details in the popup
      document.getElementById('blacklistMatchDetails').innerHTML = detailsHtml;
      
      // Show the custom popup
      document.getElementById('customBlacklistPopup').style.display = 'block';
    } else {
      // Not blacklisted - show green checkmark
      if (statusDiv) {
        statusDiv.innerHTML = '<i class="fa fa-check-circle" style="color: green;"></i>';
        nameField.classList.add('input-valid');
        nameField.classList.remove('input-invalid');
      }
    }
  })
  .catch(error => {
    console.error('Error checking blacklist:', error);
    if (statusDiv) {
      statusDiv.innerHTML = '<i class="fa fa-times-circle" style="color: orange;"></i>';
    }
  });
}
</script>
