<?php
include 'session.php';
include 'connection.php';
$pageTitle = "Information Sheet Dashboard";

// Check for Excel generation success message
$excel_generated = isset($_GET['excel_generated']) && $_GET['excel_generated'] == 1;
$excel_filename = $_SESSION['excel_filename'] ?? '';
$excel_path = $_SESSION['excel_path'] ?? '';

// Clear the session variables after reading them
if (isset($_SESSION['excel_success'])) {
    unset($_SESSION['excel_success']);
}

// Add Chart.js library in the head section
$additionalHeadContent = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
$additionalHeadContent = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
$additionalHeadContent = '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>';



// Fetch total row count from the info_sheet table
$sql = "SELECT COUNT(*) as total_rows FROM info_sheet";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$totalRows = $stmt->fetch()['total_rows'];

// Fetch total sum of 'number_of_records_retrieved_printed' from the info_sheet table
$sql = "SELECT SUM(number_of_records_retrieved_printed) as total_retrieved_printed FROM info_sheet";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$totalRetrievedPrinted = $stmt->fetch()['total_retrieved_printed'];

// Gender data
$sql = "SELECT LOWER(TRIM(gender)) AS gender, COUNT(*) as count 
        FROM info_sheet 
        WHERE gender IS NOT NULL AND gender != '' 
        GROUP BY gender";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$genders = [];
$counts = [];
while ($row = $stmt->fetch()) {
  $genders[] = ucfirst($row['gender']);
  $counts[] = $row['count'];
}

// If no gender data, provide defaults
if (empty($genders)) {
  $genders = ['No Data'];
  $counts = [0];
}

// PCT data
$lowestStmt = $pdo->query("SELECT TIME_TO_SEC(total_pct) AS pct_seconds, total_pct FROM info_sheet WHERE total_pct IS NOT NULL AND total_pct != '' ORDER BY TIME_TO_SEC(total_pct) ASC LIMIT 1");
$lowest = $lowestStmt->fetch();
if (!$lowest) {
  $lowest = ['pct_seconds' => 0, 'total_pct' => '00:00:00'];
}

$highestStmt = $pdo->query("SELECT TIME_TO_SEC(total_pct) AS pct_seconds, total_pct FROM info_sheet WHERE total_pct IS NOT NULL AND total_pct != '' ORDER BY TIME_TO_SEC(total_pct) DESC LIMIT 1");
$highest = $highestStmt->fetch();
if (!$highest) {
  $highest = ['pct_seconds' => 0, 'total_pct' => '00:00:00'];
}

// Make sure we have valid values
if (!isset($lowest['total_pct'])) $lowest['total_pct'] = '00:00:00';
if (!isset($highest['total_pct'])) $highest['total_pct'] = '00:00:00';
if (!isset($lowest['pct_seconds'])) $lowest['pct_seconds'] = 0;
if (!isset($highest['pct_seconds'])) $highest['pct_seconds'] = 0;

$pctLabels = ['Lowest PCT (' . $lowest['total_pct'] . ')', 'Highest PCT (' . $highest['total_pct'] . ')'];
$pctValues = [(int)$lowest['pct_seconds'], (int)$highest['pct_seconds']];

// If both values are 0, add some default data for visualization
if ($pctValues[0] == 0 && $pctValues[1] == 0) {
  $pctLabels = ['No PCT Data Available', 'Sample'];
  $pctValues = [1, 0];
}

// Purpose bar data
$purposeStmt = $pdo->query("SELECT TRIM(purpose) AS purpose, COUNT(*) AS count FROM info_sheet WHERE purpose IS NOT NULL AND purpose != '' GROUP BY purpose ORDER BY count DESC");
$purposeLabels = [];
$purposeCounts = [];
while ($row = $purposeStmt->fetch()) {
  $purposeLabels[] = $row['purpose'];
  $purposeCounts[] = $row['count'];
}

