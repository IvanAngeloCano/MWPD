<?php
  // Page title for this listing
  $pageTitle = "Gov-to-Gov – MWPD Filing System";
  include '_head.php';
?>
<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
      // Header title on the page
      $pageTitle = "Gov-to-Gov";
      include '_header.php';
    ?>

    <main class="main-content">
      <section class="gtog-section">
        <!-- Controls: Search, Filter, Add -->
        <div class="gtog-controls">
          <form action="" method="GET" class="search-form">
            <input 
              type="text" 
              name="search" 
              class="search-bar" 
              placeholder="Search…"
            >
            <button type="submit" class="btn search-btn">
              <i class="fa fa-search"></i>
            </button>
          </form>
          <button class="btn filter-btn">
            <i class="fa fa-filter"></i> Filter
          </button>
          <a href="gov_to_gov_add.php" class="btn add-btn">
            <i class="fa fa-plus"></i> Add New Record
          </a>
        </div>

        <!-- Data Table -->
        <div class="gtog-table">
          <table>
            <thead>
              <tr>
                <th>No.</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Sex</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <!-- Example rows -->
              <tr>
                <td>1</td>
                <td>Santos</td>
                <td>Maria</td>
                <td>Delacruz</td>
                <td>F</td>
                <td class="action-icons">
                  <a href="#" title="View"><i class="fa fa-eye"></i></a>
                  <a href="#" title="Edit"><i class="fa fa-edit"></i></a>
                  <a href="#" title="Delete"><i class="fa fa-trash-alt"></i></a>
                </td>
              </tr>
              <tr>
                <td>2</td>
                <td>Dela Cruz</td>
                <td>Juan</td>
                <td>Garcia</td>
                <td>M</td>
                <td class="action-icons">
                  <a href="#" title="View"><i class="fa fa-eye"></i></a>
                  <a href="#" title="Edit"><i class="fa fa-edit"></i></a>
                  <a href="#" title="Delete"><i class="fa fa-trash-alt"></i></a>
                </td>
              </tr>
              <!-- …more rows… -->
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="gtog-pagination">
          <button class="prev-btn" disabled>
            <i class="fa fa-chevron-left"></i> Previous
          </button>
          <a href="#" class="page active">1</a>
          <a href="#" class="page">2</a>
          <span>…</span>
          <a href="#" class="page">5</a>
          <button class="next-btn">
            Next <i class="fa fa-chevron-right"></i>
          </button>
        </div>
      </section>
    </main>
  </div>
</div>
