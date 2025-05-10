<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Gov to Gov - MWPD Filing System";
include '_head.php';
?><!-- Gov to Gov extra head content -->
<script src="includes/gov_to_gov_fixes.js" defer></script>
<?php

// --- SEARCH, FILTER, PAGINATION LOGIC (Direct Hire Style) ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 4;
if ($rows_per_page < 1) $rows_per_page = 1;

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'regular';

// Define status for different tabs
$tab_status = '';
if ($active_tab === 'regular') {
  $tab_status = 'regular';
} elseif ($active_tab === 'approved') {
  $tab_status = 'approved';
} elseif ($active_tab === 'endorsed') {
  $tab_status = 'endorsed';
}

// Calculate offset for pagination
$offset = ($page - 1) * $rows_per_page;

// Filter logic (customize for gov_to_gov fields if needed)
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
// Add more filters as needed for gov_to_gov

// Exact match logic (field:value)
$exact_match = false;
$exact_field = '';
$exact_value = '';
if (preg_match('/^(last_name|first_name|middle_name|passport_number|status):(.+)$/i', $search_query, $matches)) {
  $exact_match = true;
  $exact_field = strtolower($matches[1]);
  $exact_value = trim($matches[2]);
}

// Success and error messages
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';

// --- COUNT TOTAL RECORDS ---
try {
  // Determine which table to query based on the active tab
  $table_name = 'gov_to_gov';
  $id_field = 'g2g';
  
  // Add filter for remarks based on active tab
  if ($active_tab === 'endorsed') {
    $filter_sql .= ' AND remarks = ?';
    $filter_params[] = 'Endorsed';
  } else if ($active_tab === 'approved') {
    $filter_sql .= ' AND remarks = ?';
    $filter_params[] = 'Approved';
  } else {
    // Regular tab - exclude endorsed and approved
    $filter_sql .= ' AND (remarks NOT IN (?, ?) OR remarks IS NULL)';
    $filter_params[] = 'Endorsed';
    $filter_params[] = 'Approved';
  }
  
  if (!empty($search_query)) {
    if ($exact_match) {
      $count_sql = "SELECT COUNT(*) FROM $table_name WHERE $exact_field = ? $filter_sql";
      $count_stmt = $pdo->prepare($count_sql);
      $count_stmt->execute(array_merge([$exact_value], $filter_params));
    } else {
      // General search (search across multiple fields)
      $count_sql = "SELECT COUNT(*) FROM $table_name WHERE (last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR passport_number LIKE ?) $filter_sql";
      $count_stmt = $pdo->prepare($count_sql);
      $search_param = "%$search_query%";
      $count_stmt->execute(array_merge([$search_param, $search_param, $search_param, $search_param], $filter_params));
    }
  } else {
    $count_sql = "SELECT COUNT(*) FROM $table_name WHERE 1 $filter_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($filter_params);
  }
  $total_records = $count_stmt->fetchColumn();
  $total_pages = ceil($total_records / $rows_per_page);
} catch (PDOException $e) {
  $total_records = 0;
  $total_pages = 0;
}

