<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Dashboard - MWPD Filing System";
include '_head.php';

// Get user role from session
$userRole = $_SESSION['role'] ?? 'staff';

// Get statistics from the database
try {
    // Total Direct Hire records count
    $direct_hire_total_stmt = $pdo->query("SELECT COUNT(*) FROM direct_hire");
    $direct_hire_total = $direct_hire_total_stmt->fetchColumn();
    
    // Total Balik Manggagawa records count
    $balik_manggagawa_total_stmt = $pdo->query("SELECT COUNT(*) FROM bm");
    $balik_manggagawa_total = $balik_manggagawa_total_stmt->fetchColumn();
    
    // Total Gov-to-Gov records count
    $gov_to_gov_total_stmt = $pdo->query("SELECT COUNT(*) FROM gov_to_gov");
    $gov_to_gov_total = $gov_to_gov_total_stmt->fetchColumn();
    
    // Total Job Fairs records count
    $job_fairs_total_stmt = $pdo->query("SELECT COUNT(*) FROM job_fairs");
    $job_fairs_total = $job_fairs_total_stmt->fetchColumn();
    
    // Total Information Sheet records count
    $info_sheet_total_stmt = $pdo->query("SELECT COUNT(*) FROM info_sheet");
    $info_sheet_total = $info_sheet_total_stmt->fetchColumn();
    
    // Direct Hire pending approvals count
    $direct_hire_pending_stmt = $pdo->query("SELECT COUNT(*) FROM direct_hire WHERE status = 'pending'");
    $direct_hire_pending = $direct_hire_pending_stmt->fetchColumn();
    
    // Balik Manggagawa pending approvals count
    $bm_pending_stmt = $pdo->query("SELECT COUNT(*) FROM bm WHERE remarks = 'Pending'");
    $bm_pending = $bm_pending_stmt->fetchColumn();
    
    // Total pending approvals count
    $total_pending = $direct_hire_pending + $bm_pending;
    
    // Get recent pending approvals from all modules
    $pending_stmt = $pdo->query("
        (SELECT 'Direct Hire' as process_type, name as applicant_name, status, created_at 
        FROM direct_hire 
        WHERE status = 'pending')
        UNION ALL
        (SELECT 'Balik Manggagawa' as process_type, CONCAT(last_name, ', ', given_name) as applicant_name, remarks as status, NOW() as created_at 
        FROM bm 
        WHERE remarks = 'Pending')
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $pending_approvals = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity logs from all modules
    $activity_stmt = $pdo->query("
        (SELECT 
            CONCAT('Direct Hire: ', CASE 
                WHEN status = 'approved' THEN CONCAT(name, ' was approved')
                WHEN status = 'denied' THEN CONCAT(name, ' was denied')
                WHEN status = 'pending' THEN CONCAT(name, ' is pending approval')
                ELSE CONCAT(name, ' was added to the system')
            END) as activity,
            created_at,
            updated_at,
            CASE WHEN updated_at > created_at THEN updated_at ELSE created_at END as sort_date
        FROM direct_hire)
        UNION ALL
        (SELECT 
            CONCAT('Balik Manggagawa: ', CONCAT(last_name, ', ', given_name, ' ', 
                CASE 
                    WHEN remarks = 'Approved' THEN 'was approved'
                    WHEN remarks = 'Declined' THEN 'was declined'
                    WHEN remarks = 'Pending' THEN 'is pending approval'
                    ELSE 'was added to the system'
                END)) as activity,
            NULL as created_at,
            NULL as updated_at,
            NOW() as sort_date
        FROM bm)
        UNION ALL
        (SELECT 
            CONCAT('Gov-to-Gov: ', CONCAT(last_name, ', ', first_name, ' was added to the system')) as activity,
            NULL as created_at,
            NULL as updated_at,
            NOW() as sort_date
        FROM gov_to_gov)
        UNION ALL
        (SELECT 
            CONCAT('Job Fair: ', venue, ' (', date, ') - ', status) as activity,
            NULL as created_at,
            NULL as updated_at,
            date as sort_date
        FROM job_fairs)
        ORDER BY sort_date DESC
        LIMIT 10
    ");
    $activity_logs = $activity_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get job fair events for calendar
    $current_month = date('m');
    $current_year = date('Y');
    
    // Get all job fairs for calendar highlighting
    $job_fairs_stmt = $pdo->query("
        SELECT id, date, venue, contact_info, status 
        FROM job_fairs 
        WHERE status != 'cancelled'
        ORDER BY date ASC
    ");
    $job_fairs = $job_fairs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get job fairs for current month to display in "This Month" section
    $this_month_fairs_stmt = $pdo->query("
        SELECT id, date, venue, contact_info, status 
        FROM job_fairs 
        WHERE MONTH(date) = $current_month 
        AND YEAR(date) = $current_year
        AND status != 'cancelled'
        ORDER BY date ASC
    ");
    $this_month_fairs = $this_month_fairs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Set defaults in case of database error
    $direct_hire_total = 0;
    $direct_hire_pending = 0;
    $pending_approvals = [];
    $activity_logs = [];
    $job_fairs = [];
    $this_month_fairs = [];
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

<!-- Dashboard scrolling now handled by external CSS -->


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
        <?php if (!empty($_SESSION['error_message'])): ?>
          <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
          </div>
          <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if ($userRole === 'regional director'): ?>
          <!-- Regional Director Dashboard -->
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> Welcome, Regional Director. You can review and approve pending records in the <a href="approvals.php">Approvals</a> page.
          </div>
          <div class="dashboard-redirect-btn">
            <a href="approvals.php" class="btn btn-primary btn-lg">
              <i class="fa fa-check-circle"></i> Go to Approvals Page
            </a>
          </div>
        <?php elseif ($userRole === 'div head'): ?>
          <!-- Division Head Dashboard -->
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> Welcome, Division Head. You can manage user accounts in the <a href="accounts.php">Accounts</a> page.
          </div>
          <div class="dashboard-redirect-btn">
            <a href="accounts.php" class="btn btn-primary btn-lg">
              <i class="fa fa-users-cog"></i> Go to Account Management
            </a>
          </div>
        <?php endif; ?>
        
        <!-- User Dashboard Content -->
        <main class="dashboard-grid">
          <!-- Box 1: Total Records -->

          <!-- Add As of this month kineme eme -->
          <div class="bento-box box-total-records">
            <h2>Total Records</h2>
            <div class="record-cards">
              <!-- Card 1: Direct Hire -->
              <div class="record-card" id="directHireCard">
                <div class="card-text">
                  <span class="record-count"><?= number_format($direct_hire_total) ?></span>
                  <span class="record-label">Direct Hire</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-briefcase"></i>
                </div>
              </div>

              <!-- Card 2: Balik Manggagawa -->
              <div class="record-card" id="balikManggawaCard">
                <div class="card-text">
                  <span class="record-count"><?= number_format($balik_manggagawa_total) ?></span>
                  <span class="record-label">Balik Manggagawa</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-sign-in-alt"></i>
                </div>
              </div>

              <!-- Card 3: Gov-to-Gov -->
              <div class="record-card" id="govToGovCard">
                <div class="card-text">
                  <span class="record-count"><?= number_format($gov_to_gov_total) ?></span>
                  <span class="record-label">Gov-to-Gov</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-university"></i>
                </div>
              </div>

              <!-- Card 4: Job Fairs -->
              <div class="record-card" id="jobFairsCard">
                <div class="card-text">
                  <span class="record-count"><?= number_format($job_fairs_total) ?></span>
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
            <div class="scrollable-container">
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
                    <td><?= htmlspecialchars($approval['applicant_name']) ?></td>
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
            </div>
            <div class="view-all">
              <!-- <p class="records-info">Showing <?= min(count($pending_approvals), 5) ?> of <?= $direct_hire_pending ?> pending records</p> -->
            </div>
          </div>

          <!-- Box 3: Calendar -->
          <div class="bento-box box-calendar dashboard-stats" id="calendarBox">
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

          <!-- Box 4: This Month List -->
          <div class="bento-box box-activity-log job-fairs-this-month">
            <h2>This Month's Job Fairs</h2>
            <?php if (count($this_month_fairs) > 0): ?>
            <div class="scrollable-container">
              <ul class="activity-list">
                <?php foreach ($this_month_fairs as $fair): ?>
                <li>
                  <div class="activity-time"><?= date('M j', strtotime($fair['date'])) ?></div>
                  <div class="activity-info">
                    <div class="activity-text"><?= htmlspecialchars($fair['venue']) ?></div>
                    <div class="activity-meta"><?= htmlspecialchars($fair['contact_info'] ?? 'N/A') ?></div>
                    <div class="activity-status">
                      <span class="status-badge <?= strtolower($fair['status']) ?>">
                        <?= ucfirst(htmlspecialchars($fair['status'])) ?>
                      </span>
                    </div>
                  </div>
                </li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="view-all">
              <!-- <a href="job_fairs.php" class="btn-link">View all job fairs <i class="fa fa-arrow-right"></i></a> -->
            </div>
            <?php else: ?>
            <div class="no-records">
              <i class="fa fa-calendar"></i>
              <p>No job fairs scheduled this month</p>
            </div>
            <?php endif; ?>
          </div>
          
          <!-- Box 5: EMe (Bottom) -->
          <div class="bento-box box-statistics recent-activity">
            <h2>Recent System Activity</h2>
            <?php if (count($activity_logs) > 0): ?>
            <div class="scrollable-container">
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
            </div>
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
        </main>


      </main>
      
      <!-- Floating Action Button Menu (Circular UI) -->
      <div class="floating-action-menu">
        <div class="main-button">
          <i class="fa fa-chevron-left"></i>
        </div>
        <ul class="menu-options" id="floatingMenuOptions">
          <li class="menu-option" data-toggle="tooltip" title="Interactive Guide">
            <div class="option-button" id="wizardGuideButton" style="background-color: #28a745;">
              <i class="fa fa-magic"></i>
            </div>
          </li>
          <!-- Generate Reports option removed as requested -->
        </ul>
      </div>
      
      <!-- Activity Logs Modal -->
      <div class="modal fade" id="activityLogsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fa fa-history"></i> System Activity Logs</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Timestamp</th>
                      <th>User</th>
                      <th>Action</th>
                      <th>Module</th>
                      <th>Details</th>
                    </tr>
                  </thead>
                  <tbody id="activityLogsTable">
                    <!-- Activity logs will be loaded here via AJAX -->
                    <tr>
                      <td colspan="5" class="text-center">Loading activity logs...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <a href="#" id="viewAllActivityBtn" class="btn btn-primary">View All Activity</a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Blacklist Logs Modal -->
      <div class="modal fade" id="blacklistLogsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fa fa-ban"></i> Blacklist Activity</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="table-responsive">
                <table class="table table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Name</th>
                      <th>Identifier</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="blacklistLogsTable">
                    <!-- Blacklist logs will be loaded here via AJAX -->
                    <tr>
                      <td colspan="5" class="text-center">Loading blacklist logs...</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <a href="#" id="viewAllBlacklistBtn" class="btn btn-primary">View All Blacklist Activity</a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Generate Report Modal -->
      <div class="modal fade" id="reportGenModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="fa fa-file-export"></i> Generate Reports</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form id="reportForm">
                <div class="form-group">
                  <label for="reportType">Report Type</label>
                  <select class="form-control" id="reportType" name="reportType" required>
                    <option value="">-- Select Report Type --</option>
                    <option value="direct_hire">Direct Hire Records</option>
                    <option value="balik_manggagawa">Balik Manggagawa Records</option>
                    <option value="gov_to_gov">Gov-to-Gov Records</option>
                    <option value="job_fairs">Job Fair Records</option>
                    <option value="blacklist">Blacklist Records</option>
                    <option value="audit_log">System Audit Logs</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="dateRange">Date Range</label>
                  <div class="row">
                    <div class="col">
                      <input type="date" class="form-control" id="startDate" name="startDate">
                      <small class="form-text text-muted">Start Date</small>
                    </div>
                    <div class="col">
                      <input type="date" class="form-control" id="endDate" name="endDate">
                      <small class="form-text text-muted">End Date</small>
                    </div>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="exportFormat">Export Format</label>
                  <div class="btn-group btn-group-toggle" data-toggle="buttons">
                    <label class="btn btn-outline-primary active">
                      <input type="radio" name="exportFormat" value="csv" checked> CSV
                    </label>
                    <label class="btn btn-outline-primary">
                      <input type="radio" name="exportFormat" value="excel"> Excel
                    </label>
                    <label class="btn btn-outline-primary">
                      <input type="radio" name="exportFormat" value="pdf"> PDF
                    </label>
                    <label class="btn btn-outline-primary">
                      <input type="radio" name="exportFormat" value="docx"> DOCX
                    </label>
                  </div>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" id="generateReportBtn">Generate Report</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script>
  // JavaScript to ensure no scrollbars
  document.addEventListener('DOMContentLoaded', function() {
    // Force hide scrollbars on load
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
    document.body.style.position = 'fixed';
    document.body.style.width = '100%';
    document.body.style.height = '100vh';
    
    // Prevent any scrolling events
    document.body.addEventListener('scroll', preventScroll, {passive: false});
    document.addEventListener('wheel', preventScroll, {passive: false});
    document.addEventListener('touchmove', preventScroll, {passive: false});
    
    function preventScroll(e) {
      if (!e.target.closest('.dashboard-content')) {
        e.preventDefault();
      }
    }
  });
  </script>
  
  <!-- Disable long page scroll -->
  <style>
    /* ===== COMPLETELY ELIMINATE ALL SCROLLBARS ===== */
    /* Global scrollbar elimination */
    ::-webkit-scrollbar {
      width: 0 !important;
      height: 0 !important;
      display: none !important;
    }
    
    * {
      -ms-overflow-style: none !important; /* IE and Edge */
      scrollbar-width: none !important; /* Firefox */
    }
    
    /* Absolute position method to prevent scroll */
    html {
      overflow: hidden !important;
      height: 100% !important;
    }
    
    body {
      overflow: hidden !important;
      position: fixed !important;
      top: 0 !important;
      right: 0 !important;
      bottom: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: 100% !important;
    }
    
    /* Lock all containers */
    .layout-wrapper, .content-wrapper, #app, #content, #main {
      overflow: hidden !important;
      max-height: 100vh !important;
      height: 100vh !important;
    }
    
    /* Make dashboard content area internally scrollable but with hidden scrollbar */
    main.dashboard-content, .dashboard-main, .dashboard-container {
      overflow-y: auto !important;
      overflow-x: hidden !important;
      height: calc(100vh - 70px) !important;
      max-height: calc(100vh - 70px) !important;
      padding-bottom: 0 !important;
      scrollbar-width: none !important;
      -ms-overflow-style: none !important;
    }
    
    /* Make individual dashboard card content areas scrollable */
    .card-body {
      max-height: 300px;
      overflow-y: auto;
    }
    
    /* Wizard Guide Styles */
    .wizard-navigation .progress {
      height: 8px;
      margin-bottom: 15px;
    }
    
    .wizard-nav .nav-link {
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
    }
    
    .wizard-nav .nav-link:hover {
      background-color: rgba(40, 167, 69, 0.1);
    }
    
    .wizard-nav .nav-link.active {
      background-color: #28a745;
      color: white;
    }
    
    .wizard-step {
      animation: fadeIn 0.5s ease;
    }
    
    .wizard-footer {
      border-top: 1px solid #eee;
      padding-top: 15px;
      margin-top: 20px;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    /* Bookmark-style Floating Action Menu */
    .floating-action-menu {
      position: fixed;
      bottom: 5px; /* Moved lower, closer to the bottom */
      right: 0;  /* Align to the right edge */
      z-index: 1000;
      transition: all 0.3s ease;
      height: 200px; /* Fixed height to contain the menu */
    }
    
    .main-button {
      width: 20px; /* Much narrower by default to hide against wall */
      height: 50px; /* Shorter height */
      background-color: rgba(0, 123, 255, 0.8);
      border-radius: 5px 0 0 0; /* Rounded on top-left corner only */
      box-shadow: -3px 0px 8px rgba(0, 0, 0, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      cursor: pointer;
      transition: all 0.3s ease;
      opacity: 0.7; /* Less visible by default */
    }
    
    .main-button:hover {
      width: 45px; /* Pop out significantly on hover */
      opacity: 1;
      box-shadow: -4px 4px 12px rgba(0, 0, 0, 0.4);
    }
    
    .main-button i {
      transform: rotate(-90deg); /* Rotate arrow to point down when closed */
      transition: transform 0.3s ease;
    }
    
    .menu-options {
      position: absolute;
      right: 0px; /* Aligned with the tab */
      bottom: -5px; /* Moved down a bit more, overlapping more with the tab */
      list-style: none;
      padding: 0;
      margin: 0;
      display: none;
      flex-direction: column; /* Stack vertically from top to bottom */
      align-items: center; /* Center align items */
      width: 40px; /* Same width as the tab for perfect alignment */
    }
    
    .menu-options.show {
      display: flex;
    }
    
    /* Make the button stay popped out when menu is open */
    .main-button.active {
      width: 45px !important;
      opacity: 1 !important;
    }
    
    .menu-option {
      margin-bottom: 5px; /* Reduced space between buttons */
    }
    
    .option-button {
      width: 40px; /* Same width as the tab */
      height: 40px; /* Slightly smaller for better visual balance */
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      cursor: pointer;
      box-shadow: -3px 3px 8px rgba(0, 0, 0, 0.3);
      transition: all 0.3s ease;
      background-color: rgba(0, 123, 255, 0.8);
      opacity: 0; /* Start hidden */
      transform: translateX(30px) scale(0.7); /* Start further right and smaller */
    }
    
    .option-button:hover {
      transform: scale(1.2);
      opacity: 1; /* Fully visible on hover */
      box-shadow: -4px 4px 12px rgba(0, 0, 0, 0.4);
      z-index: 1010; /* Ensure hovered button appears above others */
    }
    
    /* Animation for showing menu options */
    .menu-options.show .option-button {
      opacity: 1; /* Fully visible */
      transform: scale(1.05) translateX(0); /* Pop out slightly */
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); /* Bouncy effect */
    }
    
    /* Staggered animation for vertically stacked menu items */
    .menu-option:nth-child(3) .option-button {
      transition-delay: 0.05s;
    }
    
    .menu-option:nth-child(2) .option-button {
      transition-delay: 0.15s;
    }
    
    .menu-option:nth-child(1) .option-button {
      transition-delay: 0.25s;
    }
    
    /* Button Colors with increased vividness */
    #activityLogsButton {
      background-color: rgba(40, 167, 69, 0.9); /* Green with high opacity */
      box-shadow: 0 0 10px rgba(40, 167, 69, 0.4); /* Green glow */
    }
    
    #activityLogsButton:hover {
      background-color: rgba(40, 167, 69, 1.0); /* Full green */
      box-shadow: 0 0 15px rgba(40, 167, 69, 0.6); /* Increased green glow */
    }
    
    #blacklistLogsButton {
      background-color: rgba(220, 53, 69, 0.9); /* Red with high opacity */
      color: white;
      text-decoration: none;
      box-shadow: 0 0 10px rgba(220, 53, 69, 0.4); /* Red glow */
    }
    
    #blacklistLogsButton:hover {
      background-color: rgba(220, 53, 69, 1.0); /* Full red */
      color: white;
      text-decoration: none;
      box-shadow: 0 0 15px rgba(220, 53, 69, 0.6); /* Increased red glow */
    }
    
    #reportGenButton {
      background-color: rgba(23, 162, 184, 0.9); /* Teal with high opacity */
      box-shadow: 0 0 10px rgba(23, 162, 184, 0.4); /* Teal glow */
    }
    
    #reportGenButton:hover {
      background-color: rgba(23, 162, 184, 1.0); /* Full teal */
      box-shadow: 0 0 15px rgba(23, 162, 184, 0.6); /* Increased teal glow */
    }
  </style>

