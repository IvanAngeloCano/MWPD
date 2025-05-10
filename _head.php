<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'MWPD Filing System'; ?></title>
  <link rel="icon" href="assets\images\DMW Logo.png" type="image/x-icon">
  <link rel="stylesheet" href="assets\css\style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Guided Tour CSS -->
  <link rel="stylesheet" href="assets/css/guided-tour.css">
  
  <!-- Notification Fix CSS -->
  <link rel="stylesheet" href="assets/css/notification-fix.css">
  
  <!-- Floating Menu Fix CSS -->
  <link rel="stylesheet" href="assets/css/floating-menu-fix.css">
  
  <!-- Quick Access Menu Fix CSS -->
  <link rel="stylesheet" href="assets/css/quick-access-fix.css">
  
  <!-- FullCalendar CSS -->
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />

  <!-- FullCalendar JS -->
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/session_notifications.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  
  <!-- Set variables for blacklist button -->
  <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
  <script>
    // Set variables for the blacklist button
    var userRole = '<?php echo $_SESSION['role']; ?>';
    
    // Check if blacklist table exists and get count of pending entries
    <?php
    $pendingCount = 0;
    if (strtolower($_SESSION['role']) === 'regional director') {
        try {
            // First check if the blacklist table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
            if ($stmt->rowCount() > 0) {
                // Table exists, get pending count
                $stmt = $pdo->query("SELECT COUNT(*) FROM blacklist WHERE status = 'pending'");
                $pendingCount = $stmt->fetchColumn();
            }
        } catch (Exception $e) {
            // Silently fail, don't show count
            $pendingCount = 0;
        }
    }
    ?>
    var pendingBlacklistCount = <?php echo $pendingCount; ?>;
  </script>
  
  <!-- Blacklist button has been removed -->
  <?php endif; ?>
</head>