// Work Category data
$workCategoryStmt = $pdo->query("SELECT worker_category, COUNT(*) AS count FROM info_sheet WHERE worker_category IS NOT NULL GROUP BY worker_category");
$workCategoryLabels = [];
$workCategoryCounts = [];
while ($row = $workCategoryStmt->fetch()) {
  $workCategoryLabels[] = ucfirst($row['worker_category']);
  $workCategoryCounts[] = $row['count'];
}

// Requested Record data
$requestStmt = $pdo->query("SELECT requested_record, COUNT(*) AS count 
                            FROM info_sheet 
                            WHERE requested_record IS NOT NULL 
                            GROUP BY requested_record");
$requestLabels = [];
$requestCounts = [];
while ($row = $requestStmt->fetch()) {
  $requestLabels[] = ucfirst($row['requested_record']);
  $requestCounts[] = $row['count'];
}

include '_head.php'; // Ensure this includes Chart.js CDN

// Add custom styles for the dashboard
?>
<style>
  /* Dashboard styling */
  .info-sheet-wrapper {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
  }
  
  /* Section Header */
  .section-header {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    margin-bottom: 1.5rem;
  }
  
  .section-header h1 {
    margin: 0 0 0.5rem 0;
    color: #343a40;
    font-size: 1.5rem;
    font-weight: 500;
  }
  
  .section-header p {
    margin: 0 0 1rem 0;
    color: #6c757d;
    font-size: 0.9rem;
  }
  
  /* Controls section */
  .controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }
  
  /* Stats section */
  .stats-section {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  
  @media (max-width: 1199px) {
    .stats-section {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (max-width: 767px) {
    .stats-section {
      grid-template-columns: 1fr;
    }
  }
  
  @media (max-width: 1199px) {
    .stat-card {
      flex: 0 0 calc(50% - 0.5rem);
    }
  }
  
  @media (max-width: 767px) {
    .stat-card {
      flex: 0 0 100%;
    }
  }
  
  /* Charts section */
  .charts-section {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
  }
  
  /* Chart section */
  .charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  
  /* Card styling - consistent with system */
  .card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1rem;
    overflow: hidden;
  }
  
  .card-header {
    padding: 1rem 1.25rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .card-title {
    margin: 0;
    font-size: 1rem;
    color: #343a40;
    font-weight: 500;
  }
  
  .card-subtitle {
    margin-top: 0.25rem;
    color: #6c757d;
    font-size: 0.875rem;
  }
  
  .card-body {
    padding: 1.25rem;
  }
  
  /* Stat Cards */
  .stat-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-left: 4px solid #007bff;
  }
  
  .stat-card:nth-child(1) { border-left-color: #007bff; }
  .stat-card:nth-child(2) { border-left-color: #28a745; }
  .stat-card:nth-child(3) { border-left-color: #17a2b8; }
  .stat-card:nth-child(4) { border-left-color: #ffc107; }
  
  .stat-card:hover {
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  }
  
  .stat-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(120deg, rgba(255,255,255,0) 30%, rgba(255,255,255,0.7) 50%, rgba(255,255,255,0) 70%);
    transform: translateX(-100%);
  }
  
  /* Stat Icons */
  .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 4px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  
  .bg-primary { background-color: #007bff; }
  .bg-success { background-color: #28a745; }
  .bg-info { background-color: #17a2b8; }
  .bg-warning { background-color: #ffc107; }
  
  /* Stat Info */
  .stat-info h3 {
    font-size: 0.875rem;
    margin: 0 0 0.25rem 0;
    color: #6c757d;
  }
  
  .stat-info .value {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #343a40;
  }
  
  .chart-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 20px;
    margin-bottom: 30px;
  }
  .chart-row {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    margin-bottom: 35px;
  }
  
  .chart-card {
    flex: 1;
    min-width: 300px;
    max-width: calc(50% - 20px); /* Prevent charts from getting too wide */
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    padding: 25px;
    position: relative;
    height: auto;
    overflow: hidden;
  }
  
  .chart-card canvas {
    max-height: 400px; /* Limit the height of the charts */
    width: 100% !important;
    height: auto !important;
  }
  
  /* Chart Headers */
  .chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    position: relative;
  }
  /* Chart Icons */
  .chart-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 18px;
    color: white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    background-color: #007bff;
  }
  
  .chart-icon::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.2);
    box-sizing: border-box;
  }
  
  .chart-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
    color: #333;
  }
  
  .chart-subtitle {
    font-size: 13px;
    color: #6c757d;
    margin: 5px 0 0 0;
  }
  
  /* Dashboard Header */
  .dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 35px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    position: relative;
  }
  
  .dashboard-actions {
    margin-left: auto; /* Push to the right */
  }
  
  .dashboard-header::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100px;
    height: 3px;
    background: linear-gradient(90deg, #4e73df, #36b9cc);
    border-radius: 3px;
  }
  
  .dashboard-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: #333;
    position: relative;
    display: inline-block;
  }
  
  .dashboard-title::before {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: #4e73df;
  }
  
  /* Dashboard Actions */
  .dashboard-actions {
    display: flex;
    gap: 12px;
  }
  
  .btn-dashboard {
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    z-index: 1;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
  }
  
  .btn-dashboard i {
    font-size: 15px;
  }
  
  .btn-dashboard::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 100%;
    transform: scale(1, 1) translate(-50%, -50%);
    transform-origin: 50% 50%;
    z-index: -1;
  }
  
  .btn-primary-soft {
    background-color: rgba(78, 115, 223, 0.1);
    color: #4e73df;
    border: none;
  }
  
  .btn-primary-soft:hover {
    background-color: #4e73df;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
  }
  
  .btn-primary {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 10px rgba(78, 115, 223, 0.3);
  }
  
  /* Simplified button hover state */
  .btn-primary:hover {
    background-color: #0069d9;
  }
  
  .btn-success-soft {
    background-color: rgba(28, 200, 138, 0.1);
    color: #1cc88a;
    border: none;
  }
  
  .btn-success-soft:hover {
    background-color: #1cc88a;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(28, 200, 138, 0.3);
  }
