<?php
session_start();
// Only show loader if just logged in
if (!isset($_SESSION['show_loader']) || !$_SESSION['show_loader']) {
    header('Location: dashboard.php');
    exit();
}
// Remove loader flag so it only shows once
unset($_SESSION['show_loader']);
?>
<!DOCTYPE html>
<html lang="en" class="loader-page">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="loader-page">
    <div class="loader-overlay">
        <img src="assets/images/DMW Logo.png" alt="MWPD Logo" class="loader-logo">
        <div class="loader"></div>
        <div class="loader-text">Welcome to MWPD Filing System</div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 1500); // 1.5 seconds
    </script>
</body>
</html>