?>
<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
      $currentFile = basename($_SERVER['PHP_SELF']);
      $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
      $pageTitle = ucwords(str_replace(['-', '_'], ' ', $fileWithoutExtension));
      include '_header.php';
      include 'includes/notification_modal.php'; // Include the notification system
    ?>

    <main class="main-content">
      <section class="direct-hire-wrapper">
        <!-- Top Section -->
        <div class="process-page-top">
          <div class="tabs">
            <a href="?tab=regular<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'regular' ? 'active' : '' ?>">Regular</a>
            <a href="?tab=approved<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'approved' ? 'active' : '' ?>">Approved</a>
            <a href="?tab=endorsed<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'endorsed' ? 'active' : '' ?>">Endorsed</a>
          </div>

          <div class="controls">
            <form action="" method="GET" class="search-form">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <div class="search-form">
                <input type="text" name="search" placeholder="Search or use last_name:Smith for exact match" class="search-bar" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="btn search-btn"><i class="fa fa-search"></i></button>
              </div>
            </form>

            <button class="btn filter-btn" id="showFilterBarBtn" style="background-color: #007bff; color: white; border: none; border-radius: 4px; font-family: 'Montserrat', sans-serif; font-weight: 500; height: 1rem; padding:  1rem;"><i class="fa fa-filter"></i> Filter</button>
            <a href="gov_to_gov_add.php" class="btn add-btn"><i class="fa fa-plus"></i> Add New Record</a>
            <?php if ($active_tab === 'regular'): ?>
            <button type="button" onclick="submitSelectedApplicants()" class="btn go-btn create-memo" style="background-color: #007bff; color: white; border: none; border-radius: 4px; font-family: 'Montserrat', sans-serif; font-weight: 500; height: 2rem; padding: 0.5rem 1rem;"><i class="fa fa-file-alt"></i> SUBMIT FOR APPROVAL</button>
            <?php endif; ?>
            <?php if ($active_tab === 'approved'): ?>
            <button type="button" onclick="generateMemo()" class="btn go-btn create-memo" style="background-color: #28a745; color: white; border: none; border-radius: 4px; font-family: 'Montserrat', sans-serif; font-weight: 500; height: 2rem; padding: 0.5rem 1rem;"><i class="fa fa-file"></i> GENERATE MEMO</button>
            <?php endif; ?>
            <?php if ($active_tab === 'endorsed'): ?>
            <button type="button" onclick="renewSelectedRecords()" class="btn go-btn create-memo" style="background-color: #6c757d; color: white; border: none; border-radius: 4px; font-family: 'Montserrat', sans-serif; font-weight: 500; height: 2rem; padding: 0.5rem 1rem;"><i class="fa fa-sync-alt"></i> RENEW SELECTED</button>
            <?php endif; ?>
          </div>

          <?php if (!empty($error_message)): ?>
            <div class="error-message">
              <?= htmlspecialchars($error_message) ?>
            </div>
          <?php endif; ?>

          <!-- Filter Bar Wrapper (hidden by default) -->
          <div id="filterBarWrapper" style="display:none;">
            <form class="filter-bar" method="GET" id="inlineFilterForm">
              <input type="hidden" name="tab" value="<?= htmlspecialchars($active_tab) ?>">
              <input type="hidden" name="rows" value="<?= $rows_per_page ?>">
              <input type="hidden" name="page" value="1">
              <label>Status:
                <select name="filter_status">
                  <option value="">All</option>
                  <option value="Endorsed" <?= isset($_GET['filter_status']) && $_GET['filter_status']==='Endorsed' ? 'selected' : '' ?>>Endorsed</option>
                  <option value="Regular" <?= isset($_GET['filter_status']) && $_GET['filter_status']==='Regular' ? 'selected' : '' ?>>Regular</option>
                </select>
              </label>
              <label>Jobsite:
                <input type="text" name="filter_jobsite" placeholder="Jobsite" value="<?= isset($_GET['filter_jobsite']) ? htmlspecialchars($_GET['filter_jobsite']) : '' ?>">
              </label>
              <label>Date:
                <input type="date" name="filter_date_from" value="<?= isset($_GET['filter_date_from']) ? htmlspecialchars($_GET['filter_date_from']) : '' ?>"> to
                <input type="date" name="filter_date_to" value="<?= isset($_GET['filter_date_to']) ? htmlspecialchars($_GET['filter_date_to']) : '' ?>">
              </label>
              <button type="submit" class="btn go-btn">Apply</button>
              <button type="button" class="btn clear-btn" id="clearAllFiltersBtn">Clear All</button>
            </form>
            <div class="filter-chips" id="filterChips">
              <!-- Chips will be rendered here by JS -->
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <div class="showing-results">
              <span>Showing <span id="startRecord"><?= min(($offset + 1), $total_records) ?></span>-<span id="endRecord"><?= min(($offset + $rows_per_page), $total_records) ?></span> of <span id="totalRecords"><?= $total_records ?></span> records</span>
            </div>
            
            <div class="rows-per-page" style="display:inline-block;">
              <label>
                Rows per page:
                <input type="number" min="1" id="rowsPerPage" class="rows-input" value="<?= $rows_per_page ?>">
              </label>
              <button type="button" id="resetRowsBtn" class="reset-btn">Reset</button>
            </div>
          </div>
        </div>

        <!-- Middle Section -->
        <div class="direct-hire-table">
          <table>
            <thead>
              <tr>
                <th class="select-all-header" id="select-all-label"><input type="checkbox" id="select-all-checkbox">All</th>
                <th>No.</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Passport No.</th>
                <th>Remarks</th>
                <?php if ($active_tab === 'endorsed'): ?>
                <th>Endorsement Info</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody id="g2g-tbody">
              <?php
              try {
                // Determine which table to query based on the active tab
                $table_name = 'gov_to_gov';
                $id_field = 'g2g';
                
                // Add filter for remarks based on active tab
                if ($active_tab === 'endorsed') {
                  $filter_sql .= ' AND remarks = ?';
                  $filter_params[] = 'Endorsed';
                } elseif ($active_tab === 'approved') {
                  $filter_sql .= ' AND remarks = ?';
                  $filter_params[] = 'Approved';
                } else { // regular tab
                  $filter_sql .= ' AND (remarks NOT IN (?, ?) OR remarks IS NULL)';
                  $filter_params[] = 'Endorsed';
                  $filter_params[] = 'Approved';
                }
                
                if (!empty($search_query)) {
                  if ($exact_match) {
                    $sql = "SELECT * FROM $table_name WHERE $exact_field = ? $filter_sql ORDER BY g2g DESC LIMIT ?, ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_merge([$exact_value], $filter_params, [$offset, $rows_per_page]));
                  } else {
                    $search_param = "%$search_query%";
                    $sql = "SELECT * FROM $table_name WHERE (last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR passport_number LIKE ?) $filter_sql ORDER BY g2g DESC LIMIT ?, ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_merge([$search_param, $search_param, $search_param, $search_param], $filter_params, [$offset, $rows_per_page]));
                  }
                } else {
                  $sql = "SELECT * FROM $table_name WHERE 1 $filter_sql ORDER BY g2g DESC LIMIT ?, ?";
                  $stmt = $pdo->prepare($sql);
                  $stmt->execute(array_merge($filter_params, [$offset, $rows_per_page]));
                }
                
                if ($stmt->rowCount() > 0) {
                  $index = 0;
                  while ($row = $stmt->fetch()) {
                    echo "<tr>";
                    
                    // Show checkboxes in all tabs
                    echo '<td><input type="checkbox" class="record-checkbox" name="selected_ids[]" value="'.htmlspecialchars($row[$id_field]).'"'.(isset($_GET['select_all']) && $_GET['select_all'] === '1' ? ' checked' : '').'></td>';
                    echo "<td>" . ($offset + $index + 1) . "</td>";
                    echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
                    
                    // Debug and display passport number - handle both passport_number and passport_no
                    $passport = '';
                    if (isset($row['passport_number']) && !empty($row['passport_number'])) {
                      $passport = $row['passport_number'];
                    } elseif (isset($row['passport_no']) && !empty($row['passport_no'])) {
                      $passport = $row['passport_no'];
                    }
                    echo "<td>" . htmlspecialchars($passport) . "</td>";
                    
                    // Debug and display remarks - handle both remarks and status fields
                    // For the approved tab, always show "Approved" as the remarks
                    if ($active_tab === 'approved') {
                      $remarks = 'Approved';
                    } else {
                      $remarks = (isset($row['remarks']) && !empty($row['remarks'])) ? $row['remarks'] : 'N/A';
                      // Fallback to status if remarks is empty
                      if ($remarks === 'N/A' && isset($row['status']) && !empty($row['status'])) {
                        $remarks = $row['status'];
                      }
                    }
                    echo "<td style='color: #333; font-weight: normal;'>" . htmlspecialchars($remarks) . "</td>";
                    
                    // Show endorsement info in the endorsed tab
                    if ($active_tab === 'endorsed') {
                      // Get the current date if endorsement_date is not set
                      $endorsement_date = isset($row['endorsement_date']) ? $row['endorsement_date'] : date('Y-m-d H:i:s');
                      $employer = isset($row['employer']) ? $row['employer'] : '';
                      $memo_reference = isset($row['memo_reference']) ? $row['memo_reference'] : '';
                      
                      echo "<td><span class='endorsed-info'>Endorsed on: " . date('M d, Y', strtotime($endorsement_date)) . "<br>";
                      echo "Employer: " . htmlspecialchars($employer) . "<br>";
                      echo "Memo Ref: " . htmlspecialchars($memo_reference) . "</span></td>";
                    }
                    
                    echo "</tr>";
                    $index++;
                  }
                } else {
                  echo "<tr><td colspan='".($active_tab === 'endorsed' ? '8' : '9')."' class='no-records'>No records found</td></tr>";
                }
              } catch (PDOException $e) {
                echo "<tr><td colspan='".($active_tab === 'endorsed' ? '8' : '9')."' class='error-message'>Database error: " . $e->getMessage() . "</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
        
        <!-- Bottom Section -->
        <div class="process-page-bot">
          <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= ($page - 1) ?>&rows=<?= $rows_per_page ?>&search=<?= urlencode($search_query) ?>" class="prev-btn">
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
            <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= $i ?>&rows=<?= $rows_per_page ?>&search=<?= urlencode($search_query) ?>" class="page<?= $i == $page ? ' active' : '' ?>">
              <?= $i ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
            <a href="?tab=<?= urlencode($active_tab) ?>&page=<?= ($page + 1) ?>&rows=<?= $rows_per_page ?>&search=<?= urlencode($search_query) ?>" class="next-btn">
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

<!-- Popup Memo Form -->
<div id="popupMemoForm" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fa fa-university"></i> Submit for Approval</h3>
      <span class="close" onclick="document.getElementById('popupMemoForm').style.display='none'">&times;</span>
    </div>
    <div class="modal-body">
      <form id="memoForm">
        <div id="hiddenIdsContainer"></div>
        
        <div class="form-group">
          <label for="memo_reference">Reference Number:</label>
          <input type="text" id="memo_reference" name="memo_reference" class="form-control" placeholder="e.g., MEMO-2023-001">
        </div>
        
        <div class="form-group">
          <label for="employer">Employer:</label>
          <input type="text" id="employer" name="employer" class="form-control" placeholder="Enter employer name">
        </div>
        
        <div class="form-group">
          <label>Selected Applicants:</label>
          <div id="selectedApplicants" class="selected-applicants"></div>
        </div>
        
        <div class="form-group" style="margin-top: 15px;">
          <label style="display: flex; align-items: center; cursor: pointer;">
            <input type="checkbox" id="force_update" name="force_update" value="1" style="margin-right: 8px;">
            <span>Update existing pending approvals</span>
          </label>
          <small style="color: #666; margin-top: 5px; display: block;">Check this option if you want to update records that already have pending approvals</small>
        </div>
        
        <div class="modal-footer">
          <button type="button" id="generateMemoBtn" class="btn btn-primary">Submit for Approval</button>
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('popupMemoForm').style.display='none'">Cancel</button>
        </div>
      </form>
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
  
  /* Popup Memo Form Styles */
  #popupMemoForm {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }
  
  #popupMemoForm .modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 500px;
    max-width: 90%;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }
  
  #popupMemoForm .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #007bff;
    color: white;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }
  
  #popupMemoForm .modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
  }
  
  #popupMemoForm .close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
  }
  
  #popupMemoForm .modal-body {
    padding: 20px;
  }
  
  #popupMemoForm .form-group {
    margin-bottom: 15px;
  }
  
  #popupMemoForm .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
  }
  
  #popupMemoForm .form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
  }
  
  #popupMemoForm .selected-applicants {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background-color: #f9f9f9;
  }
  
  #popupMemoForm .selected-applicants ul {
    margin: 0;
    padding-left: 20px;
  }
  
  #popupMemoForm .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
  }
  
  #popupMemoForm .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
  }
  
  #popupMemoForm .btn-primary {
    background-color: #007bff;
    color: white;
  }
  
  #popupMemoForm .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  #popupMemoForm .btn:hover {
    opacity: 0.9;
  }
  
  /* Success Notification Styles */
  .success-notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: white;
    width: 500px;
    max-width: 90%;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    z-index: 2000;
    overflow: hidden;
    animation: fadeIn 0.3s ease-out;
  }
  
  .success-content {
    padding: 25px;
    display: flex;
    align-items: flex-start;
  }
  
  .success-icon {
    font-size: 40px;
    color: #28a745;
    margin-right: 20px;
    flex-shrink: 0;
  }
  
  .success-message h3 {
    margin: 0 0 15px 0;
    color: #28a745;
    font-size: 20px;
  }
  
  .success-message p {
    margin: 5px 0;
    color: #555;
    font-size: 14px;
  }
  
  .success-message p.note {
    margin: 5px 0;
    color: #666;
    font-size: 13px;
    font-style: italic;
  }
  
  .success-actions {
    padding: 15px;
    background-color: #f8f9fa;
    text-align: right;
    border-top: 1px solid #eee;
  }
  
  .success-actions .btn {
    padding: 8px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
  }
  
  .success-actions .btn:hover {
    background-color: #0069d9;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, -60%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
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
    window.location.href = "gov_to_gov_delete.php?id=" + deleteRecordId;
  }
  
  // Handle rows per page input
  document.addEventListener('DOMContentLoaded', function() {
    // Add input event for rows per page
    document.getElementById('rowsPerPage').addEventListener('input', function() {
      let newValue = parseInt(this.value);
      
      // Handle empty or invalid values
      if (isNaN(newValue) || newValue <= 0) {
        // Don't update yet, wait for valid input
        return;
      }
      
      // Redirect to update rows per page
      window.location.href = '?tab=<?= urlencode($active_tab) ?>&page=1&rows=' + newValue + 
        '<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>';
    });
    
    // Handle blur event to catch when user leaves the field
    document.getElementById('rowsPerPage').addEventListener('blur', function() {
      let newValue = parseInt(this.value);
      
      // If empty or invalid, set to 1
      if (isNaN(newValue) || newValue <= 0) {
        this.value = 1;
        window.location.href = '?tab=<?= urlencode($active_tab) ?>&page=1&rows=1' + 
          '<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>';
      }
    });
    
    // Reset rows per page
    document.getElementById('resetRowsBtn').addEventListener('click', function() {
      window.location.href = '?tab=<?= urlencode($active_tab) ?>&page=1&rows=4' + 
        '<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>';
    });
  });
  
  // Close modal when clicking outside
  window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
      closeDeleteModal();
    }
  };
