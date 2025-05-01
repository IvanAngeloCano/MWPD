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
if (preg_match('/^(name|control_no|remarks|status|evaluator):(.+)$/i', $search_query, $matches)) {
  $exact_match = true;
  $exact_field = strtolower($matches[1]);
  $exact_value = trim($matches[2]);
  // Keep original search query for display purposes
}

// Handle success and error messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';

// Handle pagination
$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 5;
if ($rows_per_page < 1) $rows_per_page = 1;

// --- FILTER LOGIC ---
$filter_sql = '';
$filter_params = [];
if (!empty($_GET['filter_status'])) {
  $filter_sql .= ' AND status = ?';
  $filter_params[] = $_GET['filter_status'];
}
if (!empty($_GET['filter_remarks'])) {
  $filter_sql .= ' AND remarks LIKE ?';
  $filter_params[] = '%' . $_GET['filter_remarks'] . '%';
}
if (!empty($_GET['filter_evaluator'])) {
  $filter_sql .= ' AND evaluator LIKE ?';
  $filter_params[] = '%' . $_GET['filter_evaluator'] . '%';
}
if (!empty($_GET['filter_date_from'])) {
  $filter_sql .= ' AND created_at >= ?';
  $filter_params[] = $_GET['filter_date_from'];
}
if (!empty($_GET['filter_date_to'])) {
  $filter_sql .= ' AND created_at <= ?';
  $filter_params[] = $_GET['filter_date_to'];
}
if (!empty($_GET['filter_control_no'])) {
  $filter_sql .= ' AND control_no LIKE ?';
  $filter_params[] = '%' . $_GET['filter_control_no'] . '%';
}
if (!empty($_GET['filter_name'])) {
  $filter_sql .= ' AND name LIKE ?';
  $filter_params[] = '%' . $_GET['filter_name'] . '%';
}

