<?php
include 'session.php';
require_once 'connection.php';

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;
$offset = ($page - 1) * $rows_per_page;

// Handle search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

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

$pageTitle = "Job Fairs - MWPD Filing System";
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
        
        <!-- Job Fairs Table -->
        <div class="job-fair-table">
          <table>
            <thead>
              <tr>
                <th>No.</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Contact Information</th>
                <th>Status</th>
                <th>Actions</th>
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
                    <?php if (!empty($fair['contact_numbers'])): ?>
                    <div><?= htmlspecialchars($fair['contact_numbers']) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($fair['invitation_contact_email'])): ?>
                    <div><a href="mailto:<?= htmlspecialchars($fair['invitation_contact_email']) ?>"><?= htmlspecialchars($fair['invitation_contact_email']) ?></a></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="status-badge <?= strtolower($fair['status']) ?>">
                      <?= ucfirst(htmlspecialchars($fair['status'])) ?>
                    </span>
                  </td>
                  <td class="action-buttons">
                    <a href="job_fair_view.php?id=<?= $fair['id'] ?>" class="btn btn-sm btn-info" title="View Details">
                      <i class="fa fa-eye"></i>
                    </a>
                    <a href="job_fair_edit.php?id=<?= $fair['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                      <i class="fa fa-edit"></i>
                    </a>
                    <a href="javascript:void(0)" onclick="confirmDelete(<?= $fair['id'] ?>)" class="btn btn-sm btn-danger" title="Delete">
                      <i class="fa fa-trash"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination Controls -->
        <?php if ($total_pages > 1): ?>
        <div class="job-fair-pagination">
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
            $start_page = max(1, min($page - 2, $total_pages - 4));
            $end_page = min($total_pages, max($page + 2, 5));
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
            <a href="?page=<?= $i ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" class="page <?= ($i == $page) ? 'active' : '' ?>">
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
        </div>
        <?php endif; ?>
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
    background-color: #007bff;
    color: white;
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
  
  .job-fair-table {
    overflow-x: auto;
  }
  
  .job-fair-table table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .job-fair-table th,
  .job-fair-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
  }
  
  .job-fair-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    text-align: left;
  }
  
  .status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
  }
  
  .status-badge.planned {
    background-color: #cff4fc;
    color: #055160;
  }
  
  .status-badge.confirmed {
    background-color: #d1e7dd;
    color: #0f5132;
  }
  
  .status-badge.completed {
    background-color: #e2e3e5;
    color: #41464b;
  }
  
  .status-badge.cancelled {
    background-color: #f8d7da;
    color: #842029;
  }
  
  .action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
  }
  
  .no-records {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    font-style: italic;
  }
  
  .job-fair-pagination {
    display: flex;
    justify-content: center;
    margin-top: 1rem;
  }
  
  .pagination {
    display: flex;
    gap: 0.25rem;
    align-items: center;
  }
  
  .pagination a,
  .pagination button {
    padding: 0.5rem 0.75rem;
    border: 1px solid #dee2e6;
    background-color: #fff;
    color: #007bff;
    border-radius: 4px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
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