<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Add New Record - Gov-to-Gov";
include '_head.php';
?>
<style>
<?php include('assets/css/style.css'); ?>
/* --- Start: Direct Hire Add Page Styles for Consistent Look --- */
.form-row {
  display: flex;
  flex-wrap: wrap;
  margin-bottom: 18px;
  gap: 18px;
}
.form-group {
  flex: 1 1 220px;
  min-width: 180px;
  display: flex;
  flex-direction: column;
}
.form-group label {
  font-weight: 500;
  margin-bottom: 4px;
}
.form-group input,
.form-group select,
.form-group textarea {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 15px;
  margin-bottom: 2px;
}
.form-group textarea {
  min-height: 70px;
  resize: vertical;
}
.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 22px;
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  text-decoration: none;
}
.btn-primary {
  background-color: #007bff;
  border: 1px solid #007bff;
  color: white;
}
.btn-cancel {
  background-color: transparent;
  border: 1px solid #dc3545;
  color: #dc3545;
}
@media (max-width: 700px) {
  .form-row {
    flex-direction: column;
    gap: 10px;
  }
}
/* --- End: Direct Hire Add Page Styles for Consistent Look --- */
</style>
<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>
  <div class="content-wrapper">
    <?php include '_header.php'; ?>
    <main class="main-content">
      <div class="container">
        <!-- Removed the heading as requested -->
        <form action="save_applicant.php" method="POST" enctype="multipart/form-data" class="record-form">
          <div class="form-row">
            <div class="form-group">
              <label>Last Name</label>
              <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
              <label>First Name</label>
              <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input type="text" name="middle_name">
            </div>
            <div class="form-group">
              <label>Sex</label>
              <select name="sex">
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Birth Date</label>
              <input type="date" name="birth_date">
            </div>
            <div class="form-group">
              <label>Age</label>
              <input type="number" name="age">
            </div>
            <div class="form-group">
              <label>Height</label>
              <input type="text" name="height">
            </div>
            <div class="form-group">
              <label>Weight</label>
              <input type="text" name="weight">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Educational Attainment</label>
              <input type="text" name="educational_attainment">
            </div>
            <div class="form-group">
              <label>Present Address</label>
              <input type="text" name="present_address">
            </div>
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" name="email_address">
            </div>
            <div class="form-group">
              <label>Contact Number</label>
              <input type="text" name="contact_number">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Passport Number</label>
              <input type="text" name="passport_number">
            </div>
            <div class="form-group">
              <label>Passport Validity</label>
              <input type="date" name="passport_validity">
            </div>
            <div class="form-group">
              <label>ID Presented</label>
              <input type="text" name="id_presented">
            </div>
            <div class="form-group">
              <label>ID Number</label>
              <input type="text" name="id_number">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>With Job Experience</label>
              <select name="with_job_experience">
                <option>Yes</option>
                <option>No</option>
              </select>
            </div>
            <div class="form-group">
              <label>Company Name/Year Started–Ended</label>
              <input type="text" name="company_name_year_started_ended">
            </div>
            <div class="form-group">
              <label>With Other Experience</label>
              <select name="with_job_experience_aside_from">
                <option>Yes</option>
                <option>No</option>
              </select>
            </div>
            <div class="form-group">
              <label>Name/Company/Year Started–Ended</label>
              <input type="text" name="name_company_year_started_ended">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Remarks</label>
              <input type="text" name="remarks">
            </div>
            <div class="form-group">
              <label>Date Received by Region</label>
              <input type="date" name="date_received_by_region">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group" style="flex: 1 1 100%;">
              <label>Upload Documents</label>
              <input type="file" name="documents[]" multiple>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="gov_to_gov.php" class="btn btn-cancel">Cancel</a>
          </div>
        </form>
      </div>
    </main>
  </div>
</div>
