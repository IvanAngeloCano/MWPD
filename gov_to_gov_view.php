<?php
include 'session.php';
require_once 'connection.php';

// Handle notification marking as read
include 'read_notification_handler.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: gov_to_gov.php?error=No record ID specified');
    exit();
}

$record_id = (int)$_GET['id'];

try {
    // Get record details
    $stmt = $pdo->prepare("SELECT * FROM gov_to_gov WHERE g2g = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        throw new Exception("Record not found");
    }
    // TODO: If you have document attachments, fetch them here
    $documents = [];
} catch (Exception $e) {
    header('Location: gov_to_gov.php?error=' . urlencode($e->getMessage()));
    exit();
}

$pageTitle = "View Record - Gov-to-Gov";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="content-wrapper">
    <?php include '_header.php'; ?>
    <main class="main-content">
      <div class="record-view-wrapper">
        <!-- Record Header -->
        <div class="record-header">
          <div class="record-title">
            <h2><?= htmlspecialchars($record['last_name']) ?>, <?= htmlspecialchars($record['first_name']) ?> <?= htmlspecialchars($record['middle_name']) ?></h2>
            <div class="record-subtitle">
              <span class="record-type">Gov-to-Gov</span>
            </div>
          </div>
          <div class="record-actions">
            <a href="gov_to_gov_edit.php?id=<?= $record['g2g'] ?>" class="btn btn-primary"><i class="fa fa-edit"></i> Edit</a>
            <a href="gov_to_gov.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to List</a>
          </div>
        </div>
        <!-- Record Details -->
        <div class="record-details">
          <div class="record-section">
            <h3>Basic Information</h3>
            <div class="detail-grid">
              <div class="detail-item"><div class="detail-label">Last Name</div><div class="detail-value"><?= htmlspecialchars($record['last_name']) ?></div></div>
              <div class="detail-item"><div class="detail-label">First Name</div><div class="detail-value"><?= htmlspecialchars($record['first_name']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Middle Name</div><div class="detail-value"><?= htmlspecialchars($record['middle_name']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Sex</div><div class="detail-value"><?= htmlspecialchars($record['sex']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Birth Date</div><div class="detail-value"><?= htmlspecialchars($record['birth_date']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Age</div><div class="detail-value"><?= htmlspecialchars($record['age']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Height</div><div class="detail-value"><?= htmlspecialchars($record['height']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Weight</div><div class="detail-value"><?= htmlspecialchars($record['weight']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Educational Attainment</div><div class="detail-value"><?= htmlspecialchars($record['educational_attainment']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Present Address</div><div class="detail-value"><?= htmlspecialchars($record['present_address']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Email Address</div><div class="detail-value"><?= htmlspecialchars($record['email_address']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Contact Number</div><div class="detail-value"><?= htmlspecialchars($record['contact_number']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Passport Number</div><div class="detail-value"><?= htmlspecialchars($record['passport_number']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Passport Validity</div><div class="detail-value"><?= htmlspecialchars($record['passport_validity']) ?></div></div>
              <div class="detail-item"><div class="detail-label">ID Presented</div><div class="detail-value"><?= htmlspecialchars($record['id_presented']) ?></div></div>
              <div class="detail-item"><div class="detail-label">ID Number</div><div class="detail-value"><?= htmlspecialchars($record['id_number']) ?></div></div>
              <div class="detail-item"><div class="detail-label">With Job Experience</div><div class="detail-value"><?= htmlspecialchars($record['with_job_experience']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Company Name/Year Started–Ended</div><div class="detail-value"><?= htmlspecialchars($record['company_name_year_started_ended']) ?></div></div>
              <div class="detail-item"><div class="detail-label">With Other Experience</div><div class="detail-value"><?= htmlspecialchars($record['with_job_experience_aside_from']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Name/Company/Year Started–Ended</div><div class="detail-value"><?= htmlspecialchars($record['name_company_year_started_ended']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Remarks</div><div class="detail-value"><?= htmlspecialchars($record['remarks']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Date Received by Region</div><div class="detail-value"><?= htmlspecialchars($record['date_received_by_region']) ?></div></div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<style>
  .record-view-wrapper {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    margin: 30px auto;
    max-width: 1000px;
  }
  .record-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
    background-color: #f8f9fa;
  }
  .record-title h2 { margin: 0 0 8px 0; font-size: 24px; }
  .record-subtitle { display: flex; gap: 15px; color: #666; }
  .record-type { font-weight: 500; }
  .record-actions { display: flex; gap: 10px; }
  .record-details { padding: 30px 30px 20px 30px; }
  .record-section { margin-bottom: 30px; }
  .record-section h3 { font-size: 18px; margin: 0 0 15px 0; padding-bottom: 8px; border-bottom: 1px solid #eee; }
  .detail-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px 15px; }
  .detail-item { display: flex; flex-direction: column; gap: 5px; }
  .detail-label { font-size: 12px; color: #666; font-weight: 500; }
  .detail-value { font-size: 15px; }
  .btn { padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
  .btn-primary { background-color: #007bff; border: 1px solid #007bff; color: white; }
  .btn-secondary { background-color: #6c757d; border: 1px solid #6c757d; color: white; }
</style>
