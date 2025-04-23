<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Dashboard - MWPD Filing System";
include '_head.php';

// Get statistics from the database
try {
    // Total Direct Hire records count
    $direct_hire_total_stmt = $pdo->query("SELECT COUNT(*) FROM direct_hire");
    $direct_hire_total = $direct_hire_total_stmt->fetchColumn();
    
    // Direct Hire pending approvals count
    $direct_hire_pending_stmt = $pdo->query("SELECT COUNT(*) FROM direct_hire WHERE status = 'pending'");
    $direct_hire_pending = $direct_hire_pending_stmt->fetchColumn();
    
    // Get recent pending approvals
    $pending_stmt = $pdo->query("
        SELECT 'Direct Hire' as process_type, name, status, created_at 
        FROM direct_hire 
        WHERE status = 'pending' 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $pending_approvals = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity logs (using the direct_hire for now)
    $activity_stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN status = 'approved' THEN CONCAT(name, ' was approved')
                WHEN status = 'denied' THEN CONCAT(name, ' was denied')
                ELSE CONCAT(name, ' was added to the system')
            END as activity,
            created_at,
            updated_at
        FROM direct_hire
        ORDER BY 
            CASE 
                WHEN updated_at > created_at THEN updated_at
                ELSE created_at
            END DESC
        LIMIT 5
    ");
    $activity_logs = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Set defaults in case of database error
    $direct_hire_total = 0;
    $direct_hire_pending = 0;
    $pending_approvals = [];
    $activity_logs = [];
}

// Format time differences for display
function time_elapsed_string($datetime, $full = false) {
    // Set timezone to match your server
    date_default_timezone_set('Asia/Manila'); // Adjust to your timezone
    
    // Get current time
    $now = new DateTime();
    
    // Convert the datetime string to a DateTime object with proper timezone
    $ago = new DateTime($datetime);
    
    // Get the difference
    $diff = $now->diff($ago);
    
    // Calculate seconds difference for "just now" detection
    $seconds_diff = $now->getTimestamp() - $ago->getTimestamp();
    
    // If less than 60 seconds, show "just now"
    if ($seconds_diff < 60) {
        return "just now";
    }
    
    // If less than 60 minutes, show in minutes
    if ($diff->h == 0 && $diff->d == 0 && $diff->m == 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    
    // If less than 24 hours, show in hours and minutes
    if ($diff->d == 0 && $diff->m == 0) {
        if ($diff->i > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ' . 
                   $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        }
    }
    
    // If less than 7 days, show in days
    if ($diff->d < 7 && $diff->m == 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    
    // If less than 30 days, show in weeks
    if ($diff->d < 30 && $diff->m == 0) {
        $weeks = floor($diff->d / 7);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }
    
    // If less than 365 days, show in months
    if ($diff->y == 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    
    // Otherwise, show in years
    return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
}
?>

<body>

  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php
      // Get current filename like 'dashboard-eme.php'
      $currentFile = basename($_SERVER['PHP_SELF']);

      // Remove the file extension
      $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);

      // Replace dashes with spaces
      $pageTitle = ucwords(str_replace('-', ' ', $fileWithoutExtension));

      include '_header.php';
      ?>

      <main class="main-content">
        <main class="dashboard-grid">
          <!-- Box 1: Total Records -->

          <!-- Add As of this month kineme eme -->
          <div class="bento-box box-total-records">
            <h2>Total Records</h2>
            <div class="record-cards">
              <!-- Card 1: Direct Hire -->
              <div class="record-card">
                <div class="card-text">
                  <span class="record-count"><?= number_format($direct_hire_total) ?></span>
                  <span class="record-label">Direct Hire</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-briefcase"></i>
                </div>
              </div>

              <!-- Card 2: Balik Manggagawa -->
              <div class="record-card">
                <div class="card-text">
                  <span class="record-count">0</span>
                  <span class="record-label">Balik Manggagawa</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-sign-in-alt"></i>
                </div>
              </div>

              <!-- Card 3: Gov-to-Gov -->
              <div class="record-card">
                <div class="card-text">
                  <span class="record-count">0</span>
                  <span class="record-label">Gov-to-Gov</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-university"></i>
                </div>
              </div>

              <!-- Card 4: Job Fairs -->
              <div class="record-card">
                <div class="card-text">
                  <span class="record-count">0</span>
                  <span class="record-label">Job Fairs</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-clipboard-list"></i>
                </div>
              </div>
            </div>
          </div>


          <!-- Box 2: Pending Approvals -->
          <div class="bento-box box-pending-approvals">
            <h2>Pending Approvals <span class="count-badge"><?= $direct_hire_pending ?></span></h2>
            <?php if (count($pending_approvals) > 0): ?>
            <table>
              <thead>
                <tr>
                  <th>Process Type</th>
                  <th>Applicant Name</th>
                  <th>Status</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pending_approvals as $approval): ?>
                <tr>
                  <td><?= htmlspecialchars($approval['process_type']) ?></td>
                  <td><?= htmlspecialchars($approval['name']) ?></td>
                  <td><span class="status-badge pending"><?= ucfirst(htmlspecialchars($approval['status'])) ?></span></td>
                  <td title="<?= date('M j, Y g:i A', strtotime($approval['created_at'])) ?>"><?= time_elapsed_string($approval['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php else: ?>
            <div class="no-records">
                <i class="fa fa-check-circle"></i>
                <p>No pending approvals at this time</p>
            </div>
            <?php endif; ?>
            <div class="view-all">
              <!-- <p class="records-info">Showing <?= min(count($pending_approvals), 5) ?> of <?= $direct_hire_pending ?> pending records</p> -->
            </div>
          </div>

          <!-- Box 3: Calendar -->
          <div class="bento-box box-calendar">
            <div class="calendar-header">
              <button id="prevMonth">&lt;</button>
              <h2 id="monthLabel">April 2025</h2>
              <button id="nextMonth">&gt;</button>
            </div>

            <div class="calendar-weekdays">
              <div>Sun</div>
              <div>Mon</div>
              <div>Tue</div>
              <div>Wed</div>
              <div>Thu</div>
              <div>Fri</div>
              <div>Sat</div>
            </div>

            <div class="calendar-grid" id="calendarGrid">
              <!-- JS will populate the days here -->
            </div>
          </div>

          <!-- Box 4: Activity List -->
          <div class="bento-box box-activity-log">
            <h2>Recent Activity</h2>
            <?php if (count($activity_logs) > 0): ?>
            <ul class="activity-list">
              <?php foreach ($activity_logs as $log): 
                  $time = !empty($log['updated_at']) && $log['updated_at'] > $log['created_at'] 
                          ? $log['updated_at'] 
                          : $log['created_at'];
                  
                  // Format exact time for tooltip  
                  $exact_time = date('M j, Y g:i A', strtotime($time));
              ?>
              <li>
                <div class="activity-time"><?= date('M j', strtotime($time)) ?></div>
                <div class="activity-info">
                  <div class="activity-text"><?= htmlspecialchars($log['activity']) ?></div>
                  <div class="activity-meta" title="<?= $exact_time ?>"><?= time_elapsed_string($time) ?></div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
            <div class="view-all">
              <!-- <p class="records-info">Showing <?= count($activity_logs) ?> most recent activities</p> -->
            </div>
            <?php else: ?>
            <div class="no-records">
                <i class="fa fa-history"></i>
                <p>No recent activity</p>
            </div>
            <?php endif; ?>
          </div>
          
          <!-- Box 5: Statistics (Bottom) -->
          <div class="bento-box box-statistics">
            <h2>Statistics</h2>
            <div class="statistics-content">
              <!-- Monthly Metrics -->
              <div class="stat-metrics">
                <div class="metric-items">
                  <div class="metric-item">
                    <div class="metric-label">Total Records</div>
                    <div class="metric-value"><?= number_format($direct_hire_total) ?></div>
                    <div class="metric-trend positive">+12.5%</div>
                  </div>
                  <div class="metric-item">
                    <div class="metric-label">Approval Rate</div>
                    <div class="metric-value">84%</div>
                    <div class="metric-trend positive">+2.3%</div>
                  </div>
                  <div class="metric-item">
                    <div class="metric-label">Avg Processing</div>
                    <div class="metric-value">3.2 days</div>
                    <div class="metric-trend negative">+0.5 days</div>
                  </div>
                  <div class="metric-item">
                    <div class="metric-label">Pending</div>
                    <div class="metric-value"><?= number_format($direct_hire_pending) ?></div>
                    <div class="metric-trend neutral">0%</div>
                  </div>
                  <div class="metric-item">
                    <div class="metric-label">Total in Database</div>
                    <div class="metric-value"><?= number_format($direct_hire_total) ?></div>
                    <div class="metric-trend positive">+15.2%</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </main>


      </main>
    </div>
  </div>

</body>

<script>
  const monthLabel = document.getElementById('monthLabel');
  const calendarGrid = document.getElementById('calendarGrid');
  const prevBtn = document.getElementById('prevMonth');
  const nextBtn = document.getElementById('nextMonth');

  let currentDate = new Date();

  function renderCalendar(date) {
    const year = date.getFullYear();
    const month = date.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    monthLabel.textContent = date.toLocaleString('default', {
      month: 'long',
      year: 'numeric'
    });
    calendarGrid.innerHTML = '';

    // Padding before first day
    for (let i = 0; i < firstDay; i++) {
      calendarGrid.innerHTML += `<div></div>`;
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const isEvent = [5, 12].includes(day); // Sample event days
      calendarGrid.innerHTML += `<div class="day ${isEvent ? 'event' : ''}">${day}</div>`;
    }
  }

  prevBtn.onclick = () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar(currentDate);
  };

  nextBtn.onclick = () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar(currentDate);
  };

  renderCalendar(currentDate);
</script>

</body>
</html>