<header class="header">
  <div class="header-left">
    <h1><?= isset($pageTitle) ? $pageTitle : 'Page' ?></h1>
  </div>

  <div class="header-right">
    <button class="quick-add">+ Quick Add</button>

    <div class="notif-icon">
      <i class="fa fa-bell"></i>
      <span class="notif-dot"></span>
    </div>

    <div class="user-profile">
      <div class="profile-icon">
        <i class="fa fa-user-circle"></i>
      </div>
      <div class="profile-info">
        <span>Juan Dela Cruz</span>
        <span>Division Head</span>
      </div>
    </div>
  </div>
</header>


<div id="quickAddModal" class="quick-add-modal hidden">
  <div class="modal-content">
    <h2>Select Process</h2>
    <div class="quick-add-cards">
      <a href="direct_hire_form.php" class="quick-card">
        <i class="fa fa-briefcase"></i>
        <span>Direct Hire</span>
      </a>
      <a href="balik_manggagawa_form.php" class="quick-card">
        <i class="fa fa-sign-in-alt"></i>
        <span>Balik Manggagawa</span>
      </a>
      <a href="gov_to_gov_form.php" class="quick-card">
        <i class="fa fa-university"></i>
        <span>Gov-to-Gov</span>
      </a>
      <a href="job_fair_form.php" class="quick-card">
        <i class="fa fa-clipboard-list"></i>
        <span>Job Fairs</span>
      </a>
    </div>
    <button class="modal-close" onclick="closeModal()">Close</button>
  </div>
</div>


<script>
  const quickAddBtn = document.querySelector('.quick-add');
  const modal = document.getElementById('quickAddModal');

  quickAddBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
  });

  function closeModal() {
    modal.classList.add('hidden');
  }

  // Optional: Close modal on outside click
  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      closeModal();
    }
  });
</script>