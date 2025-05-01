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

// --- COUNT TOTAL RECORDS ---
try {
  if (!empty($search_query)) {
    if ($exact_match) {
      $count_sql = "SELECT COUNT(*) FROM gov_to_gov WHERE $exact_field = ? $filter_sql";
      $count_stmt = $pdo->prepare($count_sql);
      $count_stmt->execute(array_merge([$exact_value], $filter_params));
    } else {
      // General search (search across multiple fields)
      $count_sql = "SELECT COUNT(*) FROM gov_to_gov WHERE (last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR passport_number LIKE ?) $filter_sql";
      $count_stmt = $pdo->prepare($count_sql);
      $search_param = "%$search_query%";
      $count_stmt->execute(array_merge([$search_param, $search_param, $search_param, $search_param], $filter_params));
    }
  } else {
    $count_sql = "SELECT COUNT(*) FROM gov_to_gov WHERE 1 $filter_sql";
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
<style>
  .popupForm {
    display: none;
    position: fixed;
    top: 5%;
    left: 50%;
    transform: translateX(-50%);
    background: #fff;
    width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 25px 30px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    z-index: 999;
    font-family: 'Segoe UI', sans-serif;
  }

  .popupForm h3 {
    margin-bottom: 20px;
    text-align: center;
    color: #333;
  }

  .popupForm label {
    display: block;
    margin-top: 12px;
    margin-bottom: 4px;
    font-size: 14px;
    color: #444;
  }

  .popupForm input,
  .popupForm select {
    width: 100%;
    padding: 8px 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 6px;
    transition: border-color 0.3s;
  }

  .popupForm input:focus,
  .popupForm select:focus {
    border-color: #5a9bf9;
    outline: none;
  }

  .popupForm button {
    margin-top: 20px;
    padding: 10px 15px;
    font-size: 14px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
  }

  .popupForm button[type="submit"] {
    background-color: #007bff;
    color: white;
    margin-right: 10px;
  }

  .popupForm button[type="button"] {
    background-color: #ccc;
    color: #333;
  }

  .custom-button {
    padding: 0.5rem 1rem;
    border: none;
    background-color: #eee;
    cursor: pointer;
    border-radius: 6px;
    font-size: 1rem;
    text-decoration: none;
  }

  .custom-button.create-memo {
    background-color: #007bff;
    color: white;
  }

  .custom-button.add-applicant {
    background-color: #007bff;
    color: white;
  }

  .custom-button:hover {
    background-color: #ddd;
  }

  .go-btn {
    background-color: #007bff !important;
    color: #fff !important;
    border: none;
    border-radius: 4px;
    padding: 5px 10px;
    margin: 0 2px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;
  }

  .go-btn:hover {
    background-color: #0056b3 !important;
  }

  .action-icons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    align-items: center;
  }

  .action-icons a {
    color: inherit;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-size: 1rem;
    transition: background 0.2s, color 0.2s;
    border: none;
    background: none;
  }

  .action-icons a .fa-eye {
    color: #007bff;
  }

  .action-icons a .fa-edit {
    color: #28a745;
  }

  .action-icons a .fa-trash-alt,
  .action-icons a .fa-trash {
    color: #dc3545;
  }

  .action-icons a:hover {
    background: #f0f2f7;
  }

  #searchInput {
    height: 35px;
    width: 220px;
    padding: 10px;
    font-size: 14px;
    border: none;
    border-radius: 25px;
    background-color: #f5f5f5;
    color: #333;
    transition: background-color 0.3s ease-in-out;
    text-align: center;
  }

  #searchInput:focus {
    outline: none;
    background-color: #e0e0e0;
  }

  table {
    margin-top: 10px;
    width: 100%;
    border-collapse: collapse;
    font-family: 'Montserrat', sans-serif;
    background-color: #fff;
  }

  th,
  td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #ddd;
  }

  th {
    background-color: #007bff !important;
    font-weight: bold;
    color: #fff !important;
  }

  td {
    color: #444;
    font-weight: 600;
  }

  table thead {
    background-color: #f8f8f8;
  }

  table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
  }

  table tbody tr:hover {
    background-color: #e9e9e9;
  }

  table td,
  table th {
    word-wrap: break-word;
    max-width: 150px;
  }

  table td {
    font-size: 14px;
  }

  .gtog-record-form-wrapper {
    margin-bottom: 24px;
  }

  .action-icons {
    border-left: none !important;
  }

  .action-icons a {
    border: none !important;
  }

  .last-column {
    border-right: none !important;
  }