</script>

<script>
// Get the modal
var modal = document.getElementById('popupMemoForm');

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}

// New function to prepare memo generation
function prepareMemoGeneration() {
  // Clear previous selections
  const hiddenIdsContainer = document.getElementById('hiddenIdsContainer');
  hiddenIdsContainer.innerHTML = '';
  
  // Get all checked checkboxes
  const checkboxes = document.querySelectorAll('input.record-checkbox:checked');
  console.log("Number of checked checkboxes:", checkboxes.length);
  
  if (checkboxes.length === 0) {
    document.getElementById('warningModal').style.display = 'block';
    return;
  }
  
  const selectedIds = [];
  const selectedNames = [];
  
  // Process each checked checkbox
  checkboxes.forEach(function(checkbox) {
    const row = checkbox.closest('tr');
    const id = checkbox.value;
    
    // Debug - log each checkbox value
    console.log("Selected ID:", id);
    
    const name = row.querySelector('td:nth-child(3)').textContent + ', ' + 
                row.querySelector('td:nth-child(4)').textContent;
    
    selectedIds.push(id);
    selectedNames.push(name);
    
    // Create a hidden input for each selected ID
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'selected_ids[]';
    hiddenInput.value = id;
    hiddenIdsContainer.appendChild(hiddenInput);
  });
  
  // Display selected applicants in the form
  const selectedApplicantsDiv = document.getElementById('selectedApplicants');
  
  if (selectedIds.length > 0) {
    let html = '<ul>';
    selectedNames.forEach(function(name) {
      html += '<li>' + name + '</li>';
    });
    html += '</ul>';
    
    selectedApplicantsDiv.innerHTML = html;
    document.getElementById('popupMemoForm').style.display = 'block';
  } else {
    selectedApplicantsDiv.innerHTML = '';
    document.getElementById('warningModal').style.display = 'block';
  }
}

