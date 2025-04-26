<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Gov to Gov - MWPD Filing System";
include '_head.php';
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
    font-family: 'Segoe UI', sans-serif;
    background-color: #fff;
  }

  th, td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
  }

  th {
    background-color: #f2f2f2;
    font-weight: bold;
    color: #555;
  }

  td {
    color: #444;
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

  table td, table th {
    word-wrap: break-word;
    max-width: 150px;
  }

  table td {
    font-size: 14px;
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
      <div class="container">

        <div id="popupMemoForm" class="popupForm">
          <form action="generate_memo.php" method="POST" style="margin-bottom: 20px;">
            <h3>Generate Memo</h3>
            <label for="employer">Employer Name:</label>
            <input type="text" id="employer" name="employer" required>
            <label for="memo_date">Date:</label>
            <input type="date" id="memo_date" name="memo_date" value="<?= date('Y-m-d') ?>" required>
            <button type="submit">Generate Memo</button>
            <button type="button" onclick="document.getElementById('popupMemoForm').style.display='none'">Cancel</button>
          </form>
        </div>

        <div id="popupAppForm" class="popupForm">
          <form action="save_applicant.php" method="POST">
            <h3>Add New Applicant</h3>
            <label>Last Name:</label><input type="text" name="last_name" required>
            <label>First Name:</label><input type="text" name="first_name" required>
            <label>Middle Initial:</label><input type="text" name="middle_name">
            <label>Sex:</label>
            <select name="sex">
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <label>Birth Date:</label><input type="date" name="birth_date">
            <label>Age:</label><input type="number" name="age">
            <label>Height:</label><input type="text" name="height">
            <label>Weight:</label><input type="text" name="weight">
            <label>Educational Attainment:</label><input type="text" name="educational_attainment">
            <label>Present Address:</label><input type="text" name="present_address">
            <label>Email Address:</label><input type="email" name="email_address">
            <label>Contact Number:</label><input type="text" name="contact_number">
            <label>Passport Number:</label><input type="text" name="passport_number">
            <label>Passport Validity:</label><input type="date" name="passport_validity">
            <label>ID Presented:</label><input type="text" name="id_presented">
            <label>ID Number:</label><input type="text" name="id_number">
            <label>With Job Experience:</label>
            <select name="with_job_experience"><option>Yes</option><option>No</option></select>
            <label>Company Name/Year Started–Ended:</label><input type="text" name="company_name_year_started_ended">
            <label>With Other Experience:</label>
            <select name="with_job_experience_aside_from"><option>Yes</option><option>No</option></select>
            <label>Name/Company/Year Started–Ended:</label><input type="text" name="name_company_year_started_ended">
            <label>Remarks:</label><input type="text" name="remarks">
            <label>Date Received by Region:</label><input type="date" name="date_received_by_region">
            <button type="submit">Save</button>
            <button type="button" onclick="document.getElementById('popupAppForm').style.display='none'">Cancel</button>
          </form>
        </div>

        <button onclick="document.getElementById('popupMemoForm').style.display='block'" class="custom-button create-memo"> <b> CREATE MEMO DOC </b> </button>
        <button onclick="document.getElementById('popupAppForm').style.display='block'" class="custom-button add-applicant"> <b>ADD </b></button>

        <input type="text" id="searchInput" placeholder="Search...">

        <table id="dataTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Last Name</th>
              <th>First Name</th>
              <th>M.I.</th>
              <th>Sex</th>
              <th>Birth Date</th>
              <th>Age</th>
              <th>Height</th>
              <th>Weight</th>
              <th>Educational Attainment</th>
              <th>Present Address</th>
              <th>Email Address</th>
              <th>Contact Number</th>
              <th>Passport Number</th>
              <th>Passport Validity</th>
              <th>ID Presented</th>
              <th>ID Number</th>
              <th>With Job Experience</th>
              <th>Company Name/Year Started–Ended</th>
              <th>With Other Experience</th>
              <th>Name/Company/Year Started–Ended</th>
              <th>Remarks</th>
              <th>Date Received by Region</th>
            </tr>
          </thead>
          <tbody>
            <?php
            try {
              $stmt = $pdo->query("SELECT * FROM gov_to_gov");
              if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch()) {
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($row['g2g']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['birth_date']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['age']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['height']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['weight']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['educational_attainment']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['present_address']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['email_address']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['contact_number']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['passport_number']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['passport_validity']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['id_presented']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['with_job_experience']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['company_name_year_started_ended']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['with_job_experience_aside_from']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['name_company_year_started_ended']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['date_received_by_region']) . "</td>";
                  echo "</tr>";
                }
              }
            } catch (PDOException $e) {
              echo "Error: " . $e->getMessage();
            }
            ?>
          </tbody>
        </table>

      </div>
    </main>
  </div>
</div>

<script>
  document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#dataTable tbody tr').forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
  });
</script>