try {
  // Different query approach based on tab and search
  if ($active_tab === 'denied') {
    if (!empty($search_query)) {
      if ($exact_match) {
        // Exact matching for denied tab
        $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE status = 'denied' AND $exact_field = ? $filter_sql";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute(array_merge([$exact_value], $filter_params));

        $sql = "SELECT * FROM direct_hire WHERE status = 'denied' AND $exact_field = ? $filter_sql 
                        ORDER BY created_at DESC LIMIT ?, ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$exact_value], $filter_params, [$offset, $rows_per_page]));
      } else {
        // Regular search for denied tab
        $search_condition = "(
                    control_no LIKE ? OR
                    name LIKE ? OR
                    remarks LIKE ? OR
                    evaluator LIKE ? OR
                    status LIKE ?
                )";

        $search_params = array_fill(0, 5, "%$search_query%");

        // Add date search if applicable
        $date_conditions = "";
        $date_params = [];

        if (
          preg_match('/^\d{4}-\d{2}-\d{2}$/', $search_query) ||
          preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $search_query) ||
          preg_match('/^\d{1,2} [a-zA-Z]+ \d{4}$/', $search_query)
        ) {

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

        $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE status = 'denied' AND ($search_condition$date_conditions) $filter_sql";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute(array_merge($search_params, $date_params, $filter_params));

        $sql = "SELECT * FROM direct_hire WHERE status = 'denied' AND ($search_condition$date_conditions) $filter_sql 
                        ORDER BY created_at DESC LIMIT ?, ?";
        $stmt = $pdo->prepare($sql);

        $all_params = array_merge($search_params, $date_params, $filter_params);
        $all_params[] = $offset;
        $all_params[] = $rows_per_page;
        $stmt->execute($all_params);
      }
    } else {
      // Denied tab without search
      $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE status = 'denied' $filter_sql";
      $count_stmt = $pdo->prepare($count_sql);
      $count_stmt->execute($filter_params);

      // Prepare the SQL but don't execute yet - we'll do that after calculating offset
      $sql = "SELECT * FROM direct_hire WHERE status = 'denied' $filter_sql ORDER BY created_at DESC LIMIT ?, ?";
      $stmt = $pdo->prepare($sql);
      // We'll execute this after calculating the offset
    }
  } else {
    // Professional or Household tab
    if (!empty($search_query)) {
      if ($exact_match) {
        // Exact matching for type tab
        $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE type = ? AND $exact_field = ? $filter_sql";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute(array_merge([$active_tab, $exact_value], $filter_params));

        $sql = "SELECT * FROM direct_hire WHERE type = ? AND $exact_field = ? $filter_sql 
                        ORDER BY created_at DESC LIMIT ?, ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$active_tab, $exact_value], $filter_params, [$offset, $rows_per_page]));
      } else {
        // Regular search for type tab
        $search_condition = "(
                    control_no LIKE ? OR
                    name LIKE ? OR
                    remarks LIKE ? OR
                    evaluator LIKE ? OR
                    status LIKE ?
                )";

        $search_params = array_fill(0, 5, "%$search_query%");

        // Add date search if applicable
        $date_conditions = "";
        $date_params = [];

        if (
          preg_match('/^\d{4}-\d{2}-\d{2}$/', $search_query) ||
          preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $search_query) ||
          preg_match('/^\d{1,2} [a-zA-Z]+ \d{4}$/', $search_query)
        ) {

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

        $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE type = ? AND ($search_condition$date_conditions) $filter_sql";
        $count_stmt = $pdo->prepare($count_sql);

        $count_params = array($active_tab);
        $count_params = array_merge($count_params, $search_params, $date_params, $filter_params);
        $count_stmt->execute($count_params);

        $sql = "SELECT * FROM direct_hire WHERE type = ? AND ($search_condition$date_conditions) $filter_sql 
                        ORDER BY created_at DESC LIMIT ?, ?";
        $stmt = $pdo->prepare($sql);

        $all_params = array($active_tab);
        $all_params = array_merge($all_params, $search_params, $date_params, $filter_params);
        $all_params[] = $offset;
        $all_params[] = $rows_per_page;
        $stmt->execute($all_params);
      }
    } else {
      // Without search
      $count_sql = "SELECT COUNT(*) FROM direct_hire WHERE type = ? $filter_sql";
      $count_stmt = $pdo->prepare($count_sql);
      $count_stmt->execute(array_merge([$active_tab], $filter_params));
      
      // Prepare the SQL but don't execute yet - we'll do that after calculating offset
      $sql = "SELECT * FROM direct_hire WHERE type = ? $filter_sql ORDER BY created_at DESC LIMIT ?, ?";
      $stmt = $pdo->prepare($sql);
    }
  }

  // Get total records and pages
  $total_records = $count_stmt->fetchColumn();
  $total_pages = ceil($total_records / $rows_per_page);
  
  // Now set the page number, ensuring it's within valid range
  $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
  if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
  } elseif ($page < 1) {
    $page = 1;
  }
  
  // Calculate offset based on validated page number
  $offset = ($page - 1) * $rows_per_page;
  
  // Now that we have the offset, execute the query with the proper parameters
  if (!empty($search_query)) {
    // For search queries, parameters were already bound and executed above
  } else {
    // For non-search queries, bind parameters and execute now
    if ($active_tab === 'denied') {
      // For denied tab
      $stmt->execute(array_merge($filter_params, [$offset, $rows_per_page]));
    } else {
      // For professional/household tabs
      $stmt->bindValue(1, $active_tab, PDO::PARAM_STR);
      $stmt->bindValue(2, $offset, PDO::PARAM_INT);
      $stmt->bindValue(3, $rows_per_page, PDO::PARAM_INT);
      $stmt->execute();
    }
  }

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
              <input type="hidden" name="rows" value="<?= $rows_per_page ?>">
              <input type="text" name="search" placeholder="Search..." class="search-bar" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
              <button type="submit" class="search-btn"><i class="fa fa-search"></i></button>
            </form>
            
            <button class="btn filter-btn" id="showFilterBarBtn"><i class="fa fa-filter"></i> Filter</button>
            
            <a href="direct_hire_add.php" class="btn add-btn"><i class="fa fa-plus"></i> Add New Record</a>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="error-message">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <!-- Filter Bar Wrapper (hidden by default) -->
          <div class="filter-bar-wrapper" id="filterBarWrapper" style="display:none;">
            <form class="filter-bar" method="GET" id="inlineFilterForm">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="hidden" name="rows" value="<?= $rows_per_page ?>">
              <input type="hidden" name="page" value="1">
              <label>Status:
                <select name="filter_status">
                  <option value="">All</option>
                  <option value="pending" <?= isset($_GET['filter_status']) && $_GET['filter_status']==='pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="approved" <?= isset($_GET['filter_status']) && $_GET['filter_status']==='approved' ? 'selected' : '' ?>>Approved</option>
                  <option value="denied" <?= isset($_GET['filter_status']) && $_GET['filter_status']==='denied' ? 'selected' : '' ?>>Denied</option>
                </select>
              </label>
              <label>Remarks:
                <input type="text" name="filter_remarks" placeholder="Remarks" value="<?= isset($_GET['filter_remarks']) ? htmlspecialchars($_GET['filter_remarks']) : '' ?>">
              </label>
              <label>Evaluator:
                <input type="text" name="filter_evaluator" placeholder="Evaluator" value="<?= isset($_GET['filter_evaluator']) ? htmlspecialchars($_GET['filter_evaluator']) : '' ?>">
              </label>
              <label>Date Range:
                <input type="date" name="filter_date_from" value="<?= isset($_GET['filter_date_from']) ? htmlspecialchars($_GET['filter_date_from']) : '' ?>"> to
                <input type="date" name="filter_date_to" value="<?= isset($_GET['filter_date_to']) ? htmlspecialchars($_GET['filter_date_to']) : '' ?>">
              </label>
              <label>Control No:
                <input type="text" name="filter_control_no" placeholder="Control No." value="<?= isset($_GET['filter_control_no']) ? htmlspecialchars($_GET['filter_control_no']) : '' ?>">
              </label>
              <label>Name:
                <input type="text" name="filter_name" placeholder="Name" value="<?= isset($_GET['filter_name']) ? htmlspecialchars($_GET['filter_name']) : '' ?>">
              </label>
              <div class="filter-actions">
                <button type="submit" class="btn go-btn">Apply</button>
                <button type="button" class="btn clear-btn" id="clearAllFiltersBtn">Clear All</button>
              </div>
            </form>
            <div class="filter-chips" id="filterChips">
              <!-- Chips will be rendered here by JS -->
            </div>
          </div>

          <div class="table-footer">
            <span class="results-count">
              Showing <?= min(($offset + 1), $total_records) ?>-<?= min(($offset + $rows_per_page), $total_records) ?> out of <?= $total_records ?> results
            </span>
            <form action="" method="GET" id="rowsPerPageForm">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="hidden" name="page" value="<?= $page ?>">
              <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
              <?php endif; ?>
              <div class="rows-control">
                <label>
                  Rows per page:
                  <input type="number" min="1" name="rows" class="rows-input" value="<?= $rows_per_page ?>" id="rowsInput">
                </label>
                <button type="button" class="btn reset-btn" id="resetRowsBtn">Reset</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Middle Section -->
        <div class="direct-hire-table">
          <style>
            .filter-bar {
              display: flex;
              flex-wrap: wrap;
              gap: 10px;
              align-items: center;
              background: #fff;
              border-radius: 16px;
              box-shadow: 0 2px 8px rgba(0,0,0,0.06);
              padding: 16px 18px 8px 18px;
              margin-bottom: 12px;
            }
            .filter-bar label {
              margin-bottom: 0;
              font-weight: 500;
              color: #333;
            }
            .filter-bar input,
            .filter-bar select {
              border-radius: 16px;
              border: 1px solid #ccc;
              padding: 6px 14px;
              outline: none;
              box-shadow: none;
              transition: box-shadow 0.2s;
              margin-left: 4px;
              margin-right: 10px;
            }
            .filter-bar input:focus,
            .filter-bar select:focus {
              box-shadow: 0 0 0 2px #007bff33;
              border-color: #007bff;
            }
            .filter-bar .btn {
              border-radius: 16px;
              padding: 6px 18px;
              margin-left: 6px;
              font-weight: 500;
              border: none;
            }
            .filter-bar .btn.go-btn {
              background: #007bff;
              color: #fff;
            }
            .filter-bar .btn.clear-btn {
              background: #eee;
              color: #444;
            }
            .filter-chips {
              display: flex;
              flex-wrap: wrap;
              gap: 8px;
              margin-top: 6px;
              margin-bottom: 10px;
            }
            .filter-chip {
              display: flex;
              align-items: center;
              background: #e7f1ff;
              color: #007bff;
              border-radius: 16px;
              padding: 4px 12px 4px 10px;
              font-size: 14px;
              font-weight: 500;
              animation: chipIn 0.25s;
            }
            .filter-chip .chip-remove {
              margin-left: 6px;
              background: none;
              border: none;
              color: #007bff;
              cursor: pointer;
              font-size: 16px;
              line-height: 1;
            }
            @keyframes chipIn {
              from { opacity: 0; transform: translateY(-10px); }
              to { opacity: 1; transform: translateY(0); }
            }
            @media (max-width: 700px) {
              .filter-bar { flex-direction: column; align-items: stretch; }
            }
            
            .warning-modal .modal-header {
              background-color: #add8e6;
              color: #007bff;
              border-bottom: 1px solid #add8e6;
            }
            
            .warning-modal .modal-header h3 {
              color: #007bff;
            }
            
            .warning-modal .modal-header .fa-info-circle {
              margin-right: 10px;
              color: #007bff;
            }
            
            .warning-modal .modal-body {
              padding: 20px;
              font-size: 16px;
            }
            
            .warning-modal .modal-footer {
              border-top: 1px solid #add8e6;
              background-color: #f8f9fa;
            }
          </style>
          <table>
            <thead>
              <tr>
                <th>No.</th>
                <th>Control No.</th>
                <th>Name</th>
                <th>Remarks</th>
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
                    <td><?= htmlspecialchars($record['remarks'] ?? 'N/A') ?></td>
                    <td>
                      <span class="status-badge <?= strtolower($record['status']) ?>">
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

        <!-- Pagination Controls Wrapper -->
        <div class="pagination-controls-wrapper" style="margin-top:-1px;">
          <!-- Bottom Section -->
          <div class="direct-hire-bottom">
            <div class="pagination">
              <?php if ($page > 1): ?>
                <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= ($page - 1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="prev-btn">
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
                <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= $i ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page<?= $i == $page ? ' active' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>

              <?php if ($page < $total_pages): ?>
                <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= ($page + 1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="next-btn">
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
        </div>
      </section>
    </main>
  </div>
</div>

<script>
// Double-click functionality for table rows
document.addEventListener('DOMContentLoaded', function() {
  const tableRows = document.querySelectorAll('tbody tr');
  tableRows.forEach(row => {
    row.addEventListener('dblclick', function(e) {
      // Don't trigger if clicking on checkbox or action buttons
      if (e.target.closest('input[type="checkbox"]') || e.target.closest('.action-icons')) {
        return;
      }
      
      // Get the record ID from the row
      const viewLink = row.querySelector('a[href*="direct_hire_view.php"]');
      if (viewLink) {
        window.location.href = viewLink.getAttribute('href');
      }
    });
    
    // Add cursor style to indicate clickable
    row.style.cursor = 'pointer';
  });
});

// Use the existing filter button to toggle filter bar
document.addEventListener('DOMContentLoaded', function() {
  const showBtn = document.getElementById('showFilterBarBtn');
  const filterBarWrapper = document.getElementById('filterBarWrapper');
  
  // Filter bar is hidden by default
  filterBarWrapper.style.display = 'none';
  
  showBtn.addEventListener('click', function(e) {
    e.preventDefault();
    if (filterBarWrapper.style.display === 'none') {
      filterBarWrapper.style.display = 'block';
      showBtn.classList.add('active');
    } else {
      filterBarWrapper.style.display = 'none';
      showBtn.classList.remove('active');
    }
  });
  
  // Render filter chips for active filters
  function renderFilterChips() {
    const params = new URLSearchParams(window.location.search);
    const chips = [];
    const filterLabels = {
      filter_status: 'Status',
      filter_remarks: 'Remarks',
      filter_evaluator: 'Evaluator',
      filter_date_from: 'Date From',
      filter_date_to: 'Date To',
      filter_control_no: 'Control No.',
      filter_name: 'Name',
    };
    for (const key in filterLabels) {
      const value = params.get(key);
      if (value) {
        let label = filterLabels[key] + ': ' + value;
        chips.push(`<span class='filter-chip' data-key='${key}'>${label}<button class='chip-remove' title='Remove'>&times;</button></span>`);
      }
    }
    document.getElementById('filterChips').innerHTML = chips.join('');
  }
  
  renderFilterChips();
  
  // Remove chip handler
  document.getElementById('filterChips').addEventListener('click', function(e) {
    if (e.target.classList.contains('chip-remove')) {
      const chip = e.target.closest('.filter-chip');
      const key = chip.getAttribute('data-key');
      const params = new URLSearchParams(window.location.search);
      params.delete(key);
      window.location.search = params.toString();
    }
  });
  
  // Clear all filters
  document.getElementById('clearAllFiltersBtn').addEventListener('click', function() {
    const form = document.getElementById('inlineFilterForm');
    Array.from(form.elements).forEach(function(el) {
      if (el.name.startsWith('filter_')) el.value = '';
    });
    form.submit();
  });
});
</script>

<!-- Warning Modal for No Selection -->
<div id="warningModal" class="modal">
  <div class="modal-content warning-modal">
    <div class="modal-header">
      <h3><i class="fa fa-info-circle"></i> Information</h3>
      <span class="close" onclick="document.getElementById('warningModal').style.display='none'">&times;</span>
    </div>
    <div class="modal-body">
      <p>No selected</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-primary" onclick="document.getElementById('warningModal').style.display='none'">OK</button>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal" style="display: none;">
  <div class="modal-content" style="max-width: 400px;">
    <div class="modal-header">
      <span class="close" onclick="document.getElementById('deleteModal').style.display='none'">&times;</span>
      <h2>Confirm Delete</h2>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to delete this record?</p>
      <p>This action cannot be undone.</p>
    </div>
    <div class="modal-footer">
      <form id="deleteForm" action="direct_hire_delete.php" method="POST">
        <input type="hidden" name="id" id="deleteId">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </form>
    </div>
  </div>
</div>

<!-- Popup Memo Form -->
<div id="popupMemoForm" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="document.getElementById('popupMemoForm').style.display='none'">&times;</span>
      <h2>Generate Memorandum</h2>
    </div>
    <div class="modal-body">
      <form action="generate_memo.php" method="POST" id="memoForm">
        <div class="form-group">
          <label for="employer">Employer:</label>
          <input type="text" id="employer" name="employer" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="memo_date">Memo Date:</label>
          <input type="date" id="memo_date" name="memo_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <p><strong>Selected Applicants:</strong></p>
          <div id="selectedApplicants"></div>
          <div id="selectedApplicantsIds"></div>
          <input type="hidden" name="source" value="direct_hire">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('popupMemoForm').style.display='none'">Cancel</button>
          <button type="submit" class="btn btn-primary">Generate Memo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Get the delete modal
  var deleteModal = document.getElementById('deleteModal');
  var warningModal = document.getElementById('warningModal');
  
  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
    if (event.target == deleteModal) {
      deleteModal.style.display = "none";
    }
    if (event.target == document.getElementById('popupMemoForm')) {
      document.getElementById('popupMemoForm').style.display = "none";
    }
    if (event.target == warningModal) {
      warningModal.style.display = "none";
    }
  }
  
  // Function to show delete confirmation modal
  function showDeleteModal(id) {
    document.getElementById('deleteId').value = id;
    deleteModal.style.display = "block";
  }

  // Function to show warning modal
  function showWarningModal(message) {
    const modalBody = warningModal.querySelector('.modal-body p');
    if (modalBody && message) {
      modalBody.textContent = message;
    }
    warningModal.style.display = "block";
  }

  // Update the memo form with selected applicants
  document.addEventListener('DOMContentLoaded', function() {
    // Handle select all checkbox
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.record-checkbox');
        checkboxes.forEach(function(checkbox) {
          checkbox.checked = selectAllCheckbox.checked;
        });
      });
    }
    
    document.querySelector('.create-memo').addEventListener('click', function(e) {
      e.preventDefault();
      
      // Get all checkboxes in the table
      const checkboxes = document.querySelectorAll('input[type="checkbox"].record-checkbox:checked');
      
      // Always ensure the memo modal is hidden first
      document.getElementById('popupMemoForm').style.display = 'none';
      
      if (checkboxes.length === 0) {
        // Show warning modal if no checkboxes are selected
        showWarningModal('No selected');
        return; // Stop execution here
      }
      
      const selectedIds = [];
      const selectedNames = [];

      checkboxes.forEach(function(checkbox) {
        const row = checkbox.closest('tr');
        const id = checkbox.value;
        // Get the name from the 4th cell (index 3) which contains the name
        const name = row.cells[3].textContent.trim();
        
        selectedIds.push(id);
        selectedNames.push(name);
      });

      // Display selected applicants
      const selectedApplicantsDiv = document.getElementById('selectedApplicants');
      const selectedApplicantsIdsDiv = document.getElementById('selectedApplicantsIds');
      
      // Add selected applicants to the form and show it
      let html = '<ul>';
      selectedNames.forEach(function(name) {
        html += '<li>' + name + '</li>';
      });
      html += '</ul>';
      selectedApplicantsDiv.innerHTML = html;
      
      // Add hidden inputs for selected IDs
      let idsHtml = '';
      selectedIds.forEach(function(id) {
        idsHtml += '<input type="hidden" name="selected_ids[]" value="' + id + '">';
      });
      selectedApplicantsIdsDiv.innerHTML = idsHtml;
      
      // Only show the memo form if we have selections
      document.getElementById('popupMemoForm').style.display = 'block';
    });
  });
</script>