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
$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 8;
if ($rows_per_page < 1) $rows_per_page = 1;

// --- FILTER LOGIC ---
$filter_sql = '';
$filter_params = [];
if (!empty($_GET['filter_status'])) {
  $filter_sql .= ' AND status = ?';
  $filter_params[] = $_GET['filter_status'];
}
if (!empty($_GET['filter_jobsite'])) {
  $filter_sql .= ' AND jobsite LIKE ?';
  $filter_params[] = '%' . $_GET['filter_jobsite'] . '%';
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
                    jobsite LIKE ? OR
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
                    jobsite LIKE ? OR
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

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $rows_per_page;

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
        <div class="process-page-top">
          <div class="tabs">
            <a href="?tab=professional<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'professional' ? 'active' : '' ?>">Professional</a>
            <a href="?tab=household<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'household' ? 'active' : '' ?>">Household</a>
            <a href="?tab=denied<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'denied' ? 'active' : '' ?>">Denied</a>
          </div>

          <div class="controls">
            <form action="" method="GET" class="search-form">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <div class="search-form">
                <input type="text" name="search" placeholder="Search" class="search-bar" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="btn search-btn"><i class="fa fa-search"></i></button>
              </div>

            </form>

            <!-- <div class="search-help">
              <p><strong>Search tips:</strong> Use <code>field:value</code> for exact match (e.g., <code>name:John</code>, <code>status:approved</code>)</p>
            </div> -->

            <button class="btn filter-btn" id="showFilterBarBtn" "><i class=" fa fa-filter"></i> Filter</button>
            <a href="direct_hire_add.php?type=<?= urlencode($active_tab) ?>" class="btn add-btn"><i class="fa fa-plus"></i> Add New Record</a>
            <!-- <button onclick="document.getElementById('popupMemoForm').style.display='block'" class="btn go-btn create-memo" style="margin-left:10px;"><i class="fa fa-file-alt"></i> <b>GENERATE MEMO</b></button> -->
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="error-message">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <div class="process-page-top-additionals">
            <span class="results-count">
              Showing <?= min(($offset + 1), $total_records) ?>-<?= min(($offset + $rows_per_page), $total_records) ?> out of <?= $total_records ?> results
            </span>
            <form action="" method="GET" id="rowsPerPageForm" style="display:inline-block;">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="hidden" name="page" value="<?= $page ?>">
              <?php if (!empty($search_query)): ?>
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
              <?php endif; ?>
              <label>
                Rows per page:
                <input type="number" min="1" name="rows" class="rows-input" value="<?= $rows_per_page ?>" id="rowsInput">
              </label>
              <button type="button" class="btn" id="resetRowsBtn" style="background-color:#007bff;color:#fff;border:none;border-radius:6px;padding:3px 10px;">Reset</button>
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
              box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
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
              border-radius: 6px;
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
              border-radius: 6px;
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
              border-radius: 6px;
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
              from {
                opacity: 0;
                transform: translateY(-10px);
              }

              to {
                opacity: 1;
                transform: translateY(0);
              }
            }

            @media (max-width: 700px) {
              .filter-bar {
                flex-direction: column;
                align-items: stretch;
              }
            }
          </style>
          <!-- Filter Bar Wrapper (hidden by default) -->
          <div id="filterBarWrapper" style="display:none;">
            <form class="filter-bar" method="GET" id="inlineFilterForm">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="hidden" name="rows" value="<?= $rows_per_page ?>">
              <input type="hidden" name="page" value="1">
              <label>Status:
                <select name="filter_status">
                  <option value="">All</option>
                  <option value="approved" <?= isset($_GET['filter_status']) && $_GET['filter_status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                  <option value="pending" <?= isset($_GET['filter_status']) && $_GET['filter_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="denied" <?= isset($_GET['filter_status']) && $_GET['filter_status'] === 'denied' ? 'selected' : '' ?>>Denied</option>
                </select>
              </label>
              <label>Jobsite:
                <input type="text" name="filter_jobsite" class="input-filter" placeholder="Jobsite" value="<?= isset($_GET['filter_jobsite']) ? htmlspecialchars($_GET['filter_jobsite']) : '' ?>">
              </label>
              <label>Evaluator:
                <input type="text" name="filter_evaluator" class="input-filter" placeholder="Evaluator" value="<?= isset($_GET['filter_evaluator']) ? htmlspecialchars($_GET['filter_evaluator']) : '' ?>">
              </label>
              <label>Date:
                <input type="date" name="filter_date_from" value="<?= isset($_GET['filter_date_from']) ? htmlspecialchars($_GET['filter_date_from']) : '' ?>" style="width:130px;"> to
                <input type="date" name="filter_date_to" value="<?= isset($_GET['filter_date_to']) ? htmlspecialchars($_GET['filter_date_to']) : '' ?>" style="width:130px;">
              </label>
              <label>Control No.:
                <input type="text" name="filter_control_no" class="input-filter" placeholder="Control No." value="<?= isset($_GET['filter_control_no']) ? htmlspecialchars($_GET['filter_control_no']) : '' ?>">
              </label>
              <label>Name:
                <input type="text" name="filter_name" class="input-filter" placeholder="Name" value="<?= isset($_GET['filter_name']) ? htmlspecialchars($_GET['filter_name']) : '' ?>">
              </label>
              <button type="submit" class="btn go-btn">Apply</button>
              <button type="button" class="btn clear-btn" id="clearAllFiltersBtn">Clear All</button>
            </form>
            <div class="filter-chips" id="filterChips">
              <!-- Chips will be rendered here by JS -->
            </div>
          </div>
          <script>
            // Use the existing filter button to toggle filter bar
            const showBtn = document.getElementById('showFilterBarBtn');
            const filterBarWrapper = document.getElementById('filterBarWrapper');
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
                filter_jobsite: 'Jobsite',
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
            // Document ready functions
            document.addEventListener('DOMContentLoaded', function() {
              // Check if we need to reload the table after deletion
              const urlParams = new URLSearchParams(window.location.search);
              if (urlParams.get('reload') === 'true') {
                // Force table reload
                loadRecords();
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
          <table>
            <thead>
              <tr>
                <!-- <th><input type="checkbox" id="select-all-checkbox"></th> -->
                <th>No.</th>
                <th>Control No.</th>
                <th>Name</th>
                <th>Jobsite</th>
                <th>Status</th>
                <th style="text-align: center;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($records) == 0): ?>
                <tr>
                  <td colspan="7" class="no-records">No records found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($records as $index => $record): ?>
                  <tr data-id="<?= $record['id'] ?>">
                    <!-- <td><input type="checkbox" class="record-checkbox" value="<?= $record['id'] ?>"></td> -->
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
                      <a href="javascript:void(0)" onclick="openDeleteModal(<?= $record['id'] ?>)" title="Delete Record">
                        <i class="fa fa-trash-alt"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <script>
          document.addEventListener("DOMContentLoaded", function() {
            // Select all table rows with a data-id attribute
            const tableRows = document.querySelectorAll("tr[data-id]");

            tableRows.forEach(function(row) {
              row.addEventListener("dblclick", function() {
                const recordId = this.getAttribute("data-id");
                if (recordId) {
                  // Redirect to view page
                  window.location.href = `direct_hire_view.php?id=${recordId}`;
                }
              });
            });
          });
        </script>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
          <div class="modal-content">
            <div class="modal-header">
              <h3>Confirm Delete</h3>
              <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete this record? This action cannot be undone.</p>
              <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">Delete</button>
              </div>
            </div>
          </div>
        </div>

        <style>
          /* Delete Modal Styles */
          #deleteModal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
          }
          
          #deleteModal.show {
            display: flex;
          }
          
          #deleteModal .modal-content {
            background-color: #fff;
            border-radius: 5px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
          }
          
          #deleteModal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #ffc107; /* Yellow color */
            color: white;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
          }
          
          #deleteModal .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
          }
          
          #deleteModal .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
          }
          
          #deleteModal .modal-body {
            padding: 20px;
          }
          
          #deleteModal .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
          }
          
          #deleteModal .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
          }
          
          #deleteModal .btn-secondary {
            background-color: #6c757d;
            color: white;
          }
          
          #deleteModal .btn-danger {
            background-color: #ffc107; /* Yellow color */
            color: white;
          }
          
          #deleteModal .btn-danger:hover {
            background-color: #e0a800; /* Darker yellow on hover */
          }
          
          #deleteModal .btn:hover {
            opacity: 0.9;
          }
          
          /* Delete button styles */
          .delete-button {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 5px;
            margin-left: 5px;
            display: inline-block;
            vertical-align: middle;
          }
          
          .delete-button:hover {
            color: #bd2130;
          }
        </style>

        <script>
          let deleteRecordId = null;
          
          function openDeleteModal(id) {
            deleteRecordId = id;
            document.getElementById('deleteModal').classList.add('show');
          }
          
          function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
          }
          
          function confirmDelete() {
            window.location.href = "direct_hire_delete.php?id=" + deleteRecordId;
          }
          
          // Close modal when clicking outside
          window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
              closeDeleteModal();
            }
            if (event.target == document.getElementById('popupMemoForm')) {
              document.getElementById('popupMemoForm').style.display = "none";
            }
          };
        </script>

        <!-- Bottom Section -->
        <div class="process-page-bot">
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
      </section>
    </main>
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

<!-- Popup Memo Form
<div id="popupMemoForm" class="modal">
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
</div> -->

<script>
  // Get the delete modal
  var deleteModal = document.getElementById('deleteModal');

  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
    if (event.target == deleteModal) {
      deleteModal.style.display = "none";
    }
    if (event.target == document.getElementById('popupMemoForm')) {
      document.getElementById('popupMemoForm').style.display = "none";
    }
  }

  // Function to show delete confirmation modal
  function showDeleteModal(id) {
    document.getElementById('deleteId').value = id;
    deleteModal.style.display = "block";
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

      if (checkboxes.length === 0) {
        alert('No applicants selected. Please select at least one applicant.');
        return;
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

      if (selectedNames.length > 0) {
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

        document.getElementById('popupMemoForm').style.display = 'block';
      } else {
        selectedApplicantsDiv.innerHTML = '';
        selectedApplicantsIdsDiv.innerHTML = '';
        alert('No applicants selected. Please select at least one applicant.');
      }
    });
  });
</script>

<?php include '_footer.php'; ?>