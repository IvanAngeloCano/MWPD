<?php
$pageTitle = "Add New Record - Direct Hire";
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

    $pageTitle = 'Add New Record to Direct Hire';
    include '_header.php';
    ?>

    <main class="main-content">
      <div class="add-record-wrapper">
        <!-- Tabs -->
        <div class="record-tabs">
          <h2>Adding records to:</h2>
          <button class="tab active">Professional</button>
          <button class="tab">Household</button>
        </div>

        <!-- Form Section -->
        <form class="record-form">
          <div class="form-grid">
            <label>Control No.<input type="text" name="control_no"></label>
            <label>Name<input type="text" name="name"></label>
            <label>Jobsite<input type="text" name="jobsite"></label>
            <label>Evaluated<input type="text" name="evaluated"></label>
            <label>For Confirmation<input type="text" name="for_confirmation"></label>
            <label>Emailed to DHAD<input type="text" name="emailed_to_dhad"></label>
            <label>Received from DHAD<input type="text" name="received_from_dhad"></label>
            <label>Evaluator<input type="text" name="evaluator"></label>
            <label>Note<textarea name="note"></textarea></label>
          </div>

          <!-- File Upload Section -->
          <div class="file-upload-section">
            <div class="upload-box">
              <div class="upload-placeholder">
                <i class="fa fa-cloud-upload-alt"></i>
                <p>Drag files here or <button class="browse-btn">Browse</button></p>
              </div>
            </div>

            <div class="uploaded-files">
              <h3>Uploaded</h3>
              <div class="file-item">
                <span class="drag-handle">☰</span>
                <span class="file-name">passport.png</span>
                <span class="file-size">1.2 MB</span>
                <button class="delete-file"><i class="fa fa-trash"></i></button>
              </div>
              <!-- more file items here -->
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="form-actions">
            <button class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
            <button class="btn btn-outline-primary"><i class="fa fa-plus"></i> Save and Add Another</button>
            <button type="reset" class="btn btn-reset">
              <i class="fa fa-undo"></i> Reset
            </button>
            <a href="direct_hire.php" class="btn btn-cancel">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </form>
      </div>


    </main>
  </div>
</div>