</style>

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
      <div class="container gtog-section">
        <!-- Gov-to-Gov Full Record Form (for Add, View, Edit) -->
        <div class="gtog-record-form-wrapper" style="display:none; margin-bottom:24px;">
          <form id="gtogRecordForm" class="record-form" enctype="multipart/form-data">
            <input type="hidden" name="g2g_id" id="g2g_id">
            <div class="form-row">
              <div class="form-group"><label>Last Name</label><input type="text" name="last_name" id="last_name" required></div>
              <div class="form-group"><label>First Name</label><input type="text" name="first_name" id="first_name" required></div>
              <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name" id="middle_name"></div>
              <div class="form-group"><label>Sex</label><select name="sex" id="sex">
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>Birth Date</label><input type="date" name="birth_date" id="birth_date"></div>
              <div class="form-group"><label>Age</label><input type="number" name="age" id="age"></div>
              <div class="form-group"><label>Height</label><input type="text" name="height" id="height"></div>
              <div class="form-group"><label>Weight</label><input type="text" name="weight" id="weight"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>Educational Attainment</label><input type="text" name="educational_attainment" id="educational_attainment"></div>
              <div class="form-group"><label>Present Address</label><input type="text" name="present_address" id="present_address"></div>
              <div class="form-group"><label>Email Address</label><input type="email" name="email_address" id="email_address"></div>
              <div class="form-group"><label>Contact Number</label><input type="text" name="contact_number" id="contact_number"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>Passport Number</label><input type="text" name="passport_number" id="passport_number"></div>
              <div class="form-group"><label>Passport Validity</label><input type="date" name="passport_validity" id="passport_validity"></div>
              <div class="form-group"><label>ID Presented</label><input type="text" name="id_presented" id="id_presented"></div>
              <div class="form-group"><label>ID Number</label><input type="text" name="id_number" id="id_number"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>With Job Experience</label><select name="with_job_experience" id="with_job_experience">
                  <option>Yes</option>
                  <option>No</option>
                </select></div>
              <div class="form-group"><label>Company Name/Year Started–Ended</label><input type="text" name="company_name_year_started_ended" id="company_name_year_started_ended"></div>
              <div class="form-group"><label>With Other Experience</label><select name="with_job_experience_aside_from" id="with_job_experience_aside_from">
                  <option>Yes</option>
                  <option>No</option>
                </select></div>
              <div class="form-group"><label>Name/Company/Year Started–Ended</label><input type="text" name="name_company_year_started_ended" id="name_company_year_started_ended"></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label>Remarks</label><input type="text" name="remarks" id="remarks"></div>
              <div class="form-group"><label>Date Received by Region</label><input type="date" name="date_received_by_region" id="date_received_by_region"></div>
            </div>
            <!-- <div class="form-row">
              <div class="form-group" style="flex: 1 1 100%;"><label>Upload Documents</label><input type="file" name="documents[]" id="documents" multiple></div>
            </div> -->
            <div class="form-actions">
              <button type="submit" id="gtogFormSaveBtn" class="btn btn-primary">Save</button>
              <button type="button" class="btn btn-cancel" onclick="hideGtogForm()">Cancel</button>
            </div>
          </form>
        </div>

        <!-- Gov-to-Gov Record View (like Direct Hire View) -->
        <div id="gtogRecordViewWrapper" class="record-view-wrapper" style="display:none; margin-bottom:24px;">
          <div class="record-header">
            <div class="record-title">
              <h2 id="gtog_view_fullname"></h2>
              <div class="record-subtitle">
                <span class="record-type">Gov-to-Gov</span>
              </div>
            </div>
            <div class="record-actions">
              <button type="button" class="btn btn-secondary" onclick="hideGtogView()"><i class="fa fa-arrow-left"></i> Back</button>
              <button type="button" class="btn btn-primary" id="gtogViewEditBtn"><i class="fa fa-edit"></i> Edit</button>
            </div>
          </div>
          <div class="record-details">
            <div class="record-section">
              <h3>Basic Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <div class="detail-label">Last Name</div>
                  <div class="detail-value" id="gtog_view_last_name"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">First Name</div>
                  <div class="detail-value" id="gtog_view_first_name"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Middle Name</div>
                  <div class="detail-value" id="gtog_view_middle_name"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Sex</div>
                  <div class="detail-value" id="gtog_view_sex"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Birth Date</div>
                  <div class="detail-value" id="gtog_view_birth_date"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Age</div>
                  <div class="detail-value" id="gtog_view_age"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Height</div>
                  <div class="detail-value" id="gtog_view_height"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Weight</div>
                  <div class="detail-value" id="gtog_view_weight"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Educational Attainment</div>
                  <div class="detail-value" id="gtog_view_educational_attainment"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Present Address</div>
                  <div class="detail-value" id="gtog_view_present_address"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Email Address</div>
                  <div class="detail-value" id="gtog_view_email_address"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Contact Number</div>
                  <div class="detail-value" id="gtog_view_contact_number"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Passport Number</div>
                  <div class="detail-value" id="gtog_view_passport_number"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Passport Validity</div>
                  <div class="detail-value" id="gtog_view_passport_validity"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">ID Presented</div>
                  <div class="detail-value" id="gtog_view_id_presented"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">ID Number</div>
                  <div class="detail-value" id="gtog_view_id_number"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">With Job Experience</div>
                  <div class="detail-value" id="gtog_view_with_job_experience"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Company Name/Year Started–Ended</div>
                  <div class="detail-value" id="gtog_view_company_name_year_started_ended"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">With Other Experience</div>
                  <div class="detail-value" id="gtog_view_with_job_experience_aside_from"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Name/Company/Year Started–Ended</div>
                  <div class="detail-value" id="gtog_view_name_company_year_started_ended"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Remarks</div>
                  <div class="detail-value" id="gtog_view_remarks"></div>
                </div>
                <div class="detail-item">
                  <div class="detail-label">Date Received by Region</div>
                  <div class="detail-value" id="gtog_view_date_received_by_region"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="gtog-top">
          <div class="controls">
            <form action="" method="GET" class="search-form">
              <div class="search-form">
                <input type="text" name="search" placeholder="Search or use last_name:Smith for exact match" class="search-bar" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="btn search-btn"><i class="fa fa-search"></i></button>
              </div>
            </form>
            <button class="btn filter-btn" id="showFilterBarBtn"><i class="fa fa-filter"></i> Filter</button>
            <button class="btn add-btn" onclick="window.location.href='gov_to_gov_add.php'"><i class="fa fa-plus"></i> Add New Record</button>
            <button type="button" onclick="submitSelectedApplicants()" class="btn go-btn create-memo"> <b>Generate Memo</b> </button>
          </div>
        </div>

        <div class="table-footer" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
          <span class="results-count" id="resultsCount" style="flex:1;text-align:left;">
            Showing <?= min(($offset + 1), $total_records) ?>-<?= min(($offset + $rows_per_page), $total_records) ?> out of <?= $total_records ?> results
          </span>
          <form action="" method="GET" id="rowsPerPageForm" style="display:inline-block;flex:1;text-align:right;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
            <input type="hidden" name="page" value="<?= $page ?>">
            <label style="margin-right:5px;">Rows per page:
              <input type="number" name="rows" class="rows-input" value="<?= $rows_per_page ?>" id="rowsInput" style="width:60px;">
            </label>
            <button type="button" class="btn go-btn reset-btn" id="resetRowsBtn" style="background-color:#007bff;color:#fff;border:none;border-radius:16px;padding:3px 10px;">Reset</button>
          </form>
        </div>

        <div class="gtog-table">
          <table>
            <thead>
              <tr>
                <th style="width: 40px;"></th>
                <th style="width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Last Name</th>
                <th style="width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">First Name</th>
                <th style="width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Middle Name</th>
                <th style="width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Passport No.</th>
                <th style="width: 80px;">Remarks</th>
                <th class="last-column" style="width: 80px;">Action</th>
              </tr>
            </thead>
            <tbody id="g2g-tbody">
              <?php
              try {
                $stmt = $pdo->query("SELECT * FROM gov_to_gov");
                if ($stmt->rowCount() > 0) {
                  while ($row = $stmt->fetch()) {
                    $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    echo "<tr>";
                    echo '<td><input type="checkbox" class="g2g-row-checkbox" name="selected_ids[]" value="' . htmlspecialchars($row['g2g']) . '"></td>';
                    echo "<td style='white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>" . htmlspecialchars($row['last_name']) . "</td>";
                    echo "<td style='white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>" . htmlspecialchars($row['first_name']) . "</td>";
                    echo "<td style='white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>" . htmlspecialchars($row['middle_name']) . "</td>";
                    echo "<td style='white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'>" . htmlspecialchars($row['passport_number']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
                    echo '<td class="action-icons last-column">';
                    echo '<a href="gov_to_gov_view.php?id=' . urlencode($row['g2g']) . '" class="view-btn" title="View Record"><i class="fa fa-eye"></i></a>';
                    echo '<a href="gov_to_gov_edit.php?id=' . urlencode($row['g2g']) . '" class="edit-btn" title="Edit Record"><i class="fa fa-edit"></i></a>';
                    echo '<a href="delete_gov_to_gov.php?id=' . urlencode($row['g2g']) . '" class="delete-btn" title="Delete Record" onclick="return confirm(\'Are you sure you want to delete this record?\')"><i class="fa fa-trash-alt"></i></a>';
                    echo '</td>';
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan=7>No records found.</td></tr>";
                }
              } catch (PDOException $e) {
                echo "<tr><td colspan=7>Error: " . $e->getMessage() . "</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>

        <div class="gtog-bottom">
          <div class="pagination">
            <?php if ($page > 1): ?>
              <a href="?page=<?= ($page - 1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="prev-btn">
                <i class="fa fa-chevron-left"></i> Previous
              </a>
            <?php else: ?>
              <button class="prev-btn" disabled><i class="fa fa-chevron-left"></i> Previous</button>
            <?php endif; ?>
            <?php
            $window = 5;
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
              <a href="?page=<?= $i ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="page<?= $i == $page ? ' active' : '' ?>"> <?= $i ?> </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <a href="?page=<?= ($page + 1) ?>&rows=<?= $rows_per_page ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" class="next-btn">
                Next <i class="fa fa-chevron-right"></i>
              </a>
            <?php else: ?>
              <button class="next-btn" disabled>Next <i class="fa fa-chevron-right"></i></button>
            <?php endif; ?>
          </div>
          <div class="go-to-page">
            <form action="" method="GET">
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

        <script>
          function showGtogForm(mode, data) {
            const wrapper = document.querySelector('.gtog-record-form-wrapper');
            const form = document.getElementById('gtogRecordForm');
            wrapper.style.display = 'block';
            // Fill form if data provided
            if (data) {
              Object.keys(data).forEach(function(key) {
                if (form[key]) form[key].value = data[key] ?? '';
              });
            } else {
              form.reset();
            }
            // Set readonly for view mode
            const editable = (mode !== 'view');
            Array.from(form.elements).forEach(function(el) {
              if (el.tagName === 'INPUT' || el.tagName === 'SELECT' || el.tagName === 'TEXTAREA') {
                if (el.type !== 'hidden' && el.type !== 'file') {
                  el.readOnly = !editable;
                  el.disabled = !editable;
                }
              }
            });
            document.getElementById('gtogFormSaveBtn').style.display = editable ? '' : 'none';
          }

          function hideGtogForm() {
            document.querySelector('.gtog-record-form-wrapper').style.display = 'none';
            document.getElementById('gtogRecordForm').reset();
          }

          function showGtogView(data) {
            // Hide form, show view
            document.querySelector('.gtog-record-form-wrapper').style.display = 'none';
            document.getElementById('gtogRecordViewWrapper').style.display = 'block';
            // Fill fields
            document.getElementById('gtog_view_fullname').textContent = `${data.last_name || ''}, ${data.first_name || ''} ${data.middle_name || ''}`;
            [
              'last_name', 'first_name', 'middle_name', 'sex', 'birth_date', 'age', 'height', 'weight', 'educational_attainment', 'present_address', 'email_address', 'contact_number', 'passport_number', 'passport_validity', 'id_presented', 'id_number', 'with_job_experience', 'company_name_year_started_ended', 'with_job_experience_aside_from', 'name_company_year_started_ended', 'remarks', 'date_received_by_region'
            ].forEach(function(field) {
              let el = document.getElementById('gtog_view_' + field);
              if (el) el.textContent = data[field] || '';
            });
            // Attach Edit event
            document.getElementById('gtogViewEditBtn').onclick = function() {
              hideGtogView();
              showGtogForm('edit', data);
            };
          }

          function hideGtogView() {
            document.getElementById('gtogRecordViewWrapper').style.display = 'none';
          }
          window.addEventListener('DOMContentLoaded', function() {
            // Add New Record button
            const addBtn = document.querySelector('.add-btn');
            if (addBtn) {
              addBtn.addEventListener('click', function(e) {
                e.preventDefault();
                hideGtogView();
                showGtogForm('add');
              });
            }
          });
        </script>

        <script>
          // CLIENT-SIDE PAGINATION & SEARCH FOR STATIC TABLE DATA
          document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('#g2g-tbody');
            const rows = Array.from(table.querySelectorAll('tr'));
            const searchInput = document.querySelector('.search-bar') || document.getElementById('searchInput');
            const resultsCount = document.getElementById('resultsCount') || document.querySelector('.results-count');
            const rowsInput = document.getElementById('rowsInput');
            const resetRowsBtn = document.getElementById('resetRowsBtn');
            let currentPage = 1;
            let rowsPerPage = parseInt(rowsInput ? rowsInput.value : 6) || 6;
            let filteredRows = rows;

            function renderTable(page = 1) {
              // Hide all rows
              rows.forEach(row => row.style.display = 'none');
              // Paginate filteredRows
              const total = filteredRows.length;
              const totalPages = Math.ceil(total / rowsPerPage) || 1;
              if (page > totalPages) page = totalPages;
              currentPage = page;
              const start = (page - 1) * rowsPerPage;
              const end = Math.min(start + rowsPerPage, total);
              for (let i = start; i < end; i++) {
                filteredRows[i].style.display = '';
              }
              // Update results count
              if (resultsCount) {
                resultsCount.textContent = `Showing ${total === 0 ? 0 : start + 1}-${end} out of ${total} results`;
              }
              // Update pagination controls
              renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
              const pagDiv = document.querySelector('.pagination');
              if (!pagDiv) return;
              pagDiv.innerHTML = '';
              // Prev
              const prevBtn = document.createElement('button');
              prevBtn.textContent = '< Previous';
              prevBtn.className = 'prev-btn';
              prevBtn.disabled = (currentPage === 1);
              prevBtn.onclick = () => renderTable(currentPage - 1);
              pagDiv.appendChild(prevBtn);
              // Page numbers
              let windowSize = 5;
              let start = Math.max(1, currentPage - Math.floor(windowSize / 2));
              let end = Math.min(totalPages, start + windowSize - 1);
              if (end - start < windowSize - 1) start = Math.max(1, end - windowSize + 1);
              for (let i = start; i <= end; i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = 'page' + (i === currentPage ? ' active' : '');
                pageBtn.disabled = (i === currentPage);
                pageBtn.onclick = () => renderTable(i);
                pagDiv.appendChild(pageBtn);
              }
              // Next
              const nextBtn = document.createElement('button');
              nextBtn.textContent = 'Next >';
              nextBtn.className = 'next-btn';
              nextBtn.disabled = (currentPage === totalPages);
              nextBtn.onclick = () => renderTable(currentPage + 1);
              pagDiv.appendChild(nextBtn);
            }

            function applySearch() {
              const q = (searchInput && searchInput.value.trim().toLowerCase()) || '';
              if (!q) {
                filteredRows = rows;
              } else {
                filteredRows = rows.filter(row => row.innerText.toLowerCase().includes(q));
              }
              renderTable(1);
            }
            // Event listeners
            if (searchInput) {
              searchInput.addEventListener('input', applySearch);
            }
            if (rowsInput) {
              rowsInput.addEventListener('change', function() {
                rowsPerPage = parseInt(this.value) || 6;
                renderTable(1);
              });
            }
            if (resetRowsBtn) {
              resetRowsBtn.addEventListener('click', function() {
                if (rowsInput) rowsInput.value = 6;
                rowsPerPage = 6;
                renderTable(1);
              });
            }
            // Initial render
            renderTable(1);
          });
        </script>

      </div>
    </main>
  </div>
</div>

<!-- Popup Memo Form -->
<div id="popupMemoForm" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close" onclick="document.getElementById('popupMemoForm').style.display='none'">&times;</span>
      <h2>Generate Memorandum</h2>
    </div>
    <div class="modal-body">
      <form action="generate_memo.php" method="POST" id="memoForm" target="_blank">
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
          <div id="hiddenIdsContainer"></div>
          <input type="hidden" name="source" value="gov_to_gov">
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
    const checkboxes = document.querySelectorAll('input.g2g-row-checkbox:checked');
    console.log("Number of checked checkboxes:", checkboxes.length);

    if (checkboxes.length === 0) {
      alert("Please select at least one applicant before generating a memo.");
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

      const name = row.querySelector('td:nth-child(2)').textContent + ', ' +
        row.querySelector('td:nth-child(3)').textContent;

      selectedIds.push(id);
      selectedNames.push(name);

      // Create a hidden input for each selected ID
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'selected_ids[]';
      hiddenInput.value = id;
      hiddenIdsContainer.appendChild(hiddenInput);

      // Debug - log each hidden input
      console.log("Created hidden input with value:", id);
    });

    // Display selected applicants
    const selectedApplicantsDiv = document.getElementById('selectedApplicants');

    if (selectedNames.length > 0) {
      let html = '<ul>';
      selectedNames.forEach(function(name) {
        html += '<li>' + name + '</li>';
      });
      html += '</ul>';
      selectedApplicantsDiv.innerHTML = html;
    } else {
      selectedApplicantsDiv.innerHTML = '<p class="text-danger">No applicants selected. Please select at least one applicant.</p>';
    }

    // Show the modal
    document.getElementById('popupMemoForm').style.display = 'block';
  }

  // Add a submit event listener to the form
  document.getElementById('memoForm').addEventListener('submit', function(event) {
    // Check if there are any hidden inputs for selected IDs
    const hiddenInputs = document.querySelectorAll('input[name="selected_ids[]"]');
    if (hiddenInputs.length === 0) {
      event.preventDefault();
      alert("Please select at least one applicant before generating a memo.");
    } else {
      console.log("Form submission with " + hiddenInputs.length + " selected IDs");
    }
  });
