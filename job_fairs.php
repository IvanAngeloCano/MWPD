<?php
include 'session.php';
require_once 'connection.php';

// Handle pagination and set defaults
$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 3;

// Handle search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get total record count first to determine valid page numbers
try {
    // Build the query based on search and filters
    $where_conditions = [];
    $count_params = [];
    
    if (!empty($search_query)) {
        $where_conditions[] = "(venue LIKE ? OR note LIKE ?)";
        $count_params[] = "%$search_query%";
        $count_params[] = "%$search_query%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $count_params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $count_sql = "SELECT COUNT(*) FROM job_fairs $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $rows_per_page);
    
    // Now set the page number, ensuring it's within valid range
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
    } elseif ($page < 1) {
        $page = 1;
    }
} catch (PDOException $e) {
    $total_records = 0;
    $total_pages = 0;
    $page = 1;
}

$offset = ($page - 1) * $rows_per_page;

try {
    // Build the query based on search and filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search_query)) {
        $where_conditions[] = "(venue LIKE ? OR note LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get total record count
    $count_sql = "SELECT COUNT(*) FROM job_fairs $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $rows_per_page);
    
    // Get the records
    $sql = "SELECT * FROM job_fairs $where_clause ORDER BY date ASC LIMIT ?, ?";
    $stmt = $pdo->prepare($sql);
    
    // Add pagination parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
    $stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $rows_per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    $job_fairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $job_fairs = [];
    $total_records = 0;
    $total_pages = 0;
}