// Add event listener for the Submit for Approval button in the modal
document.addEventListener('DOMContentLoaded', function() {
  const generateMemoBtn = document.getElementById('generateMemoBtn');
  if (generateMemoBtn) {
    // Update button text
    generateMemoBtn.innerHTML = 'Submit for Approval';
    
    generateMemoBtn.addEventListener('click', function(event) {
      // Prevent default form submission
      event.preventDefault();
      
      console.log("Submit for Approval button clicked");
      
      // Close the modal immediately
      document.getElementById('popupMemoForm').style.display = 'none';
      
      // Show loading indicator in a visible area
      const loadingIndicator = document.createElement('div');
      loadingIndicator.className = 'loading-indicator';
      loadingIndicator.innerHTML = '<i class="fa fa-spinner fa-spin fa-3x"></i> Processing...';
      loadingIndicator.style.position = 'fixed';
      loadingIndicator.style.top = '50%';
      loadingIndicator.style.left = '50%';
      loadingIndicator.style.transform = 'translate(-50%, -50%)';
      loadingIndicator.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
      loadingIndicator.style.padding = '20px';
      loadingIndicator.style.borderRadius = '5px';
      loadingIndicator.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.3)';
      loadingIndicator.style.zIndex = '9999';
      document.body.appendChild(loadingIndicator);
      
      // Get all the selected IDs
      const selectedIdsInputs = document.querySelectorAll('#hiddenIdsContainer input[name="selected_ids[]"]');
      const selectedIds = Array.from(selectedIdsInputs).map(input => input.value);
      console.log("Selected IDs for approval:", selectedIds);
      
      // Submit records for approval
      const approvalXhr = new XMLHttpRequest();
      approvalXhr.open('POST', 'g2g_submit_for_approval.php', true);
      approvalXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      
      // Prepare the data for approval - only send selected IDs and minimal required info
      const approvalData = new URLSearchParams();
      const memoRef = document.getElementById('memo_reference').value || 'Memo ' + new Date().toISOString().slice(0, 10);
      const employer = document.getElementById('employer').value || 'Not specified';
      
      approvalData.append('memo_reference', memoRef);
      approvalData.append('employer', employer);
      
      // Only add the selected IDs
      selectedIds.forEach(id => {
        approvalData.append('selected_ids[]', id);
      });
      
      console.log("Sending approval data:", memoRef, employer, selectedIds);
      
      // Handle the approval response
      approvalXhr.onload = function() {
        console.log("Approval response received:", approvalXhr.status);
        console.log("Response text:", approvalXhr.responseText);
        
        // Remove loading indicator
        document.body.removeChild(loadingIndicator);
        
        if (approvalXhr.status === 200) {
          try {
            const response = JSON.parse(approvalXhr.responseText);
            console.log("Parsed approval response:", response);
            
            if (response.success) {
              // Create a simple success notification
              const successNotification = document.createElement('div');
              successNotification.className = 'success-notification';
              
              // Check if there were any rejected IDs due to pending approvals
              if (response.already_pending && response.rejected_ids && response.rejected_ids.length > 0) {
                // Some records were already pending approval
                successNotification.innerHTML = `
                  <div class="success-content">
                    <div class="success-icon"><i class="fa fa-check-circle"></i></div>
                    <div class="success-message">
                      <h3>Submitted for Approval</h3>
                      <p class="note">${response.submitted_ids.length} record(s) submitted. ${response.rejected_ids.length} record(s) already pending.</p>
                    </div>
                  </div>
                  <div class="success-actions">
                    <button class="btn btn-primary" onclick="window.location.reload()">OK</button>
                  </div>
                `;
              } else {
                // All records were submitted successfully
                successNotification.innerHTML = `
                  <div class="success-content">
                    <div class="success-icon"><i class="fa fa-check-circle"></i></div>
                    <div class="success-message">
                      <h3>Submitted for Approval</h3>
                    </div>
                  </div>
                  <div class="success-actions">
                    <button class="btn btn-primary" onclick="window.location.reload()">OK</button>
                  </div>
                `;
              }
              
              document.body.appendChild(successNotification);
              
              // Auto-redirect after 3 seconds
              setTimeout(function() {
                window.location.href = 'gov_to_gov.php?success=' + encodeURIComponent('Submitted for approval');
              }, 3000);
            } else {
              alert('Error submitting records for approval: ' + response.message);
            }
          } catch (e) {
            console.error("Error parsing approval response:", e);
            alert('Error processing approval response.');
          }
        } else {
          console.error("Approval request failed:", approvalXhr.status);
          alert('Error submitting records for approval. Please try again.');
        }
      };
      
      // Handle network errors for approval
      approvalXhr.onerror = function(e) {
        console.error("Network error during approval submission:", e);
        // Remove loading indicator
        document.body.removeChild(loadingIndicator);
        alert('Network error occurred while submitting records for approval.');
      };
      
      // Send the approval request with only the selected IDs
      console.log("Sending approval request with data:", approvalData.toString());
      approvalXhr.send(approvalData);
    });
  }
});
</script>

