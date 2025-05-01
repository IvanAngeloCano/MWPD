<?php
include 'session.php';
include 'connection.php'; // Your PDO connection file
$pageTitle = "Information Sheet - MWPD Filing System";
include '_head.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $fileName = $_FILES['file']['name'];
    $fileData = file_get_contents($_FILES['file']['tmp_name']);

    $stmt = $pdo->prepare("INSERT INTO documents (file_name, file_data) VALUES (?, ?)");
    $stmt->execute([$fileName, $fileData]);

    echo "<script>alert('File uploaded successfully!');</script>";
}

// Handle file download
if (isset($_GET['download_id'])) {
    $id = (int)$_GET['download_id'];

    $stmt = $pdo->prepare("SELECT file_name, file_data FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();

    if ($file) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $file['file_name'] . '"');
        header('Content-Length: ' . strlen($file['file_data']));
        echo $file['file_data'];
        exit;
    } else {
        echo "<script>alert('File not found.');</script>";
    }
}
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    // Get current filename like 'dashboard-eme.php'
    $currentFile = basename($_SERVER['PHP_SELF']);
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', $fileWithoutExtension));
    include '_header.php';
    ?>

    <main class="main-content">
      <div class="container">
        <!-- Upload Form -->
        <form method="post" enctype="multipart/form-data">
          <button type="button" onclick="document.getElementById('fileInput').click()">Upload DOCX</button>
          <input type="file" id="fileInput" name="file" style="display:none;" accept=".docx" onchange="this.form.submit()">
        </form>

        <!-- Uploaded Files List -->
        <h3 class="mt-4">Uploaded Files</h3>
        <ul>
          <?php
          $stmt = $pdo->query("SELECT id, file_name FROM documents ORDER BY id DESC");
          while ($row = $stmt->fetch()) {
              echo '<li><a href="?download_id=' . $row['id'] . '">' . htmlspecialchars($row['file_name']) . '</a></li>';
          }
          ?>
        </ul>
      </div>
    </main>

  </div>
</div>