$pageTitle = "Job Fairs";
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
      <div class="job-fair-wrapper">
        <!-- Top Section with heading and controls -->
        <div class="job-fair-header">
          <h1>DMW Job Fairs Monitoring</h1>
          <p>List of scheduled job fairs across different regions</p>
          
          <div class="contact-info">
            <p><strong>For concerns, you may contact the DMW Regional Office IV-A CALABARZON</strong></p>
            <p>Mobile & Telephone No.: 0962 671 9976 or (049) 548 1375</p>
            <p>Email address: <a href="mailto:dmw4a.processing@dmw.gov.ph">dmw4a.processing@dmw.gov.ph</a></p>
          </div>
        </div>
        
        <!-- Controls Section -->
        <div class="job-fair-controls">
          <div class="search-filter">
            <form action="" method="GET" class="search-form">
              <input type="text" name="search" placeholder="Search job fairs..." 
                     class="search-bar" value="<?= htmlspecialchars($search_query) ?>">
              <button type="submit" class="btn search-btn"><i class="fa fa-search"></i></button>
            </form>
            
            <div class="filter-group">
              <form action="" method="GET">
                <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <?php endif; ?>
                
                <select name="status" onchange="this.form.submit()">
                  <option value="">All Statuses</option>
                  <option value="planned" <?= $status_filter === 'planned' ? 'selected' : '' ?>>Planned</option>
                  <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                  <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                  <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
              </form>
            </div>
          </div>
          
          <div class="action-buttons">
            <a href="job_fair_add.php" class="btn btn-primary">
              <i class="fa fa-plus"></i> Add New Job Fair
            </a>
          </div>
        </div>
        
        <!-- Controls Section with Rows Per Page -->
        <div class="job-fair-top">
          <div class="table-footer">
            <span class="results-count">
              Showing <?= min(($offset + 1), $total_records) ?>-<?= min(($offset + $rows_per_page), $total_records) ?> out of <?= $total_records ?> results
            </span>
            <form action="" method="GET" id="rowsPerPageForm" style="display:inline-block;">
              <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
              <?php endif; ?>
              <?php if (!empty($status_filter)): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
              <?php endif; ?>
              <input type="hidden" name="page" value="<?= $page ?>">
              <label>
                Rows per page:
                <input type="number" min="1" name="rows" class="rows-input" value="<?= $rows_per_page ?>" id="rowsInput">
              </label>
              <button type="button" class="btn go-btn reset-btn" id="resetRowsBtn" style="background-color:#007bff;color:#fff;border:none;border-radius:16px;padding:3px 10px;">Reset</button>
            </form>
          </div>
        </div>
        
        <!-- Job Fairs Table -->
        <div class="direct-hire-table">
          <table>
            <thead>
              <tr>
                <th>No.</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Contact Information</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($job_fairs)): ?>
              <tr>
                <td colspan="6" class="no-records">No job fairs found</td>
              </tr>
              <?php else: ?>
                <?php foreach ($job_fairs as $index => $fair): ?>
                <tr>
                  <td><?= $offset + $index + 1 ?></td>
                  <td><?= date('F d, Y', strtotime($fair['date'])) ?></td>
                  <td><?= htmlspecialchars($fair['venue']) ?></td>
                  <td>
                    <?php if (!empty($fair['contact_info'])): ?>
                      <?= htmlspecialchars($fair['contact_info']) ?>
                    <?php else: ?>
                      <span class="text-muted">Not provided</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="status <?= strtolower($fair['status']) ?>">
                      <?= ucfirst(htmlspecialchars($fair['status'])) ?>
                    </span>
                  </td>
                  <td class="action-icons">
                    <a href="job_fair_view.php?id=<?= $fair['id'] ?>" title="View Job Fair">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="job_fair_edit.php?id=<?= $fair['id'] ?>" title="Edit Job Fair">
                      <i class="fa fa-edit"></i>
                    </a>
                    <a href="javascript:void(0)" onclick="confirmDelete(<?= $fair['id'] ?>)" title="Delete Job Fair">
                      <i class="fa fa-trash-alt"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Bottom Section with Pagination and Go To Page -->
        <div class="direct-hire-bottom">
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?page=<?= ($page-1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" class="prev-btn">
                <i class="fa fa-chevron-left"></i> Previous
              </a>
            <?php else: ?>
              <button class="prev-btn" disabled>
                <i class="fa fa-chevron-left"></i> Previous
              </button>
            <?php endif; ?>

            <?php
              // Calculate start and end page for 3-page window
              $window = 3;
              $half = floor($window / 2);
              if ($total_pages <= $window) {
                $start_page = 1;
                $end_page = $total_pages;
              } else {
                if ($page <= $half + 1) {
                  $start_page = 1;
                  $end_page = $window;
                } elseif ($page >= $total_pages - $half) {
                  $start_page = $total_pages - $window + 1;
                  $end_page = $total_pages;
                } else {
                  $start_page = $page - $half;
                  $end_page = $page + $half;
                }
              }
              for ($i = $start_page; $i <= $end_page; $i++):
            ?>
              <a href="?page=<?= $i ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" class="page<?= $i == $page ? ' active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
              <a href="?page=<?= ($page+1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" class="next-btn">
                Next <i class="fa fa-chevron-right"></i>
              </a>
            <?php else: ?>
              <button class="next-btn" disabled>
                Next <i class="fa fa-chevron-right"></i>
              </button>
            <?php endif; ?>
          </div>

          <div class="go-to-page">
            <form action="" method="GET">
              <input type="hidden" name="rows" value="<?= $rows_per_page ?>">
              <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
              <?php endif; ?>
              <?php if (!empty($status_filter)): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
              <?php endif; ?>
              <label>Go to Page:</label>
              <input type="number" name="page" min="1" max="<?= $total_pages ?>" value="<?= $page ?>">
              <button type="submit" class="btn go-btn">Go</button>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Confirm Delete</h3>
      <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to delete this job fair? This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<script>
  function confirmDelete(id) {
    const modal = document.getElementById('deleteModal');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    // Set the onclick event for the confirm button
    confirmBtn.onclick = function() {
      window.location.href = 'job_fair_delete.php?id=' + id;
    };
    
    // Show the modal
    modal.style.display = 'flex';
  }
  
  function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
  }
  
  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
      closeDeleteModal();
    }
  };
  
  // Auto-submit rows per page on input, with debounce
  document.addEventListener('DOMContentLoaded', function() {
    var rowsInput = document.getElementById('rowsInput');
    var form = document.getElementById('rowsPerPageForm');
    var debounceTimeout;
    if (rowsInput) {
      rowsInput.addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(function() {
          form.submit();
        }, 300); // 300ms debounce
      });
    }
    var resetBtn = document.getElementById('resetRowsBtn');
    if (resetBtn) {
      resetBtn.addEventListener('click', function() {
        rowsInput.value = 3;
        form.submit();
      });
    }
  });
</script>

