
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
      <i class="fa fa-caret-down"></i>
    </div>
  </div>
</header>
