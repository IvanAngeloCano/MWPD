<?php
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
      <section class="direct-hire-wrapper">
        <!-- Top Section -->
        <div class="direct-hire-top">
          <div class="tabs">
            <button class="tab active">Professional</button>
            <button class="tab">Household</button>
            <button class="tab">Denied</button>
          </div>

          <div class="controls">
            <input type="text" placeholder="Search..." class="search-bar">
            <button class="btn filter-btn"><i class="fa fa-filter"></i> Filter</button>
            <a href="direct_hire_add.php">
              <button class="btn add-btn"><i class="fa fa-plus"></i> Add New Record</button>
            </a>
          </div>

          <div class="table-footer">
            <span class="results-count">Showing 1-10 out of 100 results</span>
            <label>
              Rows per page:
              <input type="number" min="1" class="rows-input" value="10">
            </label>
          </div>
        </div>

        <!-- Middle Section -->
        <div class="direct-hire-table">
          <table>
            <thead>
              <tr>
                <th>No.</th>
                <th>Control No.</th>
                <th>Name</th>
                <th>Jobsite</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>1</td>
                <td>DH-001</td>
                <td>Juan Dela Cruz</td>
                <td>Dubai</td>
                <td><span class="status approved">Approved</span></td>
                <td class="action-icons">
                  <i class="fa fa-eye"></i>
                  <i class="fa fa-edit"></i>
                  <i class="fa fa-trash-alt"></i>
                </td>
              </tr>
              <!-- Add more rows as needed -->
            </tbody>
          </table>
        </div>

        <!-- Bottom Section -->
        <div class="direct-hire-bottom">
          <div class="pagination">
            <button class="prev-btn">
              <i class="fa fa-chevron-left"></i> Previous
            </button>

            <button class="page active">1</button>
            <button class="page">2</button>
            <button class="page">3</button>
            <span>...</span>
            <button class="page">11</button>

            <button class="next-btn">
              Next <i class="fa fa-chevron-right"></i>
            </button>
          </div>


          <div class="go-to-page">
            <label>Go to Page:</label>
            <input type="number" min="1">
            <button class="btn go-btn">Go</button>
          </div>
        </div>
      </section>



    </main>
  </div>
</div>