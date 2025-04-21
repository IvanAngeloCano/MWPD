<?php 
  $pageTitle = "Login - MWPD Filing System";
  include '_head.php'; 
?>

<body>
  <div class="main-grid">
    <div class="upper-half-bg"></div>
    <div class="lower-half-bg"></div>

    <div class="login-wrapper">
      <div class="login-box">
        <img src="assets\images\DMW Logo.png" alt="DMW Logo" class="dmw-logo">
        <h2>Login to system</h2>
        <form method="POST" action="dashboard.php">
          <div class="username-box">
            <label>Enter your Username</label>
            <div class="input-group">
              <i class="fa fa-user"></i>
              <input type="text" name="username" placeholder="Username" required>
            </div>
          </div>


          <div class="password-box">
            <label>Enter your Password</label>
            <div class="input-group">
              <i class="fa fa-lock"></i>
              <input type="password" name="password" placeholder="Password" id="password" required>
              <i class="fa fa-eye toggle-password" onclick="togglePassword()"></i>
            </div>
          </div>

          <button type="submit" class="login-button text-md">Login</button>
        </form>
      </div>
    </div>

    <div class="info-upper-half">
      <p class="text-md font-medium">Republic of the Philippines</p>
      <p class="text-lg font-semibold">DEPARTMENT OF MIGRANT WORKERS</p>
      <p class="text-md font-medium italic">Kagawaran ng Manggagawang Pandarayuhan</p>
    </div>

    <div class="info-lower-half">
      <p class="text-lg font-semibold text-primary">Migrant Workers Processing Division (MWPD) Filing System</p>
      <p class="text-md text-black">For MWPD staff to efficiently manage, track, and archive migrant worker application records.</p>
    </div>
  </div>


  <img src="assets\images\bagong-pilipinas-logo.png" class="bagong-logo" alt="Bagong Pilipinas">

  <script>
  </script>
</body>

</html>