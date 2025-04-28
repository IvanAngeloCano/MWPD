<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Balik Manggagawa - MWPD Filing System";
include '_head.php';
?>

<style>
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
    font-size: 14px;
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
    max-width: 250px;
  }
  #searchInput {
    height: 35px;
    width: 300px;
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

  /* Scrollable Table Wrapper */
  .scroll-table-wrapper {
    overflow-x: auto;
    width: 100%;
  }
  .scroll-table-wrapper table {
    white-space: nowrap;
    min-width: 100%;
    transition: min-width 0.3s ease;
  }
  /* When showing all columns */
  .scroll-table-wrapper table.show-all {
    min-width: 1800px;
  }

  /* Hide optional columns initially */
  .optional-column {
    display: none;
  }

  /* Popup Styling */
  #overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 500;
  }
  #popupForm {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    max-width: 90%;
    height: 80vh;
    background: #fff;
    padding: 20px;
    overflow-y: auto;
    border-radius: 15px;
    box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.25);
    z-index: 1000;
    flex-direction: column;
  }
  #popupForm h3 {
    text-align: center;
    margin-bottom: 20px;
  }
  #popupForm input, #popupForm select {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
  }
  #popupForm button {
    width: 48%;
    padding: 10px;
    margin-top: 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  #popupForm .save-btn {
    background-color: #4CAF50;
    color: white;
  }
  #popupForm .cancel-btn {
    background-color: #f44336;
    color: white;
  }
  .form-buttons {
    display: flex;
    justify-content: space-between;
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

        <input type="text" id="searchInput" placeholder="Search...">
        <button onclick="showPopup()" style="margin-top: 10px; padding: 8px 16px; border:none; border-radius:5px; background-color:#5a9bf9; color:white; cursor:pointer;">Add New Record</button>

        <div class="scroll-table-wrapper">
          <table id="dataTable">
            <thead>
              <tr>
                <th>O.R NO.</th>
                <th>LAST NAME</th>
                <th>GIVEN NAME</th>
                <th>MIDDLE NAME</th>
                <th>SEX</th>
                <th>ADDRESS</th>
                <th>DESTINATION</th>
                <th class="optional-column">COUNTER NO. (EVAL)</th>
                <th class="optional-column">TYPE</th>
                <th class="optional-column">TIME IN (EVAL)</th>
                <th class="optional-column">TIME OUT (EVAL)</th>
                <th class="optional-column">TOTAL PCT (EVAL)</th>
                <th class="optional-column">COUNTER NO. (PAYMENT)</th>
                <th class="optional-column">TIME IN (PAYMENT)</th>
                <th class="optional-column">TIME OUT (PAYMENT)</th>
                <th class="optional-column">TOTAL PCT (PAYMENT)</th>
                <th>REMARKS</th>
              </tr>
            </thead>
            <tbody>
              <?php
              try {
                $stmt = $pdo->query("SELECT * FROM ofw_transaction ORDER BY or_no");
                if ($stmt->rowCount() > 0) {
                  while ($row = $stmt->fetch()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['or_no']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['given_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['destination']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['eval_counter_no']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['eval_type']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['eval_time_in']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['eval_time_out']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['eval_total_pct']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['pay_counter_no']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['pay_time_in']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['pay_time_out']) . "</td>";
                    echo "<td class='optional-column'>" . htmlspecialchars($row['pay_total_pct']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='17' class='text-center'>No data found.</td></tr>";
                }
              } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
              }
              ?>
            </tbody>
          </table>
        </div>

        <!-- Show All Button -->
        <div style="margin-top: 10px; text-align: right;">
          <button id="showAllBtn" style="padding: 8px 16px; border:none; border-radius:5px; background-color:#5a9bf9; color:white; cursor:pointer;">Show All</button>
        </div>

        <!-- Popup Form -->
        <div id="overlay"></div>
        <div id="popupForm">
          <h3>Add New Record</h3>
          <form id="addForm">
            <input type="text" name="last_name" placeholder="Last Name" required><br>
            <input type="text" name="given_name" placeholder="Given Name" required><br>
            <input type="text" name="middle_name" placeholder="Middle Name"><br>
            <select name="sex" required>
              <option value="">Select Sex</option>
              <option value="M">Male</option>
              <option value="F">Female</option>
            </select><br>
            <input type="text" name="address" placeholder="Address" required><br>
            <input type="text" name="destination" placeholder="Destination" required><br>
            <input type="text" name="eval_counter_no" placeholder="Counter No. (Eval)" required><br>
            <input type="text" name="eval_type" placeholder="Type" required><br>
            <input type="time" name="eval_time_in" required><br>
            <input type="time" name="eval_time_out" required><br>
            <input type="number" name="eval_total_pct" placeholder="Total PCT (Eval)" required><br>
            <input type="text" name="pay_counter_no" placeholder="Counter No. (Payment)" required><br>
            <input type="time" name="pay_time_in" required><br>
            <input type="time" name="pay_time_out" required><br>
            <input type="number" name="pay_total_pct" placeholder="Total PCT (Payment)" required><br>
            <input type="text" name="remarks" placeholder="Remarks"><br>

            <div class="form-buttons">
              <button type="submit" class="save-btn">Save</button>
              <button type="button" onclick="hidePopup()" class="cancel-btn">Cancel</button>
            </div>
          </form>
        </div>

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

  function showPopup() {
    document.getElementById('popupForm').style.display = 'flex';
    document.getElementById('overlay').style.display = 'block';
  }

  function hidePopup() {
    document.getElementById('popupForm').style.display = 'none';
    document.getElementById('overlay').style.display = 'none';
  }

  document.getElementById('showAllBtn').addEventListener('click', function() {
    document.querySelectorAll('th.optional-column, td.optional-column').forEach(cell => {
      cell.style.display = 'table-cell';
    });
    document.querySelector('#dataTable').classList.add('show-all');
    this.style.display = 'none';
  });

  document.getElementById('addForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('add_record.php', {
      method: 'POST',
      body: formData
    }).then(response => response.json())
      .then(data => {
        if (data.success) {
          const table = document.getElementById('dataTable').querySelector('tbody');
          const row = table.insertRow();
          row.innerHTML = `
            <td>${data.record.or_no}</td>
            <td>${data.record.last_name}</td>
            <td>${data.record.given_name}</td>
            <td>${data.record.middle_name}</td>
            <td>${data.record.sex}</td>
            <td>${data.record.address}</td>
            <td>${data.record.destination}</td>
            <td class="optional-column">${data.record.eval_counter_no}</td>
            <td class="optional-column">${data.record.eval_type}</td>
            <td class="optional-column">${data.record.eval_time_in}</td>
            <td class="optional-column">${data.record.eval_time_out}</td>
            <td class="optional-column">${data.record.eval_total_pct}</td>
            <td class="optional-column">${data.record.pay_counter_no}</td>
            <td class="optional-column">${data.record.pay_time_in}</td>
            <td class="optional-column">${data.record.pay_time_out}</td>
            <td class="optional-column">${data.record.pay_total_pct}</td>
            <td>${data.record.remarks}</td>
          `;
          hidePopup();
          document.getElementById('addForm').reset();
        } else {
          alert('Error saving record.');
        }
      }).catch(error => {
        console.error('Error:', error);
      });
  });
</script>
