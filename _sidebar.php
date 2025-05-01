<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
  <div class="sidebar-top">
    <div class="logo">
      <img src="assets\images\DMW Logo.png" alt="DMW Logo" />
      <span>MWPD<br><strong>Filing System</strong></span>
    </div>

    <nav class="nav-links">
      <a href="dashboard.php" class="nav-item <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>">
        <i class="fa fa-th-large"></i>
        <span>Dashboard</span>
      </a>
      <a href="direct_hire.php" class="nav-item <?= ($currentPage == 'direct_hire.php') ? 'active' : '' ?>">
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
      
      <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'div head' || $_SESSION['role'] === 'Division Head')): ?>
      <a href="accounts.php" class="nav-item <?= ($currentPage == 'accounts.php') ? 'active' : '' ?>">
        <i class="fa fa-users-cog"></i>
        <span>Accounts</span>
      </a>
      <?php endif; ?>
      
      <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'regional director' || $_SESSION['role'] === 'Regional Director')): ?>
      <a href="approval_view_simple.php" class="nav-item <?= ($currentPage == 'approval_view_simple.php') ? 'active' : '' ?>">
        <i class="fa fa-check-circle"></i>
        <span>Approval</span>
      </a>
      <a href="g2g_pending_approvals.php" class="nav-item <?= ($currentPage == 'g2g_pending_approvals.php') ? 'active' : '' ?>">
        <i class="fa fa-university"></i>
        <span>Gov-to-Gov Approvals</span>
      </a>
      <?php endif; ?>
    </nav>
  </div>

  <div class="logout nav-links">
    <a href="logout.php" class="nav-item">
      <i class="fa fa-sign-out-alt logout-icon"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>