<script>
// Function to renew selected endorsed records and move them back to regular status
function renewSelectedRecords() {
  // Clear previous selections
  const hiddenIdsContainer = document.getElementById('hiddenIdsContainer');
  hiddenIdsContainer.innerHTML = '';
  
  // Get all checked checkboxes
  const checkboxes = document.querySelectorAll('input.record-checkbox:checked');
  console.log("Number of checked checkboxes for renewal:", checkboxes.length);
  
  if (checkboxes.length === 0) {
    document.getElementById('warningModal').style.display = 'block';
    return;
  }
  
  // Confirmation dialog
  const confirmRenew = confirm(`Are you sure you want to renew ${checkboxes.length} selected record(s)? This will move them back to Regular status.`);
  
  if (!confirmRenew) {
    return;
  }
  
  // Get all selected IDs
  const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
  
  // Create a loading indicator
  const loadingIndicator = document.createElement('div');
  loadingIndicator.className = 'loading-overlay';
  loadingIndicator.innerHTML = `
    <div class="loading-spinner"></div>
    <div class="loading-text">Renewing records...</div>
  `;
  document.body.appendChild(loadingIndicator);
  
  // Create an AJAX request to process the renewals
  const renewXhr = new XMLHttpRequest();
  renewXhr.open('POST', 'process_gov_to_gov_renewal.php', true);
  
  // Create form data for the request
  const renewData = new FormData();
  renewData.append('action', 'renew_records');
  
  // Add the selected IDs
  selectedIds.forEach(id => {
    renewData.append('selected_ids[]', id);
  });
  
  // Handle the renewal response
  renewXhr.onload = function() {
    // Remove loading indicator
    document.body.removeChild(loadingIndicator);
    
    if (renewXhr.status === 200) {
      try {
        const response = JSON.parse(renewXhr.responseText);
        
        if (response.success) {
          // Create a success notification
          const successNotification = document.createElement('div');
          successNotification.className = 'success-notification';
          successNotification.innerHTML = `
            <div class="success-content">
              <div class="success-icon"><i class="fa fa-check-circle"></i></div>
              <div class="success-message">
                <h3>Renewal Successful</h3>
                <p class="note">${response.updated_count} record(s) have been renewed and moved to Regular status</p>
              </div>
            </div>
            <div class="success-actions">
              <button class="btn btn-primary" onclick="window.location.reload()">OK</button>
            </div>
          `;
          
          document.body.appendChild(successNotification);
          
          // Auto-redirect after 3 seconds
          setTimeout(function() {
            window.location.href = 'gov_to_gov.php?tab=regular&success=' + encodeURIComponent('Records renewed successfully');
          }, 3000);
        } else {
          alert('Error renewing records: ' + response.message);
        }
      } catch (e) {
        console.error("Error parsing renewal response:", e);
        alert('Error processing renewal response.');
      }
    } else {
      console.error("Renewal request failed:", renewXhr.status);
      alert('Error renewing records. Please try again.');
    }
  };
  
  // Handle network errors
  renewXhr.onerror = function(e) {
    console.error("Network error during renewal:", e);
    document.body.removeChild(loadingIndicator);
    alert('Network error occurred while renewing records.');
  };
  
  // Send the request
  renewXhr.send(renewData);
}

