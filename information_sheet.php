<?php
include 'session.php';
$pageTitle = "Information Sheet - MWPD Filing System";
include '_head.php'; // Ensure this includes Chart.js CDN
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
      <div class="info-sheet-wrapper">
        <div class="stat-card">
          <div class="stat-item">
            <h4>TOTAL REQUESTS</h4>
            <div id="totalRows" class="stat-value"></div>
          </div>
          <div class="stat-item">
            <h4>NO. RECORD PRINTED/RETRIEVE</h4>
            <div id="totalPrinted" class="stat-value"></div>
          </div>
        </div>

        <div>

          <!-- Gender Chart -->
          <div class="charts">
            <canvas id="genderChart" width="380" height="200"></canvas>
          </div>

          <!-- PCT Chart -->
          <div class="charts">
            <canvas id="pctChart" width="380" height="200"></canvas>
          </div>
        </div>



        <div class="chart-card">
          <div class="chart-item">
            <h4>PURPOSE</h4>
            <canvas id="purposeChart" width="330" height="300"></canvas>
          </div>
          <div class="chart-item">
            <h4>WORK CATEGORY</h4>
            <canvas id="workCategoryChart" width="330" height="300"></canvas>
          </div>
          <div class="chart-item">
            <h4>REQUEST RECORD</h4>
            <canvas id="requestChart" width="330" height="300"></canvas>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<?php
include 'connection.php';

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
        WHERE gender IS NOT NULL 
        GROUP BY gender";
$stmt = $pdo->prepare($sql);
$stmt->execute();

$genders = [];
$counts = [];
while ($row = $stmt->fetch()) {
  $genders[] = ucfirst($row['gender']);
  $counts[] = $row['count'];
}

// PCT data
$lowestStmt = $pdo->query("SELECT TIME_TO_SEC(total_pct) AS pct_seconds, total_pct FROM info_sheet WHERE total_pct IS NOT NULL ORDER BY TIME_TO_SEC(total_pct) ASC LIMIT 1");
$lowest = $lowestStmt->fetch();

$highestStmt = $pdo->query("SELECT TIME_TO_SEC(total_pct) AS pct_seconds, total_pct FROM info_sheet WHERE total_pct IS NOT NULL ORDER BY TIME_TO_SEC(total_pct) DESC LIMIT 1");
$highest = $highestStmt->fetch();

$pctLabels = ['Lowest PCT (' . $lowest['total_pct'] . ')', 'Highest PCT (' . $highest['total_pct'] . ')'];
$pctValues = [(int)$lowest['pct_seconds'], (int)$highest['pct_seconds']];

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
?>

<script>
  // Set the total row count in the orange div
  document.getElementById("totalRows").innerHTML = "<?php echo $totalRows; ?>";

  // Set the total sum of 'number_of_records_retrieved_printed' in the yellow div
  document.getElementById("totalPrinted").innerHTML = "<?php echo $totalRetrievedPrinted; ?>";

  // Gender Chart
  const genderLabels = <?php echo json_encode($genders); ?>;
  const genderData = <?php echo json_encode($counts); ?>;
  new Chart(document.getElementById('genderChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: genderLabels,
      datasets: [{
        label: 'Gender Distribution',
        data: genderData,
        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#8E44AD'],
        borderWidth: 1
      }]
    },
    options: {
      responsive: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });

  // PCT Chart
  const pctLabels = <?php echo json_encode($pctLabels); ?>;
  const pctData = <?php echo json_encode($pctValues); ?>;
  new Chart(document.getElementById('pctChart').getContext('2d'), {
    type: 'pie',
    data: {
      labels: pctLabels,
      datasets: [{
        label: 'PCT Comparison',
        data: pctData,
        backgroundColor: ['#2ecc71', '#e74c3c'],
        borderWidth: 1
      }]
    },
    options: {
      responsive: false,
      plugins: {
        legend: {
          position: 'bottom'
        }
      }
    }
  });

  // Purpose Chart
  const purposeLabels = <?php echo json_encode($purposeLabels); ?>;
  const purposeData = <?php echo json_encode($purposeCounts); ?>;
  new Chart(document.getElementById('purposeChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: purposeLabels,
      datasets: [{
        label: 'Purpose Count',
        data: purposeData,
        backgroundColor: '#9b59b6',
        borderWidth: 1
      }]
    },
    options: {
      responsive: false,
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });

  // Work Category Chart
  const workCategoryLabels = <?php echo json_encode($workCategoryLabels); ?>;
  const workCategoryData = <?php echo json_encode($workCategoryCounts); ?>;
  new Chart(document.getElementById('workCategoryChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: workCategoryLabels,
      datasets: [{
        label: 'Work Category Count',
        data: workCategoryData,
        backgroundColor: '#e67e22',
        borderWidth: 1
      }]
    },
    options: {
      responsive: false,
      scales: {
        x: {
          beginAtZero: true
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });

  // Requested Record Chart
  const requestLabels = <?php echo json_encode($requestLabels); ?>;
  const requestData = <?php echo json_encode($requestCounts); ?>;
  new Chart(document.getElementById('requestChart').getContext('2d'), {
    type: 'bar',
    data: {
      labels: requestLabels,
      datasets: [{
        label: 'Request Record Count',
        data: requestData,
        backgroundColor: '#95a5a6',
        borderWidth: 1
      }]
    },
    options: {
      responsive: false,
      scales: {
        x: {
          beginAtZero: true
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });
</script>