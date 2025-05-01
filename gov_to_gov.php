<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Gov to Gov - MWPD Filing System";
include '_head.php';

// --- SEARCH, FILTER, PAGINATION LOGIC (Direct Hire Style) ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows_per_page = isset($_GET['rows']) ? (int)$_GET['rows'] : 6;
if ($rows_per_page < 1) $rows_per_page = 1;

// Handle tab selection
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'regular';

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
  } else {
    $filter_sql .= ' AND (remarks != ? OR remarks IS NULL)';
    $filter_params[] = 'Endorsed';
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
    ?>

    <main class="main-content">
      <section class="direct-hire-wrapper">
        <!-- Top Section -->
        <div class="direct-hire-top">
          <div class="tabs">
            <a href="?tab=regular<?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="tab <?= $active_tab === 'regular' ? 'active' : '' ?>">Regular</a>
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

            <button class="btn filter-btn" id="showFilterBarBtn"><i class="fa fa-filter"></i> Filter</button>
            <a href="gov_to_gov_add.php" class="btn add-btn"><i class="fa fa-plus"></i> Add New Record</a>
            <?php if ($active_tab !== 'endorsed'): ?>
            <button type="button" onclick="submitSelectedApplicants()" class="btn go-btn create-memo"><i class="fa fa-file-alt"></i> <b>SUBMIT FOR APPROVAL</b></button>
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
              <label>
                Rows per page:
                <input type="number" name="rows" min="1" max="<?= $total_pages ?>" value="<?= $rows_per_page ?>" id="rowsInput">
              </label>
              <button type="button" class="btn go-btn reset-btn" id="resetRowsBtn">Reset</button>
            </form>
          </div>
        </div>

        <!-- Middle Section -->
        <div class="direct-hire-table">
          <table>
            <thead>
              <tr>
                <?php if ($active_tab !== 'endorsed'): ?>
                <th class="select-all-header" id="select-all-label"><input type="checkbox" id="select-all-checkbox">All</th>
                <?php endif; ?>
                <th>No.</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Passport No.</th>
                <th>Remarks</th>
                <?php if ($active_tab === 'endorsed'): ?>
                <th>Endorsement Info</th>
                <?php endif; ?>
                <th>Action</th>
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
                } else {
                  $filter_sql .= ' AND (remarks != ? OR remarks IS NULL)';
                  $filter_params[] = 'Endorsed';
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
                    
                    // Only show checkboxes in the regular tab
                    if ($active_tab !== 'endorsed') {
                      echo '<td><input type="checkbox" class="record-checkbox" name="selected_ids[]" value="'.htmlspecialchars($row[$id_field]).'"></td>';
                    }
                    
                    echo "<td>" . ($offset + $index + 1) . "</td>";
                    echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['passport_number']) . "</td>";
                    echo "<td>" . htmlspecialchars(isset($row['remarks']) ? $row['remarks'] : 'N/A') . "</td>";
                    
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
                    
                    echo '<td class="action-icons">';
                    
                    if ($active_tab === 'endorsed') {
                      // Actions for endorsed records
                      echo '<a href="gov_to_gov_view.php?id=' . urlencode($row[$id_field]) . '&endorsed=1" title="View Record"><i class="fa fa-eye"></i></a>';
                      echo '<button type="button" class="btn-renew" title="Renew Record" data-id="' . $row[$id_field] . '" onclick="renewRecord(' . $row[$id_field] . ')"><i class="fa fa-sync-alt"></i></button>';
                    } else {
                      // Actions for regular records
                      echo '<a href="gov_to_gov_view.php?id=' . urlencode($row[$id_field]) . '" title="View Record"><i class="fa fa-eye"></i></a>';
                      echo '<a href="gov_to_gov_edit.php?id=' . urlencode($row[$id_field]) . '" title="Edit Record"><i class="fa fa-edit"></i></a>';
                      echo '<a href="#" onclick="deleteRecord(' . $row[$id_field] . '); return false;" title="Delete Record"><i class="fa fa-trash-alt"></i></a>';
                    }
                    
                    echo '</td>';
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
        
        <div class="modal-footer">
          <button type="button" id="generateMemoBtn" class="btn btn-primary">Submit for Approval</button>
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('popupMemoForm').style.display='none'">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

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
              // Show success message and refresh the page
              alert('Records submitted for approval successfully. The Regional Director will review and approve them.');
              window.location.href = 'gov_to_gov.php?success=' + encodeURIComponent(response.message);
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
              // Show success message and refresh the page
              alert('Records submitted for approval successfully. The Regional Director will review and approve them.');
              window.location.href = 'gov_to_gov.php?success=' + encodeURIComponent(response.message);
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
  const tableRows = document.querySelectorAll('#g2g-tbody tr');
  tableRows.forEach(row => {
    row.addEventListener('dblclick', function(e) {
      // Don't trigger if clicking on checkbox or action buttons
      if (e.target.closest('input[type="checkbox"]') || e.target.closest('.action-icons')) {
        return;
      }
      
      // Get the record ID from the row
      const viewLink = row.querySelector('a[href*="gov_to_gov_view.php"]');
      if (viewLink) {
        window.location.href = viewLink.getAttribute('href');
      }
    });
    
    // Add cursor style to indicate clickable
    row.style.cursor = 'pointer';
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
    document.getElementById('rowsInput').value = 6;
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
    xhr.onerror = function() {
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
    document.getElementById('rowsInput').value = 6;
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