// Function to generate memo for approved records and move them to endorsed status
function generateMemo() {
  // Clear previous selections
  const hiddenIdsContainer = document.getElementById('hiddenIdsContainer');
  hiddenIdsContainer.innerHTML = '';
  
  // Get all checked checkboxes
  const checkboxes = document.querySelectorAll('input.record-checkbox:checked');
  console.log("Number of checked checkboxes for memo generation:", checkboxes.length);
  
  if (checkboxes.length === 0) {
    document.getElementById('warningModal').style.display = 'block';
    return;
  }
  
  // Create a modal to collect memo information
  const memoModal = document.createElement('div');
  memoModal.className = 'modal';
  memoModal.style.display = 'block';
  memoModal.id = 'memoModal';
  
  memoModal.innerHTML = `
    <div class="modal-content">
      <div class="modal-header">
        <h3>Generate Memo</h3>
        <span class="close" onclick="document.getElementById('memoModal').style.display='none'">&times;</span>
      </div>
      <div class="modal-body">
        <p>You are about to generate a memo for ${checkboxes.length} selected record(s). Please provide the following information:</p>
        <div class="form-group">
          <label for="memoReference">Memo Reference:</label>
          <input type="text" id="memoReference" placeholder="e.g., MEMO-2025-05-06-001" required>
        </div>
        <div class="form-group">
          <label for="employerName">Employer Name:</label>
          <input type="text" id="employerName" placeholder="Enter employer name" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="document.getElementById('memoModal').style.display='none'">Cancel</button>
        <button class="btn btn-primary" onclick="processAndGenerateMemo()">Generate Memo</button>
      </div>
    </div>
  `;
  
  document.body.appendChild(memoModal);
}

// Process and generate memo for selected records
function processAndGenerateMemo() {
  // Get the memo reference and employer name
  const memoReference = document.getElementById('memoReference').value;
  const employerName = document.getElementById('employerName').value;
  
  if (!memoReference || !employerName) {
    alert('Please fill in all required fields');
    return;
  }
  
  // Get all checked checkboxes
  const checkboxes = document.querySelectorAll('input.record-checkbox:checked');
  const selectedIds = Array.from(checkboxes).map(checkbox => checkbox.value);
  
  // Close the memo modal
  document.getElementById('memoModal').style.display = 'none';
  
  // Create a form to submit the data to generate_memo.php
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'generate_memo.php';
  form.target = '_blank'; // Open in a new tab/window
  
  // Add employer name
  const employerInput = document.createElement('input');
  employerInput.type = 'hidden';
  employerInput.name = 'employer';
  employerInput.value = employerName;
  form.appendChild(employerInput);
  
  // Add memo date (current date)
  const memoDateInput = document.createElement('input');
  memoDateInput.type = 'hidden';
  memoDateInput.name = 'memo_date';
  memoDateInput.value = new Date().toISOString().split('T')[0];
  form.appendChild(memoDateInput);
  
  // Add source indicator
  const sourceInput = document.createElement('input');
  sourceInput.type = 'hidden';
  sourceInput.name = 'source';
  sourceInput.value = 'gov_to_gov';
  form.appendChild(sourceInput);
  
  // Add memo reference as a custom field
  const memoRefInput = document.createElement('input');
  memoRefInput.type = 'hidden';
  memoRefInput.name = 'memo_reference';
  memoRefInput.value = memoReference;
  form.appendChild(memoRefInput);
  
  // Add the selected IDs
  selectedIds.forEach(id => {
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'selected_ids[]';
    idInput.value = id;
    form.appendChild(idInput);
  });
  
  // Add the form to the document body (required for submission)
  document.body.appendChild(form);
  
  // Now also update the records to Endorsed status in the background
  // Create a separate AJAX request to update the status
  const statusXhr = new XMLHttpRequest();
  statusXhr.open('POST', 'process_gov_to_gov_memo.php', true);
  
  // Create form data for status update
  const statusData = new FormData();
  statusData.append('action', 'update_status');
  statusData.append('memo_reference', memoReference);
  statusData.append('employer', employerName);
  
  // Add the selected IDs
  selectedIds.forEach(id => {
    statusData.append('selected_ids[]', id);
  });
  
  // Handle the status update response
  statusXhr.onload = function() {
    if (statusXhr.status === 200) {
      console.log('Records updated to Endorsed status successfully');
    } else {
      console.error('Failed to update records to Endorsed status');
    }
  };
  
  // Send the request to update statuses
  statusXhr.send(statusData);
  
  // Submit the form to generate the memo
  form.submit();
  
  // Remove the form from the document after submission
  setTimeout(() => {
    document.body.removeChild(form);
  }, 500);
}

