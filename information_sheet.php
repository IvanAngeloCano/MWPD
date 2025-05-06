<?php
include 'session.php';
include 'connection.php';
$pageTitle = "Information Sheet Dashboard";

// Add Chart.js library in the head section
$additionalHeadContent = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';


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
  /* Import Google Fonts for better typography */
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
  
  /* Dashboard Container */
  .dashboard-container {
    padding: 25px;
    font-family: 'Poppins', sans-serif;
    animation: fadeIn 0.8s ease-in-out;
  }
  
  /* Stats Row */
  .stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 35px;
  }
  
  /* Stat Cards */
  .stat-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 22px;
    flex: 1;
    min-width: 200px;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    animation: slideInUp 0.5s ease-out forwards;
    animation-delay: calc(var(--animation-order, 0) * 0.1s);
    opacity: 0;
  }
  
  .stat-card:nth-child(1) { --animation-order: 1; }
  .stat-card:nth-child(2) { --animation-order: 2; }
  .stat-card:nth-child(3) { --animation-order: 3; }
  .stat-card:nth-child(4) { --animation-order: 4; }
  
  .stat-card:hover {
    transform: translateY(-7px) scale(1.02);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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
    transition: all 0.6s ease;
  }
  
  .stat-card:hover::after {
    transform: translateX(100%);
  }
  
  /* Stat Icons */
  .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 18px;
    font-size: 24px;
    color: white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  
  /* Ensure perfect circle */
  .stat-icon::before {
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
  
  .stat-card:hover .stat-icon {
    transform: rotate(10deg) scale(1.1);
  }
  
  /* Stat Info */
  .stat-info h3 {
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin: 0 0 8px 0;
    color: #6c757d;
    font-weight: 600;
  }
  
  .stat-info .value {
    font-size: 30px;
    font-weight: 700;
    margin: 0;
    color: #333;
    transition: all 0.3s ease;
  }
  
  .stat-card:hover .stat-info .value {
    color: #4e73df;
    transform: scale(1.05);
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
    animation: fadeIn 0.8s ease-in-out;
  }
  
  .chart-card {
    flex: 1;
    min-width: 300px;
    max-width: calc(50% - 20px); /* Prevent charts from getting too wide */
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.07);
    padding: 25px;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    position: relative;
    height: auto;
    overflow: hidden;
    animation: slideInUp 0.6s ease-out forwards;
    animation-delay: calc(var(--animation-order, 0) * 0.15s);
    opacity: 0;
  }
  
  .chart-row:nth-child(1) .chart-card:nth-child(1) { --animation-order: 5; }
  .chart-row:nth-child(1) .chart-card:nth-child(2) { --animation-order: 6; }
  .chart-row:nth-child(2) .chart-card:nth-child(1) { --animation-order: 7; }
  .chart-row:nth-child(3) .chart-card:nth-child(1) { --animation-order: 8; }
  .chart-row:nth-child(3) .chart-card:nth-child(2) { --animation-order: 9; }
  
  .chart-card canvas {
    max-height: 400px; /* Limit the height of the charts */
    width: 100% !important;
    height: auto !important;
    transition: all 0.5s ease;
    animation: fadeIn 1s ease-in-out;
  }
  
  .chart-card:hover {
    transform: translateY(-7px) scale(1.01);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
  }
  
  .chart-card:hover canvas {
    transform: scale(1.02);
  }
  
  .chart-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #4e73df, #36b9cc, #1cc88a);
    opacity: 0;
    transition: opacity 0.4s ease;
  }
  
  .chart-card:hover::before {
    opacity: 1;
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
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
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
    transition: all 0.3s ease;
  }
  
  .chart-card:hover .chart-title {
    color: #4e73df;
    transform: translateX(5px);
  }
  
  .chart-subtitle {
    font-size: 13px;
    color: #6c757d;
    margin: 5px 0 0 0;
    transition: all 0.3s ease;
  }
  
  .chart-card:hover .chart-subtitle {
    color: #4e73df;
  }
  
  /* Animations */
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  
  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  canvas {
    width: 100% !important;
    height: auto !important;
  }
  .bg-primary-gradient {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
  }
  .bg-success-gradient {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
  }
  .bg-info-gradient {
    background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
  }
  .bg-warning-gradient {
    background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
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
    animation: slideInDown 0.6s ease-out forwards;
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
    transition: width 0.4s ease;
  }
  
  .dashboard-title:hover::before {
    width: 100%;
  }
  
  /* Dashboard Actions */
  .dashboard-actions {
    display: flex;
    gap: 12px;
    animation: fadeIn 0.8s ease-in-out;
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
    transition: transform 0.3s ease;
  }
  
  .btn-dashboard:hover i {
    transform: translateX(3px);
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
  
  .btn-dashboard:hover::after {
    animation: ripple 1s ease-out;
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
  
  .btn-primary:hover {
    background: linear-gradient(135deg, #3a5fc8 0%, #1a3a9c 100%);
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(78, 115, 223, 0.4);
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
  
  @keyframes ripple {
    0% {
      transform: scale(0, 0);
      opacity: 1;
    }
    20% {
      transform: scale(25, 25);
      opacity: 1;
    }
    100% {
      opacity: 0;
      transform: scale(40, 40);
    }
  }
  
  @keyframes slideInDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  /* Button click animation */
  .btn-clicked {
    transform: scale(0.95);
    opacity: 0.8;
  }
  
  /* Button click animation */
  .btn-clicked {
    transform: scale(0.95);
    opacity: 0.8;
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
      <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
          <h1 class="dashboard-title">Information Sheet Dashboard</h1>
          <div class="dashboard-actions">
            <a href="insert_info_sheet.php" class="btn-dashboard btn-primary" id="addNewBtn">
              <i class="fas fa-plus-circle"></i> Add New Record
            </a>
          </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-icon bg-primary-gradient">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-info">
              <h3>Total Requests</h3>
              <div class="value"><?php echo number_format($totalRows); ?></div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon bg-success-gradient">
              <i class="fas fa-print"></i>
            </div>
            <div class="stat-info">
              <h3>Records Printed</h3>
              <div class="value"><?php echo $totalRetrievedPrinted ? number_format($totalRetrievedPrinted) : '0'; ?></div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon bg-info-gradient">
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
            <div class="stat-icon bg-warning-gradient">
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
        
        <!-- Charts Row 1 -->
        <div class="chart-row">
          <div class="chart-card">
            <div class="chart-header">
              <div class="d-flex align-items-center">
                <div class="chart-icon bg-primary-gradient">
                  <i class="fas fa-venus-mars"></i>
                </div>
                <div>
                  <h2 class="chart-title">Gender Distribution</h2>
                  <p class="chart-subtitle">Breakdown of records by gender</p>
                </div>
              </div>
            </div>
            <canvas id="genderChart" height="250"></canvas>
          </div>
          
          <div class="chart-card">
            <div class="chart-header">
              <div class="d-flex align-items-center">
                <div class="chart-icon bg-warning-gradient">
                  <i class="fas fa-clock"></i>
                </div>
                <div>
                  <h2 class="chart-title">Processing Time Comparison</h2>
                  <p class="chart-subtitle">Lowest vs Highest PCT</p>
                </div>
              </div>
            </div>
            <canvas id="pctChart" height="250"></canvas>
          </div>
        </div>
        
        <!-- Charts Row 2 -->
        <div class="chart-row">
          <div class="chart-card">
            <div class="chart-header">
              <div class="d-flex align-items-center">
                <div class="chart-icon" style="background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);">
                  <i class="fas fa-tasks"></i>
                </div>
                <div>
                  <h2 class="chart-title">Purpose Analysis</h2>
                  <p class="chart-subtitle">Distribution by purpose category</p>
                </div>
              </div>
            </div>
            <canvas id="purposeChart" height="300"></canvas>
          </div>
        </div>
        
        <!-- Charts Row 3 -->
        <div class="chart-row">
          <div class="chart-card">
            <div class="chart-header">
              <div class="d-flex align-items-center">
                <div class="chart-icon" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
                  <i class="fas fa-users-cog"></i>
                </div>
                <div>
                  <h2 class="chart-title">Worker Categories</h2>
                  <p class="chart-subtitle">Breakdown by worker type</p>
                </div>
              </div>
            </div>
            <canvas id="workCategoryChart" height="250"></canvas>
          </div>
          
          <div class="chart-card">
            <div class="chart-header">
              <div class="d-flex align-items-center">
                <div class="chart-icon" style="background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);">
                  <i class="fas fa-file-alt"></i>
                </div>
                <div>
                  <h2 class="chart-title">Requested Records</h2>
                  <p class="chart-subtitle">Types of records requested</p>
                </div>
              </div>
            </div>
            <canvas id="requestChart" height="250"></canvas>
          </div>
        </div>
      </div>
    </main>
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
      delay: function(context) {
        // First dataset is delayed a bit
        let delay = 0;
        if (context.type === 'data') {
          delay = context.dataIndex * 100 + context.datasetIndex * 100;
        }
        return delay;
      }
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
</script>