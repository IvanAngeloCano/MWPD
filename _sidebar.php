<?php
  $currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
  <div class="sidebar-top">
    <div class="logo">
      <img src="assets\images\DMW Logo.png" alt="DMW Logo"/>
      <span>MWPD<br><strong>Filing System</strong></span>
    </div>

    <nav class="nav-links">
      <a href="dashboard.php" class="nav-item <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
        <i class="fa fa-th-large"></i>
        <span>Dashboard</span>
      </a>
      <a href="direct_hire.php" class="nav-item <?= ($currentPage == 'direct_hire.php' || $currentPage == 'direct_hire_add.php') ? 'active' : '' ?>">
        <i class="fa fa-briefcase"></i>
        <span>Direct Hire</span>
      </a>
      <a href="balik_manggagawa.php" class="nav-item <?= ($currentPage == 'balik_manggagawa.php') ? 'active' : '' ?>">
        <i class="fa fa-sign-in-alt"></i>
        <span>Balik Manggagawa</span>
      </a>
      <a href="gov_to_gov.php" class="nav-item <?= ($currentPage == 'gov_to_gov.php') ? 'active' : '' ?>">
        <i class="fa fa-university"></i>
        <span>Gov-To-Gov</span>
      </a>
      <a href="job_fairs.php" class="nav-item <?= ($currentPage == 'job_fairs.php') ? 'active' : '' ?>">
        <i class="fa fa-clipboard-list"></i>
        <span>Job Fairs</span>
      </a>
      <a href="information_sheet.php" class="nav-item <?= ($currentPage == 'information_sheet.php') ? 'active' : '' ?>">
        <i class="fa fa-info-circle"></i>
        <span>Information sheet</span>
      </a>
    </nav>
  </div>

  <div class="logout nav-links">
    <a href="login.php" class="nav-item">
      <i class="fa fa-sign-out-alt logout-icon"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>
