<?php
include 'session.php';
$pageTitle = "Direct Hire - MWPD Filing System";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    // Get current filename like 'dashboard-eme.php'
    $currentFile = basename($_SERVER['PHP_SELF']);

    // Remove the file extension
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);

    // Replace dashes with spaces
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', $fileWithoutExtension));

    include '_header.php';
    ?>

    <main class="main-content">
      <!-- Your page content here -->
    </main>
  </div>
</div>