// Function to submit selected applicants for approval
function submitSelectedApplicants() {
  // Clear previous selections
  const hiddenIdsContainer = document.getElementById('hiddenIdsContainer');
  hiddenIdsContainer.innerHTML = '';
  
  // Get all checked checkboxes
  const checkboxes = document.querySelectorAll('input.record-checkbox:checked');
  console.log("Number of checked checkboxes:", checkboxes.length);
  
  if (checkboxes.length === 0) {
    document.getElementById('warningModal').style.display = 'block';
    return;
  }
  
  const selectedIds = [];
  const selectedNames = [];
  
  // Process each checked checkbox
  checkboxes.forEach(function(checkbox) {
    const row = checkbox.closest('tr');
    const id = checkbox.value;
    
    // Debug - log each checkbox value
    console.log("Selected ID:", id);
    
    const name = row.querySelector('td:nth-child(3)').textContent + ', ' + 
                row.querySelector('td:nth-child(4)').textContent;
    
    selectedIds.push(id);
    selectedNames.push(name);
    
    // Create a hidden input for each selected ID
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'selected_ids[]';
    hiddenInput.value = id;
    hiddenIdsContainer.appendChild(hiddenInput);
  });
  
  // Display selected applicants in the form
  const selectedApplicantsDiv = document.getElementById('selectedApplicants');
  
  if (selectedIds.length > 0) {
    let html = '<ul>';
    selectedNames.forEach(function(name) {
      html += '<li>' + name + '</li>';
    });
    html += '</ul>';
    
    selectedApplicantsDiv.innerHTML = html;
    document.getElementById('popupMemoForm').style.display = 'block';
  } else {
    selectedApplicantsDiv.innerHTML = '';
    document.getElementById('warningModal').style.display = 'block';
  }
}

// Add event listener for the Submit for Approval button in the modal
document.addEventListener('DOMContentLoaded', function() {
  const generateMemoBtn = document.getElementById('generateMemoBtn');
  if (generateMemoBtn) {
    // Update button text
    generateMemoBtn.innerHTML = 'Submit for Approval';
    
    generateMemoBtn.addEventListener('click', function(event) {
      // Prevent default form submission
      event.preventDefault();
      
      console.log("Submit for Approval button clicked");
      
      // Close the modal immediately
      document.getElementById('popupMemoForm').style.display = 'none';
      
      // Show loading indicator in a visible area
      const loadingIndicator = document.createElement('div');
      loadingIndicator.className = 'loading-indicator';
      loadingIndicator.innerHTML = '<i class="fa fa-spinner fa-spin fa-3x"></i> Processing...';
      loadingIndicator.style.position = 'fixed';
      loadingIndicator.style.top = '50%';
      loadingIndicator.style.left = '50%';
      loadingIndicator.style.transform = 'translate(-50%, -50%)';
      loadingIndicator.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
      loadingIndicator.style.padding = '20px';
      loadingIndicator.style.borderRadius = '5px';
      loadingIndicator.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.3)';
      loadingIndicator.style.zIndex = '9999';
      document.body.appendChild(loadingIndicator);
      
      // Get all the selected IDs
      const selectedIdsInputs = document.querySelectorAll('#hiddenIdsContainer input[name="selected_ids[]"]');
      const selectedIds = Array.from(selectedIdsInputs).map(input => input.value);
      console.log("Selected IDs for approval:", selectedIds);
      
      // Submit records for approval
      const approvalXhr = new XMLHttpRequest();
      approvalXhr.open('POST', 'g2g_submit_for_approval.php', true);
      approvalXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      
      // Prepare the data for approval - only send selected IDs and minimal required info
      const approvalData = new URLSearchParams();
      const memoRef = document.getElementById('memo_reference').value || 'Memo ' + new Date().toISOString().slice(0, 10);
      const employer = document.getElementById('employer').value || 'Not specified';
      
      approvalData.append('memo_reference', memoRef);
      approvalData.append('employer', employer);
      
      // Only add the selected IDs
      selectedIds.forEach(id => {
        approvalData.append('selected_ids[]', id);
      });
      
      console.log("Sending approval data:", memoRef, employer, selectedIds);
      
      // Handle the approval response
      approvalXhr.onload = function() {
        console.log("Approval response received:", approvalXhr.status);
        console.log("Response text:", approvalXhr.responseText);
        
        // Remove loading indicator
        document.body.removeChild(loadingIndicator);
        
        if (approvalXhr.status === 200) {
          try {
            const response = JSON.parse(approvalXhr.responseText);
            console.log("Parsed approval response:", response);
            
            if (response.success) {
              // Create a simple success notification
              const successNotification = document.createElement('div');
              successNotification.className = 'success-notification';
              
              // Check if there were any rejected IDs due to pending approvals
              if (response.already_pending && response.rejected_ids && response.rejected_ids.length > 0) {
                // Some records were already pending approval
                successNotification.innerHTML = `
                  <div class="success-content">
                    <div class="success-icon"><i class="fa fa-check-circle"></i></div>
                    <div class="success-message">
                      <h3>Submitted for Approval</h3>
                      <p class="note">${response.submitted_ids.length} record(s) submitted. ${response.rejected_ids.length} record(s) already pending.</p>
                    </div>
                  </div>
                  <div class="success-actions">
                    <button class="btn btn-primary" onclick="window.location.reload()">OK</button>
                  </div>
                `;
              } else {
                // All records were submitted successfully
                successNotification.innerHTML = `
                  <div class="success-content">
                    <div class="success-icon"><i class="fa fa-check-circle"></i></div>
                    <div class="success-message">
                      <h3>Submitted for Approval</h3>
                    </div>
                  </div>
                  <div class="success-actions">
                    <button class="btn btn-primary" onclick="window.location.reload()">OK</button>
                  </div>
                `;
              }
              
              document.body.appendChild(successNotification);
              
              // Auto-redirect after 3 seconds
              setTimeout(function() {
                window.location.href = 'gov_to_gov.php?success=' + encodeURIComponent('Submitted for approval');
              }, 3000);
            } else {
              alert('Error submitting records for approval: ' + response.message);
            }
          } catch (e) {
            console.error("Error parsing approval response:", e);
            alert('Error processing approval response.');
          }
        } else {
          console.error("Approval request failed:", approvalXhr.status);
          alert('Error submitting records for approval. Please try again.');
        }
      };
      
      // Handle network errors for approval
      approvalXhr.onerror = function(e) {
        console.error("Network error during approval submission:", e);
        // Remove loading indicator
        document.body.removeChild(loadingIndicator);
        alert('Network error occurred while submitting records for approval.');
      };
      
      // Send the approval request with only the selected IDs
      console.log("Sending approval request with data:", approvalData.toString());
      approvalXhr.send(approvalData);
    });
  }
});
</script>

