<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: gov_to_gov.php?error=No record ID specified');
    exit();
}

$record_id = (int)$_GET['id'];

// Fetch record
try {
    $stmt = $pdo->prepare("SELECT * FROM gov_to_gov WHERE g2g = ?");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        throw new Exception("Record not found");
    }
} catch (Exception $e) {
    header('Location: gov_to_gov.php?error=' . urlencode($e->getMessage()));
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'last_name', 'first_name', 'middle_name', 'sex', 'birth_date', 'age', 'height', 'weight',
        'educational_attainment', 'present_address', 'email_address', 'contact_number',
        'passport_number', 'passport_validity', 'id_presented', 'id_number',
        'with_job_experience', 'company_name_year_started_ended',
        'with_job_experience_aside_from', 'name_company_year_started_ended',
        'remarks', 'date_received_by_region'
    ];
    $update_sql = "UPDATE gov_to_gov SET ";
    $params = [];
    foreach ($fields as $i => $field) {
        $update_sql .= "$field = ?" . ($i < count($fields) - 1 ? ", " : "");
        $params[] = $_POST[$field];
    }
    $update_sql .= " WHERE g2g = ?";
    $params[] = $record_id;
    try {
        $stmt = $pdo->prepare($update_sql);
        $stmt->execute($params);
        header('Location: gov_to_gov_view.php?id=' . $record_id . '&success=Record updated successfully');
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = "Edit Record - Gov-to-Gov";
include '_head.php';
?>
<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="content-wrapper">
    <?php include '_header.php'; ?>
    <main class="main-content">
      <div class="record-edit-wrapper">
        <form method="post" class="edit-form">
          <h2>Edit Gov-to-Gov Record</h2>
          <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
          <div class="form-grid">
            <label>Last Name<input type="text" name="last_name" value="<?= htmlspecialchars($record['last_name']) ?>" required></label>
            <label>First Name<input type="text" name="first_name" value="<?= htmlspecialchars($record['first_name']) ?>" required></label>
            <label>Middle Name<input type="text" name="middle_name" value="<?= htmlspecialchars($record['middle_name']) ?>"></label>
            <label>Sex<input type="text" name="sex" value="<?= htmlspecialchars($record['sex']) ?>"></label>
            <label>Birth Date<input type="date" name="birth_date" value="<?= htmlspecialchars($record['birth_date']) ?>"></label>
            <label>Age<input type="number" name="age" value="<?= htmlspecialchars($record['age']) ?>"></label>
            <label>Height<input type="text" name="height" value="<?= htmlspecialchars($record['height']) ?>"></label>
            <label>Weight<input type="text" name="weight" value="<?= htmlspecialchars($record['weight']) ?>"></label>
            <label>Educational Attainment<input type="text" name="educational_attainment" value="<?= htmlspecialchars($record['educational_attainment']) ?>"></label>
            <label>Present Address<input type="text" name="present_address" value="<?= htmlspecialchars($record['present_address']) ?>"></label>
            <label>Email Address<input type="email" name="email_address" value="<?= htmlspecialchars($record['email_address']) ?>"></label>
            <label>Contact Number<input type="text" name="contact_number" value="<?= htmlspecialchars($record['contact_number']) ?>"></label>
            <label>Passport Number<input type="text" name="passport_number" value="<?= htmlspecialchars($record['passport_number']) ?>"></label>
            <label>Passport Validity<input type="date" name="passport_validity" value="<?= htmlspecialchars($record['passport_validity']) ?>"></label>
            <label>ID Presented<input type="text" name="id_presented" value="<?= htmlspecialchars($record['id_presented']) ?>"></label>
            <label>ID Number<input type="text" name="id_number" value="<?= htmlspecialchars($record['id_number']) ?>"></label>
            <label>With Job Experience<input type="text" name="with_job_experience" value="<?= htmlspecialchars($record['with_job_experience']) ?>"></label>
            <label>Company Name/Year Started–Ended<input type="text" name="company_name_year_started_ended" value="<?= htmlspecialchars($record['company_name_year_started_ended']) ?>"></label>
            <label>With Other Experience<input type="text" name="with_job_experience_aside_from" value="<?= htmlspecialchars($record['with_job_experience_aside_from']) ?>"></label>
            <label>Name/Company/Year Started–Ended<input type="text" name="name_company_year_started_ended" value="<?= htmlspecialchars($record['name_company_year_started_ended']) ?>"></label>
            <label>Remarks<input type="text" name="remarks" value="<?= htmlspecialchars($record['remarks']) ?>"></label>
            <label>Date Received by Region<input type="date" name="date_received_by_region" value="<?= htmlspecialchars($record['date_received_by_region']) ?>"></label>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="gov_to_gov_view.php?id=<?= $record_id ?>" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
<style>
  .record-edit-wrapper {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    margin: 30px auto;
    max-width: 1000px;
    padding: 30px 40px;
  }
  .edit-form h2 { font-size: 24px; margin-bottom: 18px; }
  .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px 15px; }
  .form-grid label { display: flex; flex-direction: column; gap: 5px; font-size: 14px; color: #444; }
  .form-grid input { padding: 8px 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 6px; }
  .form-actions { margin-top: 30px; display: flex; gap: 15px; }
  .btn { padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; }
  .btn-primary { background-color: #007bff; border: 1px solid #007bff; color: white; }
  .btn-secondary { background-color: #6c757d; border: 1px solid #6c757d; color: white; }
</style>