</style>

<?php include '_head.php'; ?>

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
      <div class="info-sheet-wrapper">
        <!-- Hidden input to trigger modal when Excel is generated -->
        <?php if ($excel_generated): ?>
        <input type="hidden" id="excel_generated" value="1">
        <input type="hidden" id="excel_filename" value="<?= htmlspecialchars($excel_filename) ?>">
        <input type="hidden" id="excel_path" value="<?= htmlspecialchars($excel_path) ?>">
        <?php endif; ?>
        
        <!-- Section Header -->
        <div class="section-header">
          <h1>Information Sheet Dashboard</h1>
          <p>View and analyze information sheet data with visual reports</p>
          
          <div class="controls">
            <div>
              <!-- Could add filters here if needed -->
            </div>
            <div class="actions">
              <a href="insert_info_sheet.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Record
              </a>
              <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateModal">
                <i class="fas fa-file-excel"></i> Generate Excel Report
              </button>
            </div>
          </div>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-section">
          <div class="stat-card">
            <div class="stat-icon bg-primary">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-info">
              <h3>Total Requests</h3>
              <div class="value"><?php echo number_format($totalRows); ?></div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon bg-success">
              <i class="fas fa-print"></i>
            </div>
            <div class="stat-info">
              <h3>Records Printed</h3>
              <div class="value"><?php echo $totalRetrievedPrinted ? number_format($totalRetrievedPrinted) : '0'; ?></div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon bg-info">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
              <h3>Gender Distribution</h3>
              <div class="value">
                <?php 
                if (count($genders) > 0 && $genders[0] != 'No Data') {
                  $maleCount = 0;
                  $femaleCount = 0;
                  
                  // Find male and female counts
                  foreach ($genders as $index => $gender) {
                    if (strtolower($gender) == 'male') {
                      $maleCount = $counts[$index];
                    } else if (strtolower($gender) == 'female') {
                      $femaleCount = $counts[$index];
                    }
                  }
                  
                  echo "M: {$maleCount}<br>F: {$femaleCount}";
                } else {
                  echo 'No Data Available';
                }
                ?>
              </div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon bg-warning">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
              <h3>Processing Time</h3>
              <div class="value">
                <?php 
                if ($lowest['total_pct'] == '00:00:00' && $highest['total_pct'] == '00:00:00') {
                  echo 'No Data Available';
                } else {
                  echo $lowest['total_pct'] . ' - ' . $highest['total_pct']; 
                }
                ?>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-section">
          <div class="card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Gender Distribution</h2>
                <p class="card-subtitle">Breakdown of records by gender</p>
              </div>
              <div class="stat-icon bg-primary">
                <i class="fas fa-venus-mars"></i>
              </div>
            </div>
            <div class="card-body">
              <canvas id="genderChart" height="250"></canvas>
            </div>
          </div>
          
          <div class="card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Processing Time</h2>
                <p class="card-subtitle">Lowest vs Highest PCT</p>
              </div>
              <div class="stat-icon bg-warning">
                <i class="fas fa-clock"></i>
              </div>
            </div>
            <div class="card-body">
              <canvas id="pctChart" height="250"></canvas>
            </div>
          </div>
        
          <div class="card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Purpose Analysis</h2>
                <p class="card-subtitle">Distribution by purpose</p>
              </div>
              <div class="stat-icon bg-primary">
                <i class="fas fa-tasks"></i>
              </div>
            </div>
            <div class="card-body">
              <canvas id="purposeChart" height="250"></canvas>
            </div>
          </div>
        
          <div class="card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Worker Categories</h2>
                <p class="card-subtitle">Breakdown by type</p>
              </div>
              <div class="stat-icon bg-success">
                <i class="fas fa-users-cog"></i>
              </div>
            </div>
            <div class="card-body">
              <canvas id="workCategoryChart" height="250"></canvas>
            </div>
          </div>
          
          <div class="card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Requested Records</h2>
                <p class="card-subtitle">Types of requests</p>
              </div>
              <div class="stat-icon bg-info">
                <i class="fas fa-file-alt"></i>
              </div>
            </div>
            <div class="card-body">
              <canvas id="requestChart" height="250"></canvas>
            </div>
          </div>
        </div>
        <!-- Modal -->