</script>

<script>
  // Function to submit selected applicants directly to the memo form
  function submitSelectedApplicants() {
    // Get all checked checkboxes
    const checkboxes = document.querySelectorAll('input.g2g-row-checkbox:checked');
    console.log("Number of checked checkboxes:", checkboxes.length);

    if (checkboxes.length === 0) {
      alert("Please select at least one applicant before generating a memo.");
      return;
    }

    // Clear previous selections
    document.getElementById('hiddenIdsContainer').innerHTML = '';
    document.getElementById('selectedApplicants').innerHTML = '';

    const selectedIds = [];
    const selectedNames = [];

    // Process each checked checkbox
    checkboxes.forEach(function(checkbox) {
      const row = checkbox.closest('tr');
      const id = checkbox.value;

      // Debug - log each checkbox value
      console.log("Selected ID:", id);

      // Get all the required fields from the table
      const lastName = row.querySelector('td:nth-child(2)').textContent.trim();
      const firstName = row.querySelector('td:nth-child(3)').textContent.trim();
      const middleName = row.querySelector('td:nth-child(4)').textContent.trim();
      const passportNo = row.querySelector('td:nth-child(5)').textContent.trim();
      const fullName = lastName + ', ' + firstName + ' ' + middleName;

      selectedIds.push(id);
      selectedNames.push(fullName + ' (Passport: ' + passportNo + ')');

      // Create a hidden input for each selected ID
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'selected_ids[]';
      hiddenInput.value = id;
      document.getElementById('hiddenIdsContainer').appendChild(hiddenInput);
    });

    // Display selected applicants in the form
    const selectedApplicantsDiv = document.getElementById('selectedApplicants');

    if (selectedNames.length > 0) {
      let html = '<ul>';
      selectedNames.forEach(function(name) {
        html += '<li>' + name + '</li>';
      });
      html += '</ul>';
      selectedApplicantsDiv.innerHTML = html;

      // Show the memo form
      document.getElementById('popupMemoForm').style.display = 'block';
    } else {
      selectedApplicantsDiv.innerHTML = '';
      alert('No applicants selected. Please select at least one applicant.');
    }
  }

  // Add event listener for the Generate Memo button
  document.addEventListener('DOMContentLoaded', function() {
    const generateMemoBtn = document.getElementById('generateMemoBtn');
    if (generateMemoBtn) {
      generateMemoBtn.addEventListener('click', function() {
        const form = document.getElementById('memoForm');
        const formData = new FormData(form);

        // Show loading indicator
        generateMemoBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
        generateMemoBtn.disabled = true;

        // Create a new XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'generate_memo.php', true);

        // Set up a handler for when the request finishes
        xhr.onload = function() {
          if (xhr.status === 200) {
            // Hide the modal
            document.getElementById('popupMemoForm').style.display = 'none';

            // Reset the button
            generateMemoBtn.innerHTML = 'Generate Memo';
            generateMemoBtn.disabled = false;

            // Create a blob from the response
            const blob = new Blob([xhr.response], {
              type: 'application/pdf'
            });
            const url = URL.createObjectURL(blob);

            // Open the PDF in a new tab
            window.open(url, '_blank');
          } else {
            alert('Error generating memo. Please try again.');

            // Reset the button
            generateMemoBtn.innerHTML = 'Generate Memo';
            generateMemoBtn.disabled = false;
          }
        };

        // Handle network errors
        xhr.onerror = function() {
          alert('Network error occurred while generating memo.');

          // Reset the button
          generateMemoBtn.innerHTML = 'Generate Memo';
          generateMemoBtn.disabled = false;
        };

        // Send the form data
        xhr.send(formData);
      });
    }
  });
</script>