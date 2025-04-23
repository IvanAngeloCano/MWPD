<?php
include 'session.php';
require_once 'connection.php';

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'professional';

// Handle search query
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Check if search is for exact matching
$exact_match = false;
$exact_field = '';
$exact_value = '';

// Parse search query to check for special format "field:value" for exact matching
if (preg_match('/^(name|control_no|jobsite|status|evaluator):(.+)$/i', $search_query, $matches)) {
    $exact_match = true;
    $exact_field = strtolower($matches[1]);
    $exact_value = trim($matches[2]);
    // Keep original search query for display purposes
}

// Handle success and error messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;
$offset = ($page - 1) * $rows_per_page;

try {
    // Different query approach based on tab and search
    if ($active_tab === 'denied') {
        if (!empty($search_query)) {
            if ($exact_match) {
                // Exact matching for denied tab
                $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE status = 'denied' AND $exact_field = ?";
                $count_stmt = $pdo->prepare($count_sql);
                $count_stmt->execute([$exact_value]);
                
                $sql = "SELECT * FROM direct_hire WHERE status = 'denied' AND $exact_field = ? 
                        ORDER BY created_at DESC LIMIT ?, ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$exact_value, $offset, $rows_per_page]);
            } else {
                // Regular search for denied tab
                $search_condition = "(
                    control_no LIKE ? OR
                    name LIKE ? OR
                    jobsite LIKE ? OR
                    evaluator LIKE ? OR
                    status LIKE ?
                )";
                
                $search_params = array_fill(0, 5, "%$search_query%");
                
                // Add date search if applicable
                $date_conditions = "";
                $date_params = [];
                
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search_query) || 
                    preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $search_query) ||
                    preg_match('/^\d{1,2} [a-zA-Z]+ \d{4}$/', $search_query)) {
                    
                    // Try to convert to MySQL date format
                    $date_obj = date_create_from_format('Y-m-d', $search_query);
                    if (!$date_obj) {
                        $date_obj = date_create_from_format('m/d/Y', $search_query);
                    }
                    if (!$date_obj) {
                        $date_obj = date_create_from_format('j F Y', $search_query);
                    }
                    
                    if ($date_obj) {
                        $formatted_date = $date_obj->format('Y-m-d');
                        $date_fields = ['evaluated', 'for_confirmation', 'emailed_to_dhad', 'received_from_dhad'];
                        
                        $date_conditions = [];
                        foreach ($date_fields as $field) {
                            $date_conditions[] = "$field = ?";
                            $date_params[] = $formatted_date;
                        }
                        
                        // Also search for dates that contain the search string
                        foreach ($date_fields as $field) {
                            $date_conditions[] = "$field LIKE ?";
                            $date_params[] = "%$search_query%";
                        }
                        
                        $date_conditions = " OR (" . implode(" OR ", $date_conditions) . ")";
                    }
                }
                
                $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE status = 'denied' AND ($search_condition$date_conditions)";
                $count_stmt = $pdo->prepare($count_sql);
                $count_stmt->execute(array_merge($search_params, $date_params));
                
                $sql = "SELECT * FROM direct_hire WHERE status = 'denied' AND ($search_condition$date_conditions) 
                        ORDER BY created_at DESC LIMIT ?, ?";
                $stmt = $pdo->prepare($sql);
                
                $all_params = array_merge($search_params, $date_params);
                $all_params[] = $offset;
                $all_params[] = $rows_per_page;
                $stmt->execute($all_params);
            }
        } else {
            // Denied tab without search
            $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE status = 'denied'";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute();
            
            $sql = "SELECT * FROM direct_hire WHERE status = 'denied' ORDER BY created_at DESC LIMIT ?, ?";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(1, $offset, PDO::PARAM_INT);
            $stmt->bindValue(2, $rows_per_page, PDO::PARAM_INT);
            $stmt->execute();
        }
    } else {
        // Professional or Household tab
        if (!empty($search_query)) {
            if ($exact_match) {
                // Exact matching for type tab
                $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE type = ? AND $exact_field = ?";
                $count_stmt = $pdo->prepare($count_sql);
                $count_stmt->execute([$active_tab, $exact_value]);
                
                $sql = "SELECT * FROM direct_hire WHERE type = ? AND $exact_field = ? 
                        ORDER BY created_at DESC LIMIT ?, ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$active_tab, $exact_value, $offset, $rows_per_page]);
            } else {
                // Regular search for type tab
                $search_condition = "(
                    control_no LIKE ? OR
                    name LIKE ? OR
                    jobsite LIKE ? OR
                    evaluator LIKE ? OR
                    status LIKE ?
                )";
                
                $search_params = array_fill(0, 5, "%$search_query%");
                
                // Add date search if applicable
                $date_conditions = "";
                $date_params = [];
                
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search_query) || 
                    preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $search_query) ||
                    preg_match('/^\d{1,2} [a-zA-Z]+ \d{4}$/', $search_query)) {
                    
                    // Try to convert to MySQL date format
                    $date_obj = date_create_from_format('Y-m-d', $search_query);
                    if (!$date_obj) {
                        $date_obj = date_create_from_format('m/d/Y', $search_query);
                    }
                    if (!$date_obj) {
                        $date_obj = date_create_from_format('j F Y', $search_query);
                    }
                    
                    if ($date_obj) {
                        $formatted_date = $date_obj->format('Y-m-d');
                        $date_fields = ['evaluated', 'for_confirmation', 'emailed_to_dhad', 'received_from_dhad'];
                        
                        $date_conditions = [];
                        foreach ($date_fields as $field) {
                            $date_conditions[] = "$field = ?";
                            $date_params[] = $formatted_date;
                        }
                        
                        // Also search for dates that contain the search string
                        foreach ($date_fields as $field) {
                            $date_conditions[] = "$field LIKE ?";
                            $date_params[] = "%$search_query%";
                        }
                        
                        $date_conditions = " OR (" . implode(" OR ", $date_conditions) . ")";
                    }
                }
                
                $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE type = ? AND ($search_condition$date_conditions)";
                $count_stmt = $pdo->prepare($count_sql);
                
                $count_params = array($active_tab);
                $count_params = array_merge($count_params, $search_params, $date_params);
                $count_stmt->execute($count_params);
                
                $sql = "SELECT * FROM direct_hire WHERE type = ? AND ($search_condition$date_conditions) 
                        ORDER BY created_at DESC LIMIT ?, ?";
                $stmt = $pdo->prepare($sql);
                
                $all_params = array($active_tab);
                $all_params = array_merge($all_params, $search_params, $date_params);
                $all_params[] = $offset;
                $all_params[] = $rows_per_page;
                $stmt->execute($all_params);
            }
        } else {
            // Without search
            $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE type = ?";
            $count_stmt = $pdo->prepare($count_sql);
            $count_stmt->execute([$active_tab]);
            
            $sql = "SELECT * FROM direct_hire WHERE type = ? ORDER BY created_at DESC LIMIT ?, ?";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(1, $active_tab, PDO::PARAM_STR);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->bindValue(3, $rows_per_page, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
    
    // Get total records and pages
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $rows_per_page);
    
    // Fetch all the records
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Handle database error
    $error_message = "Database error: " . $e->getMessage();
    $records = [];
    $total_records = 0;
    $total_pages = 0;
}

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
            <a href="?tab=professional<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'professional' ? 'active' : '' ?>">Professional</a>
            <a href="?tab=household<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'household' ? 'active' : '' ?>">Household</a>
            <a href="?tab=denied<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'denied' ? 'active' : '' ?>">Denied</a>
          </div>

          <div class="controls">
            <form action="" method="GET" class="search-form">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="text" name="search" placeholder="Search or use name:John for exact match" class="search-bar" value="<?= htmlspecialchars($search_query) ?>">
              <button type="submit" class="btn search-btn"><i class="fa fa-search"></i></button>
            </form>
            
            <!-- <div class="search-help">
              <p><strong>Search tips:</strong> Use <code>field:value</code> for exact match (e.g., <code>name:John</code>, <code>status:approved</code>)</p>
            </div> -->
            
            <button class="btn filter-btn"><i class="fa fa-filter"></i> Filter</button>
            <a href="direct_hire_add.php?type=<?= urlencode($active_tab) ?>">
              <button class="btn add-btn"><i class="fa fa-plus"></i> Add New Record</button>
            </a>
          </div>

          <?php if (!empty($error_message)): ?>
          <div class="error-message">
            <?= htmlspecialchars($error_message) ?>
          </div>
          <?php endif; ?>

          <div class="table-footer">
            <span class="results-count">
              Showing <?= min(($offset + 1), $total_records) ?>-<?= min(($offset + $rows_per_page), $total_records) ?> out of <?= $total_records ?> results
            </span>
            <form action="" method="GET">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="hidden" name="page" value="<?= $page ?>">
              <?php if (!empty($search_query)): ?>
              <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
              <?php endif; ?>
              <label>
                Rows per page:
                <input type="number" min="1" name="rows" class="rows-input" value="<?= $rows_per_page ?>" onchange="this.form.submit()">
              </label>
            </form>
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
              <?php if (count($records) == 0): ?>
              <tr>
                <td colspan="6" class="no-records">No records found</td>
              </tr>
              <?php else: ?>
              <?php foreach ($records as $index => $record): ?>
              <tr>
                <td><?= $offset + $index + 1 ?></td>
                <td><?= htmlspecialchars($record['control_no']) ?></td>
                <td><?= htmlspecialchars($record['name']) ?></td>
                <td><?= htmlspecialchars($record['jobsite']) ?></td>
                <td>
                  <span class="status <?= strtolower($record['status']) ?>">
                    <?= ucfirst(htmlspecialchars($record['status'])) ?>
                  </span>
                </td>
                <td class="action-icons">
                  <a href="direct_hire_view.php?id=<?= $record['id'] ?>" title="View Record">
                    <i class="fa fa-eye"></i>
                  </a>
                  <a href="direct_hire_edit.php?id=<?= $record['id'] ?>" title="Edit Record">
                    <i class="fa fa-edit"></i>
                  </a>
                  <a href="javascript:void(0)" onclick="confirmDelete(<?= $record['id'] ?>)" title="Delete Record">
                    <i class="fa fa-trash-alt"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Bottom Section -->
        <div class="direct-hire-bottom">
          <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= ($page-1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="prev-btn">
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
            <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= $i ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page <?= ($i == $page) ? 'active' : '' ?>">
              <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
            <span>...</span>
            <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= $total_pages ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page">
              <?= $total_pages ?>
            </a>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
            <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= ($page+1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="next-btn">
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
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="hidden" name="rows" value="<?= $rows_per_page ?>">
              <?php if (!empty($search_query)): ?>
              <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
              <?php endif; ?>
              <label>Go to Page:</label>
              <input type="number" name="page" min="1" max="<?= $total_pages ?>" value="<?= $page ?>">
              <button type="submit" class="btn go-btn">Go</button>
            </form>
          </div>
        </div>
      </section>
    </main>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <h3>Confirm Delete</h3>
      <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
    </div>
    <div class="modal-body" style="text-align: center;">
      <p>Are you sure you want to delete this record? This action cannot be undone.</p>
      <div class="modal-actions" style="justify-content: center; margin-top: 20px;">
        <button class="btn btn-cancel" onclick="closeDeleteModal()">Cancel</button>
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
      window.location.href = 'direct_hire_delete.php?id=' + id;
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