<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="generateForm" method="GET" action="generate_spreadsheet.php" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="generateModalLabel">Generate Excel Report</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p class="mb-3">Please select the month and year for your report:</p>
        <div class="mb-3">
          <label for="month" class="form-label">Month</label>
          <select class="form-select" name="month" id="month" required>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>"><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="year" class="form-label">Year</label>
          <input type="number" class="form-control" name="year" id="year" min="2000" max="2100" value="<?= date('Y') ?>" required>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Generate Report</button>
      </div>
    </form>
  </div>
</div>

<!-- Excel Generation Success Modal -->
<div class="modal fade" id="excelSuccessModal" tabindex="-1" aria-labelledby="excelSuccessModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="excelSuccessModalLabel">Success</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Information sheet Excel file has been generated successfully.</p>
        <p class="small text-muted" id="excelPathText"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a id="downloadExcelBtn" href="#" class="btn btn-primary">Download File</a>
      </div>
    </div>
  </div>
</div>

      </div>
    </main>
  </div>
</div>

<!-- Tour Guide Modal -->
<div class="modal fade" id="tourGuideModal" tabindex="-1" role="dialog" aria-labelledby="tourGuideModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="tourGuideModalLabel">Information Sheet Guide</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Tour Navigation -->
        <div class="tour-navigation mb-4">
          <div class="progress">
            <div class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          <ul class="nav nav-pills nav-justified tour-nav mt-2">
            <li class="nav-item">
              <a class="nav-link active" data-step="1">Sidebar Navigation</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-step="2">Dashboard Overview</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-step="3">Statistics Cards</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-step="4">Charts & Reports</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-step="5">Excel Generation</a>
            </li>
          </ul>
        </div>
        
        <!-- Tour Content -->
        <div class="tour-content">
          <!-- Step 1: Sidebar Navigation (visible by default) -->
          <div class="tour-step" data-step="1">
            <div class="text-center mb-4">
              <h3>Sidebar Navigation</h3>
              <p class="lead">The sidebar provides quick access to all system modules.</p>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="card mb-3">
                  <div class="card-body">
                    <h5>Main Navigation Menu</h5>
                    <p>The sidebar contains these important sections:</p>
                    <ul class="list-group list-group-flush">
                      <li class="list-group-item d-flex align-items-center">
                        <span class="badge bg-primary me-2">1</span> Dashboard - Overview of the entire system
                      </li>
                      <li class="list-group-item d-flex align-items-center">
                        <span class="badge bg-primary me-2">2</span> Direct Hire - Manage direct hire applications
                      </li>
                      <li class="list-group-item d-flex align-items-center">
                        <span class="badge bg-primary me-2">3</span> Gov-to-Gov - Government deployment program management
                      </li>
                      <li class="list-group-item d-flex align-items-center">
                        <span class="badge bg-primary me-2">4</span> Balik Manggagawa - Process returning worker documentation
                      </li>
                      <li class="list-group-item d-flex align-items-center">
                        <span class="badge bg-primary me-2">5</span> Information Sheet - Current module you're viewing now
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Step 2: Dashboard Overview -->
          <div class="tour-step" data-step="2" style="display: none;">
            <div class="text-center mb-4">
              <h3>Welcome to Information Sheet Dashboard</h3>
              <p class="lead">This dashboard helps you analyze and export information sheet data.</p>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="card mb-3">
                  <div class="card-body">
                    <h5>Dashboard Features</h5>
                    <p>The information sheet dashboard provides visual reports and statistics about all records in the system.</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card mb-3">
                  <div class="card-body">
                    <h5>Key Functions</h5>
                    <p>Easily add new records, generate reports, and analyze data with visual charts.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Step 2: Statistics Cards (initially hidden) -->
          <div class="tour-step" data-step="2" style="display: none;">
            <div class="text-center mb-4">
              <h3>Statistics Cards</h3>
              <p class="lead">Quick overview of key metrics from the information sheets.</p>
            </div>
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">Statistics Overview</h5>
                <p>The top section displays important statistics:</p>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">Total number of information sheet records</li>
                  <li class="list-group-item">Number of records that have been printed</li>
                  <li class="list-group-item">Gender distribution of applicants</li>
                  <li class="list-group-item">Processing time statistics</li>
                </ul>
              </div>
            </div>
          </div>
          
          <!-- Step 3: Charts & Reports (initially hidden) -->
          <div class="tour-step" data-step="3" style="display: none;">
            <div class="text-center mb-4">
              <h3>Charts & Reports</h3>
              <p class="lead">Visual analysis of information sheet data.</p>
            </div>
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">Available Charts</h5>
                <p>The dashboard includes several interactive charts:</p>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">Gender distribution pie chart</li>
                  <li class="list-group-item">Processing time comparison</li>
                  <li class="list-group-item">Purpose analysis bar chart</li>
                  <li class="list-group-item">Worker categories breakdown</li>
                  <li class="list-group-item">Requested records analysis</li>
                </ul>
              </div>
            </div>
          </div>
          
          <!-- Step 4: Charts & Reports (initially hidden) -->
          <div class="tour-step" data-step="4" style="display: none;">
            <div class="text-center mb-4">
              <h3>Charts & Reports</h3>
              <p class="lead">Visual analysis of information sheet data.</p>
            </div>
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">Available Charts</h5>
                <p>The dashboard includes several interactive charts:</p>
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">Gender distribution pie chart</li>
                  <li class="list-group-item">Processing time comparison</li>
                  <li class="list-group-item">Purpose analysis bar chart</li>
                  <li class="list-group-item">Worker categories breakdown</li>
                  <li class="list-group-item">Requested records analysis</li>
                </ul>
              </div>
            </div>
          </div>
          
          <!-- Step 5: Excel Generation (initially hidden) -->
          <div class="tour-step" data-step="5" style="display: none;">
            <div class="text-center mb-4">
              <h3>Excel Report Generation</h3>
              <p class="lead">Create and download Excel reports from information sheet data.</p>
            </div>
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">How to Generate Reports</h5>
                <ol class="list-group list-group-flush">
                  <li class="list-group-item">Click the "Generate Excel Report" button</li>
                  <li class="list-group-item">Select the month and year for your report</li>
                  <li class="list-group-item">Click "Generate Report" to create the Excel file</li>
                  <li class="list-group-item">Download the file when prompted</li>
                </ol>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Tour Footer -->
        <div class="tour-footer d-flex justify-content-between mt-4">
          <button type="button" class="btn btn-secondary" id="tourPrevBtn" disabled>Previous</button>
          <button type="button" class="btn btn-primary" id="tourNextBtn">Next</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// All database queries have been moved to the top of the file
