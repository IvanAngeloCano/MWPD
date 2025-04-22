<?php
include 'session.php';
$pageTitle = "Dashboard - MWPD Filing System";
include '_head.php';
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
          <div class="bento-box box-total-records">
            <h2>Total Records</h2>
            <div class="record-cards">
              <!-- Card 1: Direct Hire -->
              <div class="record-card">

                <div class="card-text">
                  <span class="record-count">120</span>
                  <span class="record-label">Direct Hire</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-briefcase"></i>
                </div>
              </div>

              <!-- Card 2: Balik Manggagawa -->
              <div class="record-card">
                <div class="card-text">
                  <span class="record-count">85</span>
                  <span class="record-label">Balik Manggagawa</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-sign-in-alt"></i>
                </div>
              </div>

              <!-- Card 3: Gov-to-Gov -->
              <div class="record-card">
                <div class="card-text">
                  <span class="record-count">45</span>
                  <span class="record-label">Gov-to-Gov</span>
                </div>
                <div class="card-icon">
                  <i class="fa fa-university"></i>
                </div>
              </div>

              <!-- Card 4: Job Fairs -->
              <div class="record-card">
                <div class="card-text">
                  <span class="record-count">32</span>
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
            <h2>Pending Approvals</h2>
            <table>
              <thead>
                <tr>
                  <th>Process Type</th>
                  <th>Applicant Name</th>
                  <th>Status</th>
                  <th>Time</th>
                </tr>
              </thead>

              <!-- Limit to only 3 rows of data -->
              <tbody>
                <tr>
                  <td>Direct Hire</td>
                  <td>Maria Santos</td>
                  <td><span class="status-badge pending">Pending</span></td>
                  <td>2h ago</td>
                </tr>
                <tr>
                  <td>Gov-to-Gov</td>
                  <td>Juan Dela Cruz</td>
                  <td><span class="status-badge approved">Approved</span></td>
                  <td>1d ago</td>
                </tr>
                <tr>
                  <td>Balik Manggagawa</td>
                  <td>Ana Reyes</td>
                  <td><span class="status-badge declined">Declined</span></td>
                  <td>3h ago</td>
                </tr>
              </tbody>
            </table>
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


          <!-- Box 4: TBD Content -->
          <div class="bento-box box-tbd">
            <h2>To Be Decided</h2>
            <p>Maybe a stats graph, quick shortcuts, etc.</p>
            <p>Yung location kineme</p>
          </div>

          <!-- Box 5: Activity List -->
          <div class="bento-box box-activity-log">
            <h2>Activity</h2>
            <ul>
              <li>April 18 – Maria Santos submitted a form</li>
              <li>April 17 – Clearance approved for Juan Dela Cruz</li>
            </ul>
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