<script>
// Double-click functionality for table rows
document.addEventListener('DOMContentLoaded', function() {
  // Use event delegation for better performance and to catch dynamically added rows
  document.querySelector('#g2g-tbody').addEventListener('dblclick', function(e) {
    // Get the closest tr parent - this is the clicked row
    const row = e.target.closest('tr');
    if (!row) return; // Not a table row
    
    // Don't trigger if clicking on checkbox or action buttons
    if (e.target.closest('input[type="checkbox"]') || e.target.closest('.action-icons')) {
      return;
    }
    
    // Get the record ID from the checkbox value or the first action link
    const checkbox = row.querySelector('input[type="checkbox"]');
    if (checkbox && checkbox.value) {
      // Navigate to the view page with this ID
      window.location.href = 'gov_to_gov_view.php?id=' + checkbox.value;
    }
  });
  
  // Add cursor style to indicate rows are clickable
  const style = document.createElement('style');
  style.textContent = '#g2g-tbody tr { cursor: pointer; }';
  document.head.appendChild(style);
});
  
  // Select All functionality
  const selectAllLabel = document.getElementById('select-all-label');
  const selectAllCheckbox = document.getElementById('select-all-checkbox');
  
  if (selectAllLabel && selectAllCheckbox) {
    // Make the entire header cell clickable
    selectAllLabel.addEventListener('click', function(e) {
      // Prevent triggering twice when clicking directly on the checkbox
      if (e.target !== selectAllCheckbox) {
        selectAllCheckbox.checked = !selectAllCheckbox.checked;
        toggleAllCheckboxes(selectAllCheckbox.checked);
      }
    });
    
    // Handle checkbox click
    selectAllCheckbox.addEventListener('change', function() {
      toggleAllCheckboxes(this.checked);
    });
    
    // Function to toggle all checkboxes
    function toggleAllCheckboxes(checked) {
      const checkboxes = document.querySelectorAll('#g2g-tbody input[type="checkbox"]');
      checkboxes.forEach(checkbox => {
        checkbox.checked = checked;
      });
      
      // Update visual indication
      if (checked) {
        selectAllLabel.classList.add('all-selected');
      } else {
        selectAllLabel.classList.remove('all-selected');
      }
    }
  }
});

// Filter bar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const showBtn = document.getElementById('showFilterBarBtn');
  const filterBarWrapper = document.getElementById('filterBarWrapper');
  
  // Filter bar is hidden by default
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
  
  // Reset rows per page
  document.getElementById('resetRowsBtn').addEventListener('click', function() {
    document.getElementById('rowsPerPage').value = 4;
    document.getElementById('rowsPerPageForm').submit();
  });
  
  // Render filter chips for active filters
  function renderFilterChips() {
    const params = new URLSearchParams(window.location.search);
    const chips = [];
    const filterLabels = {
      filter_status: 'Status',
      filter_jobsite: 'Jobsite',
      filter_date_from: 'Date From',
      filter_date_to: 'Date To'
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

<script>
// Function to handle renewing an endorsed record
function renewRecord(recordId) {
  if (confirm('Are you sure you want to renew this record?')) {
    // Show loading spinner
    const renewBtn = document.querySelector(`button[data-id="${recordId}"]`);
    const originalText = renewBtn.innerHTML;
    renewBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    renewBtn.disabled = true;
    
    // Create a new XMLHttpRequest
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'renew_endorsed_record.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    // Set up a handler for when the request finishes
    xhr.onload = function() {
      if (xhr.status === 200) {
        try {
          const response = JSON.parse(xhr.responseText);
          console.log("Parsed renewal response:", response);
          
          if (response.success) {
            // Success - refresh the page
            window.location.href = 'gov_to_gov.php?tab=regular&success=' + encodeURIComponent(response.message);
          } else {
            // Error - show message and reset button
            alert('Error: ' + response.message);
            renewBtn.innerHTML = originalText;
            renewBtn.disabled = false;
          }
        } catch (e) {
          alert('Error processing response');
          renewBtn.innerHTML = originalText;
          renewBtn.disabled = false;
        }
      } else {
        alert('Error renewing record. Please try again.');
        renewBtn.innerHTML = originalText;
        renewBtn.disabled = false;
      }
    };
    
    // Handle network errors
    xhr.onerror = function(e) {
      alert('Network error occurred while renewing record.');
      renewBtn.innerHTML = originalText;
      renewBtn.disabled = false;
    };
    
    // Send the request
    xhr.send('record_id=' + recordId);
  }
}
</script>

<script>
// Use the existing filter button to toggle filter bar
document.addEventListener('DOMContentLoaded', function() {
  const showBtn = document.getElementById('showFilterBarBtn');
  const filterBarWrapper = document.getElementById('filterBarWrapper');
  
  // Filter bar is hidden by default
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
  
  // Reset rows per page
  document.getElementById('resetRowsBtn').addEventListener('click', function() {
    document.getElementById('rowsPerPage').value = 4;
    document.getElementById('rowsPerPageForm').submit();
  });
  
  // Render filter chips for active filters
  function renderFilterChips() {
    const params = new URLSearchParams(window.location.search);
    const chips = [];
    const filterLabels = {
      filter_status: 'Status',
      filter_jobsite: 'Jobsite',
      filter_date_from: 'Date From',
      filter_date_to: 'Date To'
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