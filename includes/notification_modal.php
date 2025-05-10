<!-- Notification Modal System -->
<div id="notificationModal" class="modal">
  <div class="modal-content">
    <div id="notificationHeader" class="modal-header">
      <h3 id="notificationTitle">Notification</h3>
      <button class="modal-close" onclick="closeNotificationModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p id="notificationMessage"></p>
      <div class="modal-actions">
        <button class="btn btn-primary" onclick="closeNotificationModal()">OK</button>
      </div>
    </div>
  </div>
</div>

<style>
  /* Modal styling for notification */
  #notificationModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
  }
  
  #notificationModal .modal-content {
    position: relative;
    background-color: #fff;
    width: 400px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    overflow: visible;
  }
  
  #notificationModal .modal-header {
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    overflow: visible;
  }
  
  #notificationModal .modal-body {
    padding: 20px;
    text-align: center;
  }
  
  #notificationModal .modal-actions {
    margin-top: 20px;
    display: flex;
    justify-content: center;
  }
  
  #notificationModal .btn {
    padding: 8px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    color: white;
  }
  
  #notificationModal .btn-primary {
    background-color: #007bff;
  }
  
  #notificationModal .btn-primary:hover {
    background-color: #0069d9;
  }
  
  #notificationModal .modal-close {
    color: white;
    font-size: 24px;
    background: none;
    border: none;
    cursor: pointer;
  }
</style>

<script>
  // Global notification functions
  function showNotification(title, message, type = 'info') {
    document.getElementById('notificationTitle').textContent = title;
    document.getElementById('notificationMessage').textContent = message;
    
    // Set header color based on notification type
    let headerColor;
    switch(type) {
      case 'success':
        headerColor = '#28a745'; // Green
        break;
      case 'error':
        headerColor = '#dc3545'; // Red
        break;
      case 'warning':
        headerColor = '#ffc107'; // Yellow
        break;
      default:
        headerColor = '#007bff'; // Blue (info)
    }
    
    document.getElementById('notificationHeader').style.backgroundColor = headerColor;
    
    // Show the modal
    document.getElementById('notificationModal').style.display = 'flex';
    
    // Return a promise that resolves when the modal is closed
    return new Promise(resolve => {
      const okButton = document.querySelector('#notificationModal .btn-primary');
      const closeButton = document.querySelector('#notificationModal .modal-close');
      
      const closeHandler = () => {
        document.getElementById('notificationModal').style.display = 'none';
        okButton.removeEventListener('click', closeHandler);
        closeButton.removeEventListener('click', closeHandler);
        resolve();
      };
      
      okButton.addEventListener('click', closeHandler);
      closeButton.addEventListener('click', closeHandler);
    });
  }
  
  function closeNotificationModal() {
    document.getElementById('notificationModal').style.display = 'none';
  }
  
  // Override native alert
  window.originalAlert = window.alert;
  window.alert = function(message) {
    showNotification('Alert', message, 'info');
  };
  
  // Helper functions for different notification types
  function showSuccess(message, title = 'Success') {
    return showNotification(title, message, 'success');
  }
  
  function showError(message, title = 'Error') {
    return showNotification(title, message, 'error');
  }
  
  function showWarning(message, title = 'Warning') {
    return showNotification(title, message, 'warning');
  }
  
  function showInfo(message, title = 'Information') {
    return showNotification(title, message, 'info');
  }
</script>