</body>

<script>
  // Bookmark-style Menu functionality
  document.addEventListener('DOMContentLoaded', function() {
    const mainButton = document.querySelector('.main-button');
    const menuOptions = document.querySelector('.menu-options');
    const activityLogsButton = document.getElementById('activityLogsButton');
    const blacklistLogsButton = document.getElementById('blacklistLogsButton');
    const reportGenButton = document.getElementById('reportGenButton');
    
    // Toggle the floating action menu
    document.querySelector('.main-button').addEventListener('click', function() {
      const menuOptions = document.querySelector('.menu-options');
      const mainButtonIcon = document.querySelector('.main-button i');
      const mainButton = document.querySelector('.main-button');
      
      menuOptions.classList.toggle('show');
      mainButton.classList.toggle('active'); // Toggle active class for button
      
      // Toggle the rotation of the arrow
      if (menuOptions.classList.contains('show')) {
        mainButtonIcon.style.transform = 'rotate(90deg)';
        // Show menu options with staggered animation
        const options = document.querySelectorAll('.option-button');
        options.forEach((option, index) => {
          setTimeout(() => {
            option.style.opacity = '1';
            option.style.transform = 'translateX(0) scale(1)';
          }, 50 * index);
        });
      } else {
        mainButtonIcon.style.transform = 'rotate(-90deg)';
        // Hide menu options
        const options = document.querySelectorAll('.option-button');
        options.forEach((option) => {
          option.style.opacity = '0';
          option.style.transform = 'translateX(30px) scale(0.7)';
        });
      }
    });
    
    // Hide menu when clicking outside
    document.addEventListener('click', function(event) {
      if (!event.target.closest('.floating-action-menu')) {
        menuOptions.classList.remove('show');
        // Reset icon when menu closes
        const icon = mainButton.querySelector('i');
        icon.style.transform = 'rotate(-90deg)'; // Point downwards when closed
        mainButton.classList.remove('active'); // Remove active class from button
        mainButton.style.width = '20px'; // Reset to narrow width when clicking outside
        
        // Hide menu options
        const options = document.querySelectorAll('.option-button');
        options.forEach((option) => {
          option.style.opacity = '0';
          option.style.transform = 'translateX(30px) scale(0.7)';
        });
      }
    });
    
    // Add hover effect to main button to preview the menu
    mainButton.addEventListener('mouseenter', function() {
      if (!menuOptions.classList.contains('show')) {
        mainButton.style.width = '45px'; // Match the hover width in CSS
      }
    });
    
    mainButton.addEventListener('mouseleave', function() {
      if (!menuOptions.classList.contains('show')) {
        mainButton.style.width = '20px'; // Return to default narrow width
      }
    });
    
    // Interactive Wizard Guide button - click handler defined in the wizard script section
    
    // Blacklist button now uses direct link to blacklist.php instead of a modal
    // The click handler has been removed and replaced with an <a> tag link
    
    // Report Generation button
    reportGenButton.addEventListener('click', function() {
      $('#reportGenModal').modal('show');
    });
    
    // Generate Report button
    document.getElementById('generateReportBtn').addEventListener('click', function() {
      const reportType = document.getElementById('reportType').value;
      const startDate = document.getElementById('startDate').value;
      const endDate = document.getElementById('endDate').value;
      const exportFormat = document.querySelector('input[name="exportFormat"]:checked').value;
      
      if (!reportType) {
        alert('Please select a report type');
        return;
      }
      
      // Construct the URL for report generation
      let url = `generate_report.php?type=${reportType}&format=${exportFormat}`;
      if (startDate) url += `&start_date=${startDate}`;
      if (endDate) url += `&end_date=${endDate}`;
      
      // Redirect to report generation URL
      window.location.href = url;
    });
  });
  
  // Load Activity Logs via AJAX
  function loadActivityLogs() {
    const activityLogsTable = document.getElementById('activityLogsTable');
    activityLogsTable.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading activity logs...</td></tr>';
    
    fetch('get_activity_logs.php?limit=10')
      .then(response => response.json())
      .then(data => {
        if (data.length === 0) {
          activityLogsTable.innerHTML = '<tr><td colspan="5" class="text-center">No activity logs found</td></tr>';
          return;
        }
        
        activityLogsTable.innerHTML = '';
        data.forEach(log => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${log.timestamp}</td>
            <td>${log.username} <small class="text-muted d-block">${log.role}</small></td>
            <td><span class="badge badge-info">${log.action}</span></td>
            <td>${log.module}</td>
            <td>${log.details}</td>
          `;
          activityLogsTable.appendChild(row);
        });
      })
      .catch(error => {
        console.error('Error loading activity logs:', error);
        activityLogsTable.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading activity logs</td></tr>';
      });
  }
  
  // Load Blacklist Logs via AJAX
  function loadBlacklistLogs() {
    const blacklistLogsTable = document.getElementById('blacklistLogsTable');
    blacklistLogsTable.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading blacklist logs...</td></tr>';
    
    fetch('get_blacklist_logs.php?limit=10')
      .then(response => response.json())
      .then(data => {
        if (data.length === 0) {
          blacklistLogsTable.innerHTML = '<tr><td colspan="5" class="text-center">No blacklist activity found</td></tr>';
          return;
        }
        
        blacklistLogsTable.innerHTML = '';
        data.forEach(log => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${log.date}</td>
            <td>${log.name}</td>
            <td>${log.identifier}</td>
            <td><span class="badge ${log.match ? 'badge-danger' : 'badge-success'}">${log.match ? 'Match Found' : 'No Match'}</span></td>
            <td>${log.action}</td>
          `;
          blacklistLogsTable.appendChild(row);
        });
      })
      .catch(error => {
        console.error('Error loading blacklist logs:', error);
        blacklistLogsTable.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading blacklist logs</td></tr>';
      });
  }
  
  // Calendar functionality
  const monthLabel = document.getElementById('monthLabel');
  const calendarGrid = document.getElementById('calendarGrid');
  const prevBtn = document.getElementById('prevMonth');
  const nextBtn = document.getElementById('nextMonth');

  let currentDate = new Date();
  
  // Job fair dates from PHP
  const jobFairDates = [
    <?php foreach ($job_fairs as $fair): ?>
      {
        date: "<?= $fair['date'] ?>",
        venue: "<?= addslashes($fair['venue']) ?>",
        status: "<?= $fair['status'] ?>"
      },
    <?php endforeach; ?>
  ];

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
      // Check if this day has any job fairs
      const currentDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const hasEvent = jobFairDates.some(event => {
        const eventDate = event.date.split(' ')[0]; // Get just the date part
        return eventDate === currentDateStr;
      });
      
      const events = jobFairDates.filter(event => {
        const eventDate = event.date.split(' ')[0]; // Get just the date part
        return eventDate === currentDateStr;
      });
      
      let tooltip = '';
      if (events.length > 0) {
        tooltip = events.map(e => e.venue).join('\n');
      }
      
      calendarGrid.innerHTML += `<div class="day ${hasEvent ? 'event' : ''}" ${tooltip ? `title="${tooltip}"` : ''}>${day}</div>`;
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