?>

<script>
  // Chart.js initialization - we don't need these lines anymore since we're using PHP to display the values directly
  // document.getElementById("totalRows").innerHTML = "<?php echo $totalRows; ?>";
  // document.getElementById("totalPrinted").innerHTML = "<?php echo $totalRetrievedPrinted; ?>";

  // Common chart options
  const commonOptions = {
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          usePointStyle: true,
          padding: 20,
          font: {
            size: 12,
            family: "'Poppins', sans-serif"
          },
          generateLabels: function(chart) {
            // Get the default legendItems
            const original = Chart.overrides.pie.plugins.legend.labels.generateLabels(chart);
            // Add hover effect to legend items
            original.forEach(item => {
              item.fontColor = '#333';
            });
            return original;
          }
        },
        onHover: function(e, legendItem, legend) {
          // Add hover effect to legend
          document.getElementById(legend.chart.canvas.id).style.cursor = 'pointer';
        },
        onLeave: function(e, legendItem, legend) {
          document.getElementById(legend.chart.canvas.id).style.cursor = 'default';
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: '#fff',
        bodyColor: '#fff',
        padding: 12,
        cornerRadius: 8,
        titleFont: {
          size: 14,
          weight: 'bold',
          family: "'Poppins', sans-serif"
        },
        bodyFont: {
          size: 13,
          family: "'Poppins', sans-serif"
        },
        displayColors: true,
        boxPadding: 5,
        usePointStyle: true,
        callbacks: {
          // Custom label formatting
          label: function(context) {
            let label = context.dataset.label || '';
            if (label) {
              label += ': ';
            }
            if (context.parsed !== null) {
              label += context.parsed;
            }
            return ' ' + label;
          }
        }
      }
    },
    animation: {
      duration: 1200,
      easing: 'easeOutCirc',
    },
    layout: {
      padding: {
        top: 15,
        right: 15,
        bottom: 25,
        left: 15
      }
    },
    interaction: {
      mode: 'nearest',
      intersect: false,
      axis: 'xy'
    },
    transitions: {
      active: {
        animation: {
          duration: 400
        }
      }
    },
    hover: {
      animationDuration: 400
    }
  };

  // Gender Chart - Doughnut
  const genderLabels = <?php echo json_encode($genders); ?>;
  const genderData = <?php echo json_encode($counts); ?>;
  // Create a variable to store the chart instance
  let genderChart = new Chart(document.getElementById('genderChart').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: genderLabels,
      datasets: [{
        label: 'Gender Distribution',
        data: genderData,
        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 10
      }]
    },
    options: {
      ...commonOptions,
      cutout: '60%',
      plugins: {
        ...commonOptions.plugins,
        title: {
          display: false,
          text: 'Gender Distribution',
          font: {
            size: 16,
            weight: 'bold'
          }
        }
      }
    }
  });

  // PCT Chart - Doughnut
  const pctLabels = <?php echo json_encode($pctLabels); ?>;
  const pctData = <?php echo json_encode($pctValues); ?>;
  new Chart(document.getElementById('pctChart').getContext('2d'), {
    type: 'doughnut',
    data: {
      labels: pctLabels,
      datasets: [{
        label: 'Processing Time',
        data: pctData,
        backgroundColor: ['#1cc88a', '#e74a3b'],
        borderWidth: 2,
        borderColor: '#ffffff',
        hoverOffset: 10
      }]
    },
    options: {
      ...commonOptions,
      cutout: '60%',
      plugins: {
        ...commonOptions.plugins,
        title: {
          display: false,
          text: 'Processing Time Comparison',
          font: {
            size: 16,
            weight: 'bold'
          }
        }
      }
    }
  });

  // Purpose Chart - Horizontal Bar Chart with gradient
  const purposeLabels = <?php echo json_encode($purposeLabels); ?>;
  const purposeData = <?php echo json_encode($purposeCounts); ?>;
  const purposeCtx = document.getElementById('purposeChart').getContext('2d');
  
  // Create gradient for purpose chart
  const purposeGradient = purposeCtx.createLinearGradient(0, 0, 0, 400);
  purposeGradient.addColorStop(0, '#9b59b6');
  purposeGradient.addColorStop(1, '#8e44ad');
  
  new Chart(purposeCtx, {
    type: 'bar',
    data: {
      labels: purposeLabels,
      datasets: [{
        label: 'Purpose Count',
        data: purposeData,
        backgroundColor: purposeGradient,
        borderWidth: 0,
        borderRadius: 4,
        barPercentage: 0.6,
        maxBarThickness: 25
      }]
    },
    options: {
      ...commonOptions,
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            drawBorder: false,
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            font: {
              size: 11
            }
          }
        },
        y: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              size: 11
            }
          }
        }
      },
      plugins: {
        ...commonOptions.plugins,
        legend: {
          display: false
        },
        title: {
          display: false,
          text: 'Purpose Distribution',
          font: {
            size: 16,
            weight: 'bold'
          }
        }
      }
    }
  });

  // Work Category Chart - Bar Chart with gradient
  const workCategoryLabels = <?php echo json_encode($workCategoryLabels); ?>;
  const workCategoryData = <?php echo json_encode($workCategoryCounts); ?>;
  const workCtx = document.getElementById('workCategoryChart').getContext('2d');
  
  // Create gradient for work category chart
  const workGradient = workCtx.createLinearGradient(0, 0, 0, 250);
  workGradient.addColorStop(0, '#f39c12');
  workGradient.addColorStop(1, '#e67e22');
  
  new Chart(workCtx, {
    type: 'bar',
    data: {
      labels: workCategoryLabels,
      datasets: [{
        label: 'Worker Category',
        data: workCategoryData,
        backgroundColor: workGradient,
        borderWidth: 0,
        borderRadius: 6,
        barPercentage: 0.7,
        maxBarThickness: 50
      }]
    },
    options: {
      ...commonOptions,
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              size: 11
            }
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            font: {
              size: 11
            }
          }
        }
      },
      plugins: {
        ...commonOptions.plugins,
        legend: {
          display: false
        }
      }
    }
  });

  // Requested Record Chart - Bar Chart with gradient
  const requestLabels = <?php echo json_encode($requestLabels); ?>;
  const requestData = <?php echo json_encode($requestCounts); ?>;
  const requestCtx = document.getElementById('requestChart').getContext('2d');
  
  // Create gradient for request chart
  const requestGradient = requestCtx.createLinearGradient(0, 0, 0, 250);
  requestGradient.addColorStop(0, '#36b9cc');
  requestGradient.addColorStop(1, '#258391');
  
  new Chart(requestCtx, {
    type: 'bar',
    data: {
      labels: requestLabels,
      datasets: [{
        label: 'Requested Records',
        data: requestData,
        backgroundColor: requestGradient,
        borderWidth: 0,
        borderRadius: 6,
        barPercentage: 0.7,
        maxBarThickness: 50
      }]
    },
    options: {
      ...commonOptions,
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              size: 11
            }
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            font: {
              size: 11
            }
          }
        }
      },
      plugins: {
        ...commonOptions.plugins,
        legend: {
          display: false
        }
      }
    }
  });
  // Add New Record button functionality
  document.getElementById('addNewBtn').addEventListener('click', function(e) {
    // The link already has the href, this just adds a nice transition effect
    this.classList.add('btn-clicked');
    setTimeout(() => {
      this.classList.remove('btn-clicked');
    }, 300);
  });
  
  // Tour Guide functionality
  document.addEventListener('DOMContentLoaded', function() {
    // Show tour guide button
    const tourBtn = document.createElement('button');
    tourBtn.className = 'btn btn-info position-fixed';
    tourBtn.style.bottom = '20px';
    tourBtn.style.right = '20px';
    tourBtn.innerHTML = 'Start Tour';
    tourBtn.addEventListener('click', function() {
      const tourModal = new bootstrap.Modal(document.getElementById('tourGuideModal'));
      tourModal.show();
    });
    document.body.appendChild(tourBtn);
    
    // Tour guide variables
    let currentTourStep = 1;
    const totalTourSteps = 5;
    
    // Update tour guide UI
    function updateTourUI() {
      // Update progress bar
      const progressPercent = ((currentTourStep - 1) / (totalTourSteps - 1)) * 100;
      document.querySelector('.progress-bar').style.width = `${progressPercent}%`;
      document.querySelector('.progress-bar').setAttribute('aria-valuenow', progressPercent);
      
      // Update navigation pills
      document.querySelectorAll('.tour-nav .nav-link').forEach(link => {
        link.classList.remove('active');
        if (parseInt(link.getAttribute('data-step')) === currentTourStep) {
          link.classList.add('active');
        }
      });
      
      // Show current step, hide others
      document.querySelectorAll('.tour-step').forEach(step => {
        step.style.display = 'none';
        if (parseInt(step.getAttribute('data-step')) === currentTourStep) {
          step.style.display = 'block';
        }
      });
      
      // Update button states
      const prevBtn = document.getElementById('tourPrevBtn');
      const nextBtn = document.getElementById('tourNextBtn');
      prevBtn.disabled = currentTourStep === 1;
      
      if (currentTourStep === totalTourSteps) {
        nextBtn.textContent = 'Finish';
      } else {
        nextBtn.textContent = 'Next';
      }
    }
    
    // Initialize tour navigation
    const nextBtn = document.getElementById('tourNextBtn');
    if (nextBtn) {
      nextBtn.addEventListener('click', function() {
        if (currentTourStep < totalTourSteps) {
          currentTourStep++;
          updateTourUI();
        } else {
          // Close the modal on finish
          const tourModal = bootstrap.Modal.getInstance(document.getElementById('tourGuideModal'));
          tourModal.hide();
          currentTourStep = 1;
          updateTourUI();
        }
      });
    }
    
    const prevBtn = document.getElementById('tourPrevBtn');
    if (prevBtn) {
      prevBtn.addEventListener('click', function() {
        if (currentTourStep > 1) {
          currentTourStep--;
          updateTourUI();
        }
      });
    }
    
    // Make nav pills clickable
    document.querySelectorAll('.tour-nav .nav-link').forEach(link => {
      link.addEventListener('click', function() {
        currentTourStep = parseInt(this.getAttribute('data-step'));
        updateTourUI();
      });
    });
    
    // Initialize tour UI
    updateTourUI();
  });
</script>