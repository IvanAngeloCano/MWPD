<?php
include 'session.php';
$pageTitle = "Information Sheet - MWPD Filing System";
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
      <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Unde quos vitae aspernatur, commodi illo laborum, voluptatum maiores totam ut suscipit rerum! Ducimus soluta architecto doloribus provident sed inventore delectus dignissimos labore hic reiciendis corrupti in natus perferendis reprehenderit repellat enim rem, impedit fugit placeat aliquid quod nam ratione voluptates odio? Incidunt nobis tempora molestiae deleniti reiciendis. Sed repellat quod aperiam, molestias excepturi impedit adipisci. Enim sapiente nemo saepe explicabo vero at facere perspiciatis ut similique nisi quibusdam fugit ea, impedit sunt, nobis eos incidunt. Quasi expedita placeat, ipsum perferendis, in fuga libero facilis impedit, vel itaque consequuntur. Assumenda distinctio beatae incidunt commodi aspernatur dolorum voluptatibus repellat totam provident sed quidem, reiciendis sapiente error obcaecati optio. Nesciunt distinctio odit beatae perspiciatis voluptates numquam reiciendis ea, eum doloribus mollitia, repellendus deserunt quo expedita ex, esse delectus at cumque! Sunt repellendus natus, soluta hic consectetur qui voluptas officiis nam. Ad distinctio harum magnam dolorem similique vero. Accusantium incidunt sint ex culpa et, adipisci veniam nam aliquam quisquam eligendi, ipsa similique aperiam sequi quam numquam praesentium illum quae quasi veritatis voluptatibus corrupti qui. Quae exercitationem iusto et. Voluptatem amet nesciunt molestias explicabo deserunt culpa consequatur, voluptatibus reprehenderit dicta corporis odio. Recusandae vitae iusto veritatis!</p>
    </main>
  </div>
</div>