<!-- Interactive Wizard Guide Modal -->
  <div class="modal fade" id="wizardGuideModal" tabindex="-1" role="dialog" aria-labelledby="wizardGuideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="wizardGuideModalLabel"><i class="fa fa-magic"></i> Interactive Guide</h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Wizard Navigation -->
          <div class="wizard-navigation mb-4">
            <div class="progress">
              <div class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <ul class="nav nav-pills nav-justified wizard-nav mt-2">
              <li class="nav-item">
                <a class="nav-link active" data-step="1"><i class="fa fa-home"></i> Welcome</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-step="2"><i class="fa fa-user-plus"></i> Direct Hire</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-step="3"><i class="fa fa-globe"></i> Gov-to-Gov</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-step="4"><i class="fa fa-plane"></i> Balik Manggagawa</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-step="5"><i class="fa fa-check-circle"></i> Approvals</a>
              </li>
            </ul>
          </div>
          
          <!-- Wizard Content -->
          <div class="wizard-content">
            <!-- Step 1: Welcome -->
            <div class="wizard-step" data-step="1">
              <div class="text-center mb-4">
                <div class="display-4 text-primary"><i class="fa fa-magic"></i></div>
                <h3>Welcome to the MWPD Filing System Guide</h3>
                <p class="lead">Let's explore the key features and workflows of the system together.</p>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="card mb-3">
                    <div class="card-body">
                      <h5><i class="fa fa-compass text-primary"></i> Navigation</h5>
                      <p>The sidebar menu contains all modules and features you need. The floating action menu provides quick access to key functions.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card mb-3">
                    <div class="card-body">
                      <h5><i class="fa fa-bell text-primary"></i> Notifications</h5>
                      <p>The system will alert you about approvals, submissions, and important events through the notification bell in the header.</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="alert alert-info">
                <i class="fa fa-lightbulb"></i> <strong>Tip:</strong> Double-clicking on any table row will open the detailed view for that record!
              </div>
            </div>
            
            <!-- Step 2: Direct Hire -->
            <div class="wizard-step" data-step="2" style="display: none;">
              <div class="text-center mb-4">
                <div class="display-4 text-primary"><i class="fa fa-user-plus"></i></div>
                <h3>Direct Hire Module</h3>
                <p class="lead">Managing direct hire applications for professional and household service workers.</p>
              </div>
              <div class="card mb-3">
                <div class="card-body">
                  <h5 class="card-title">Key Features</h5>
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex align-items-center">
                      <span class="badge badge-primary mr-2">1</span> Create new direct hire records with detailed worker and employer information
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                      <span class="badge badge-primary mr-2">2</span> Upload supporting documents and worker photos as evidence
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                      <span class="badge badge-primary mr-2">3</span> Generate clearance documents with a single click
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                      <span class="badge badge-primary mr-2">4</span> Submit records for approval to Regional Directors
                    </li>
                  </ul>
                </div>
              </div>
              <div class="alert alert-success">
                <i class="fa fa-link"></i> <strong>Quick Access:</strong> <a href="direct_hire.php" class="alert-link" target="_blank">Open Direct Hire Module</a>
              </div>
            </div>
            
            <!-- Step 3: Gov-to-Gov -->
            <div class="wizard-step" data-step="3" style="display: none;">
              <div class="text-center mb-4">
                <div class="display-4 text-primary"><i class="fa fa-globe"></i></div>
                <h3>Government-to-Government Module</h3>
                <p class="lead">Managing worker deployments under bilateral agreements between countries.</p>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="card mb-3">
                    <div class="card-body">
                      <h5><i class="fa fa-clipboard-list text-primary"></i> Record Management</h5>
                      <p>Create and manage Gov-to-Gov worker records with complete deployment details.</p>
                      <a href="gov_to_gov.php" class="btn btn-sm btn-outline-primary" target="_blank">Open Gov-to-Gov</a>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card mb-3">
                    <div class="card-body">
                      <h5><i class="fa fa-check-double text-success"></i> Approval Process</h5>
                      <p>Submit records for approval through the streamlined approval workflow.</p>
                      <a href="gov_to_gov_approvals.php" class="btn btn-sm btn-outline-success" target="_blank">View Approvals</a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="alert alert-info">
                <i class="fa fa-lightbulb"></i> <strong>Tip:</strong> Use the remarks field to add important notes about the deployment that approvers should know.
              </div>
            </div>
            
            <!-- Step 4: Balik Manggagawa -->
            <div class="wizard-step" data-step="4" style="display: none;">
              <div class="text-center mb-4">
                <div class="display-4 text-primary"><i class="fa fa-plane"></i></div>
                <h3>Balik Manggagawa Module</h3>
                <p class="lead">Processing returning Filipino workers who are going back to the same employer.</p>
              </div>
              <div class="card mb-3">
                <div class="card-body">
                  <h5 class="card-title">Workflow Steps</h5>
                  <div class="row">
                    <div class="col-md-3 text-center mb-3">
                      <div class="bg-light p-3 rounded">
                        <i class="fa fa-user-edit fa-2x text-primary mb-2"></i>
                        <p class="mb-0">1. Record Details</p>
                      </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                      <div class="bg-light p-3 rounded">
                        <i class="fa fa-file-upload fa-2x text-primary mb-2"></i>
                        <p class="mb-0">2. Upload Docs</p>
                      </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                      <div class="bg-light p-3 rounded">
                        <i class="fa fa-clipboard-check fa-2x text-primary mb-2"></i>
                        <p class="mb-0">3. Verify Info</p>
                      </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                      <div class="bg-light p-3 rounded">
                        <i class="fa fa-certificate fa-2x text-success mb-2"></i>
                        <p class="mb-0">4. Issue OWWA</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="alert alert-success">
                <i class="fa fa-link"></i> <strong>Quick Access:</strong> <a href="balik_manggagawa.php" class="alert-link" target="_blank">Open Balik Manggagawa Module</a>
              </div>
            </div>
            
            <!-- Step 5: Approvals -->
            <div class="wizard-step" data-step="5" style="display: none;">
              <div class="text-center mb-4">
                <div class="display-4 text-primary"><i class="fa fa-check-circle"></i></div>
                <h3>Approval Workflows</h3>
                <p class="lead">Managing and processing approvals across different modules.</p>
              </div>
              <div class="card mb-3">
                <div class="card-body">
                  <h5 class="card-title">Approval Features</h5>
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex align-items-center">
                      <i class="fa fa-bell text-warning mr-3"></i> Notification alerts when new items need approval
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                      <i class="fa fa-eye text-primary mr-3"></i> Detailed view of submission information before deciding
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                      <i class="fa fa-comment text-info mr-3"></i> Add remarks when approving or rejecting submissions
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                      <i class="fa fa-history text-secondary mr-3"></i> Review previous approval decisions and audit trails
                    </li>
                  </ul>
                </div>
              </div>
              <div class="alert alert-info">
                <i class="fa fa-lightbulb"></i> <strong>Tip for Regional Directors:</strong> Check your notifications regularly for pending approvals to ensure timely processing of worker documents.
              </div>
            </div>
          </div>
          
          <!-- Wizard Footer -->
          <div class="wizard-footer d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secondary" id="wizardPrevBtn" disabled>
              <i class="fa fa-arrow-left"></i> Previous
            </button>
            <button type="button" class="btn btn-primary" id="wizardNextBtn">
              Next <i class="fa fa-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Wizard Guide Javascript -->
  <script>
  // Initialize wizard functionality after document is fully loaded
  document.addEventListener('DOMContentLoaded', function() {
    // Interactive Wizard Guide functionality
    let currentWizardStep = 1;
    const totalWizardSteps = 5;
    
    // Update wizard progress bar and navigation
    function updateWizardProgress() {
      try {
        // Update progress bar
        const progressPercent = ((currentWizardStep - 1) / (totalWizardSteps - 1)) * 100;
        const progressBar = document.querySelector('.wizard-navigation .progress-bar');
        if (progressBar) {
          progressBar.style.width = progressPercent + '%';
          progressBar.setAttribute('aria-valuenow', progressPercent);
        }
        
        // Update navigation pills
        document.querySelectorAll('.wizard-nav .nav-link').forEach(link => {
          link.classList.remove('active');
          if (parseInt(link.getAttribute('data-step')) === currentWizardStep) {
            link.classList.add('active');
          }
        });
        
        // Show current step, hide others
        document.querySelectorAll('.wizard-step').forEach(step => {
          step.style.display = 'none';
          if (parseInt(step.getAttribute('data-step')) === currentWizardStep) {
            step.style.display = 'block';
          }
        });
        
        // Update buttons
        const prevBtn = document.getElementById('wizardPrevBtn');
        const nextBtn = document.getElementById('wizardNextBtn');
        
        if (prevBtn && nextBtn) {
          prevBtn.disabled = currentWizardStep === 1;
          
          if (currentWizardStep === totalWizardSteps) {
            nextBtn.innerHTML = '<i class="fa fa-check"></i> Finish';
            nextBtn.classList.remove('btn-primary');
            nextBtn.classList.add('btn-success');
          } else {
            nextBtn.innerHTML = 'Next <i class="fa fa-arrow-right"></i>';
            nextBtn.classList.remove('btn-success');
            nextBtn.classList.add('btn-primary');
          }
        }
      } catch (e) {
        console.error('Error updating wizard progress:', e);
      }
    }
    
    // Initialize wizard elements
    function initializeWizard() {
      try {
        // Next button click handler
        const nextBtn = document.getElementById('wizardNextBtn');
        if (nextBtn) {
          nextBtn.addEventListener('click', function() {
            if (currentWizardStep < totalWizardSteps) {
              currentWizardStep++;
              updateWizardProgress();
            } else {
              // Last step - close the modal
              if (typeof jQuery !== 'undefined') {
                jQuery('#wizardGuideModal').modal('hide');
              } else {
                // Fallback to vanilla JS if jQuery is not available
                const modal = document.getElementById('wizardGuideModal');
                if (modal) {
                  modal.classList.remove('show');
                  modal.style.display = 'none';
                  document.body.classList.remove('modal-open');
                  
                  // Remove backdrop
                  const backdrop = document.querySelector('.modal-backdrop');
                  if (backdrop) {
                    backdrop.parentNode.removeChild(backdrop);
                  }
                }
              }
            }
          });
        }
        
        // Previous button click handler
        const prevBtn = document.getElementById('wizardPrevBtn');
        if (prevBtn) {
          prevBtn.addEventListener('click', function() {
            if (currentWizardStep > 1) {
              currentWizardStep--;
              updateWizardProgress();
            }
          });
        }
        
        // Navigation pill click handlers
        document.querySelectorAll('.wizard-nav .nav-link').forEach(link => {
          link.addEventListener('click', function() {
            currentWizardStep = parseInt(this.getAttribute('data-step'));
            updateWizardProgress();
          });
        });
        
        // Wizard Guide Button Event Handler
        const wizardBtn = document.getElementById('wizardGuideButton');
        if (wizardBtn) {
          wizardBtn.addEventListener('click', function() {
            // Check if jQuery is available before using it
            if (typeof jQuery !== 'undefined') {
              jQuery('#wizardGuideModal').modal('show');
            } else {
              // Fallback to vanilla JS if jQuery is not available
              const modal = document.getElementById('wizardGuideModal');
              if (modal) {
                modal.classList.add('show');
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
                
                // Add backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
              }
            }
            
            // Reset wizard to first step when opened
            currentWizardStep = 1;
            updateWizardProgress();
          });
        }
      } catch (e) {
        console.error('Error initializing wizard:', e);
      }
    }
    
    // Initialize the wizard
    initializeWizard();
    
    // Add close button functionality to the modal
    const closeBtn = document.querySelector('.modal-header .close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function() {
        if (typeof jQuery !== 'undefined') {
          jQuery('#wizardGuideModal').modal('hide');
        } else {
          // Fallback to vanilla JS if jQuery is not available
          const modal = document.getElementById('wizardGuideModal');
          if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            // Remove backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
              backdrop.parentNode.removeChild(backdrop);
            }
          }
        }
      });
    }
  });
  </script>

  <!-- Direct intro.js implementation -->
  <script>
    // Directly add intro.js implementation
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize intro.js
      const intro = introJs();
      
      // Define steps directly here
      // Define custom positions to keep tooltips away from content
      const positionPendingApprovals = function(tooltip, item) {
        const rect = item.getBoundingClientRect();
        tooltip.style.top = rect.top + window.scrollY - 15 + 'px';
        tooltip.style.left = rect.right + window.scrollX + 20 + 'px';
        return tooltip;
      };
      
      intro.setOptions({
        steps: [
          {
            intro: '<h4>Welcome to MWPD Filing System</h4><p>This interactive guide will walk you through the main features of the Migrant Workers Protection Division Filing System. We will highlight key components you need to work efficiently in the system.</p>'
          },
          {
            element: document.querySelector('.sidebar'),
            intro: '<h4>Navigation Menu</h4><p>Access all system modules through this sidebar menu. Click on any item to navigate to that section.</p>',
            position: 'right'
          },
          {
            element: document.querySelector('.quick-add'),
            intro: '<h4>Quick Add</h4><p>Quickly add new records without navigating to specific modules.</p>',
            position: 'bottom'
          },
          {
            element: document.querySelector('#notificationIcon'),
            intro: '<h4>Notifications Center</h4><p>View all system notifications here. The badge shows how many unread notifications you have.</p>',
            position: 'bottom'
          },
          {
            element: document.querySelector('.user-profile'),
            intro: '<h4>User Profile</h4><p>Access your profile settings, change password, or log out from the system.</p>',
            position: 'bottom'
          },
          {
            element: document.querySelector('.box-total-records'),
            intro: '<h4>Total Records</h4><p>View all your system records at a glance. This section shows counts for Direct Hire, Balik Manggagawa, Gov-to-Gov, and Job Fairs records with their status indicators.</p>',
            position: 'bottom'
          },
          {
            element: document.querySelector('#pendingApprovalsBox'),
            intro: '<h4>Pending Approvals</h4><p>Monitor applications awaiting approval from Regional Directors.</p>',
            position: 'right',
            tooltipClass: 'pending-approvals-tooltip'
          },
          {
            element: document.querySelector('#calendarBox'),
            intro: '<h4>Calendar View</h4><p>View scheduled job fairs and important events for the month.</p>',
            position: 'left'
          },
          {
            element: document.querySelector('.recent-activity'),
            intro: '<h4>Recent System Activity</h4><p>Track the latest activities and updates in the system.</p>',
            position: 'top'
          },
          {
            element: document.querySelector('.job-fairs-this-month'),
            intro: '<h4>This Month\'s Job Fairs</h4><p>Quick overview of all job fairs scheduled for the current month.</p>',
            position: 'top'
          },
          {
            element: document.querySelector('.floating-action-menu'),
            intro: '<h4>Quick Access Menu</h4><p>Use this floating menu for quick access to common actions like this interactive guide, blacklist checking, and generating reports.</p>',
            position: 'right',
            tooltipClass: 'quick-access-tooltip'
          }
        ],
        showBullets: true,
        showProgress: true,
        exitOnOverlayClick: false,
        tooltipClass: 'intro-tooltip',
        showStepNumbers: false,
        skipLabel: 'Skip Tour',
        showBullets: true,
        showProgress: true,
        hidePrev: false,
        hideNext: false,
        hideSkip: false,
        showButtons: true,
        disableInteraction: false,
        doneLabel: 'Done',
        nextLabel: 'Next →',
        prevLabel: '← Back'
      });
      
      // Make intro globally available
      window.dashboardTour = intro;
      
      // Add event listener to wizard guide button
      const wizardGuideButton = document.getElementById('wizardGuideButton');
      if (wizardGuideButton) {
        // Remove existing event listeners
        const newWizardButton = wizardGuideButton.cloneNode(true);
        wizardGuideButton.parentNode.replaceChild(newWizardButton, wizardGuideButton);
        
        // Add click event
        newWizardButton.addEventListener('click', function(e) {
          e.preventDefault();
          // Start the tour
          window.dashboardTour.start();
        });
      }
      
      // Auto-start tour for first-time visitors
      if (!localStorage.getItem('mwpdTourShown')) {
        setTimeout(() => {
          window.dashboardTour.start();
          localStorage.setItem('mwpdTourShown', 'true');
        }, 1000);
      }
    });
  </script>


<!-- Dashboard scrolling now handled by external JS file -->

</body>
</html>