<style>
  .job-fair-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .job-fair-header {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #007bff;
  }
  
  .job-fair-header h1 {
    margin: 0 0 0.5rem 0;
    color: #343a40;
  }
  
  .job-fair-header p {
    margin: 0 0 1rem 0;
    color: #6c757d;
  }
  
  .contact-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
  }
  
  .contact-info p {
    margin: 0.25rem 0;
  }
  
  .job-fair-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
  }
  
  .search-filter {
    display: flex;
    gap: 1rem;
    align-items: center;
  }
  
  .search-form {
    display: flex;
    align-items: center;
  }
  
  .search-bar {
    min-width: 300px;
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
  }
  
  .filter-group select {
    padding: 0.5rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
  }
  
  .action-buttons {
    display: flex;
    gap: 0.5rem;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-weight: 500;
  }
  
  .btn-primary {
    background-color: #28a745;
    color: white !important;
  }
  
  .btn-info {
    background-color: #17a2b8;
    color: white;
  }
  
  .btn-danger {
    background-color: #dc3545;
    color: white;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }
  
  /* Rows Per Page and Table Footer Styles */
  .job-fair-top {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .table-footer {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #666;
  }
  
  .rows-input {
    width: 60px;
    padding: 0.2rem;
    margin-left: 0.3rem;
  }
  
  /* Direct Hire Table Styles */
  .direct-hire-table {
    overflow-x: auto;
  }
  
  .direct-hire-table table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .direct-hire-table thead {
    background-color: #007bff;
    color: white;
  }
  
  .direct-hire-table th {
    padding: 0.75rem;
    text-align: left;
  }
  
  .direct-hire-table tbody tr {
    border-bottom: 1px solid #ddd;
  }
  
  .direct-hire-table td {
    padding: 0.75rem;
  }
  
  .direct-hire-table .status {
    padding: 0.3rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: bold;
    display: inline-block;
  }
  
  .direct-hire-table .status.planned {
    background-color: #cff4fc;
    color: #055160;
  }
  
  .direct-hire-table .status.confirmed {
    background-color: #d1e7dd;
    color: #0f5132;
  }
  
  .direct-hire-table .status.completed {
    background-color: #e2e3e5;
    color: #41464b;
  }
  
  .direct-hire-table .status.cancelled {
    background-color: #f8d7da;
    color: #842029;
  }
  
  .direct-hire-table .action-icons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
  }
  
  .direct-hire-table .action-icons i {
    cursor: pointer;
  }
  
  .direct-hire-table .action-icons a {
    color: inherit;
    text-decoration: none;
  }
  
  .direct-hire-table .action-icons i.fa-eye {
    color: #007bff;
  }
  
  .direct-hire-table .action-icons i.fa-edit {
    color: #28a745;
  }
  
  .direct-hire-table .action-icons i.fa-trash-alt {
    color: #dc3545;
  }
  
  .no-records {
    text-align: center;
    padding: 20px;
    color: #666;
  }
  
  /* Pagination and Go to Page Styles */
  .direct-hire-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
  }
  
  .pagination {
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }
  
  .pagination button {
    height: 2rem;
  }
  
  .pagination button,
  .pagination a {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid #dee2e6;
    background-color: #fff;
    color: #007bff;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.9rem;
  }
  
  .pagination a.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
  }
  
  .pagination button:disabled {
    color: #6c757d;
    cursor: not-allowed;
  }
  
  .pagination .prev-btn,
  .pagination .next-btn {
    font-weight: 500;
  }
  
  .go-to-page {
    display: flex;
    align-items: center;
    gap: 0.3rem;
  }
  
  .go-to-page label {
    font-size: .9rem;
  }
  
  .go-to-page input {
    font-size: .9rem;
    width: 50px;
    height: 1.5rem;
    padding: 0.3rem;
  }
  
  .go-btn {
    border: 1px solid transparent;
    background-color: transparent;
    border-radius: 6px;
    cursor: pointer;
    align-items: center;
    font-size: 0.9rem;
    text-decoration: none;
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: .2rem;
  }
  
  /* Modal styles */
  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }
  
  .modal-content {
    background-color: white;
    border-radius: 8px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
  }
  
  .modal-header h3 {
    margin: 0;
  }
  
  .modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
  }
</style>