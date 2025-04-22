<?php
// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
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
        <span><?= isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : htmlspecialchars($_SESSION['username']) ?></span>
        <span><?= isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'User' ?></span>
      </div>
      <i class="fa fa-caret-down"></i>
    </div>
  </div>
</header>
