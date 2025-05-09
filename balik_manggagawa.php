<?php
include 'session.php';
<<<<<<< HEAD
=======
require_once 'connection.php';
>>>>>>> e676bef (Initial commit on updated_BM)
$pageTitle = "Balik Manggagawa - MWPD Filing System";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
<<<<<<< HEAD
    // Get current filename like 'dashboard-eme.php'
    $currentFile = basename($_SERVER['PHP_SELF']);

    // Remove the file extension
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);

    // Replace dashes with spaces
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', $fileWithoutExtension));

=======
    $currentFile = basename($_SERVER['PHP_SELF']);
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
    $pageTitle = ucwords(str_replace(['-', '_'], ' ', $fileWithoutExtension));
>>>>>>> e676bef (Initial commit on updated_BM)
    include '_header.php';
    ?>

    <main class="main-content">
<<<<<<< HEAD
      <!-- Your page content here -->
      <p>Lorem ipsum dolor sit, amet consectetur adipisicing elit. Unde quos vitae aspernatur, commodi illo laborum, voluptatum maiores totam ut suscipit rerum! Ducimus soluta architecto doloribus provident sed inventore delectus dignissimos labore hic reiciendis corrupti in natus perferendis reprehenderit repellat enim rem, impedit fugit placeat aliquid quod nam ratione voluptates odio? Incidunt nobis tempora molestiae deleniti reiciendis. Sed repellat quod aperiam, molestias excepturi impedit adipisci. Enim sapiente nemo saepe explicabo vero at facere perspiciatis ut similique nisi quibusdam fugit ea, impedit sunt, nobis eos incidunt. Quasi expedita placeat, ipsum perferendis, in fuga libero facilis impedit, vel itaque consequuntur. Assumenda distinctio beatae incidunt commodi aspernatur dolorum voluptatibus repellat totam provident sed quidem, reiciendis sapiente error obcaecati optio. Nesciunt distinctio odit beatae perspiciatis voluptates numquam reiciendis ea, eum doloribus mollitia, repellendus deserunt quo expedita ex, esse delectus at cumque! Sunt repellendus natus, soluta hic consectetur qui voluptas officiis nam. Ad distinctio harum magnam dolorem similique vero. Accusantium incidunt sint ex culpa et, adipisci veniam nam aliquam quisquam eligendi, ipsa similique aperiam sequi quam numquam praesentium illum quae quasi veritatis voluptatibus corrupti qui. Quae exercitationem iusto et. Voluptatem amet nesciunt molestias explicabo deserunt culpa consequatur, voluptatibus reprehenderit dicta corporis odio. Recusandae vitae iusto veritatis!</p>
    </main>
  </div>
</div>
=======

      <style>
        .date-today-group, .name-group, .info-group, .job-group, .employment-group {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: wrap;
}

.date-today-group h6,
.name-group h6,
.info-group h6,
.job-group h6,
.employment-group h6 {
  margin: 0;
  font-size: 16px;
  min-width: 120px;
  color: #333;
}

.name-group input,
.info-group select,
.info-group input,
.job-group input,
.employment-group input {
  flex: 1;
  min-width: 150px;
}

.form-buttons {
  margin-top: 20px;
}

        .name-group input {
          flex: 1; /* makes the inputs responsive */
          min-width: 150px; /* optional: prevents too small inputs */
        }

        .date-today-group {
          display: flex;
          align-items: center;
          gap: 10px; /* Space between label and input */
          margin-bottom: 10px;
        }
        .date-today-group h6 {
          margin: 0;
          font-size: 16px;
          color: #333;
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
          max-width: 150px;
        }
        .action-button {
          padding: 0.5rem 1rem;
          border: none;
          background-color: #007bff;
          color: white;
          border-radius: 6px;
          font-size: 1rem;
          cursor: pointer;
          margin: 5px;
        }
        .action-button:hover {
          background-color: #0056b3;
        }
        #searchInput {
          margin-bottom: 10px;
          padding: 8px;
          width: 250px;
          border: 1px solid #ccc;
          border-radius: 4px;
        }
        .show-button {
          padding: 0.5rem 1rem;
          background-color: #28a745;
          color: white;
          border-radius: 6px;
          font-size: 1rem;
          cursor: pointer;
          margin-top: 10px;
          margin-bottom: 20px;
          border: none;
        }
        .show-button:hover {
          background-color: #218838;
        }
        /* Popup Modal Styling */
        #addModal {
          display: none;
          position: fixed;
          top: 50%;
          left: 50%;
          transform: translate(-50%, -50%);
          background: #fff;
          padding: 20px;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
          z-index: 9999;
          width: 80%;
          max-width: 800px;
          overflow-y: auto;
          max-height: 90vh;

        }
        #addModal h3 {
          margin-bottom: 20px;
          font-size: 24px;
          color: #333;
        }
        #addModal input[type="text"],
        #addModal input[type="date"],
        #addModal select {
          width: 100%;
          padding: 12px;
          margin: 10px 0;
          border: 1px solid #ccc;
          border-radius: 4px;
          font-size: 14px;
        }
        #addModal button {
          padding: 10px 15px;
          background-color: #007bff;
          color: white;
          border: none;
          border-radius: 6px;
          font-size: 1rem;
          cursor: pointer;
          margin-right: 10px;
        }
        #addModal button:hover {
          background-color: #0056b3;
        }
        #addModal .cancel-button {
          background-color: #dc3545;
        }
        #addModal .cancel-button:hover {
          background-color: #c82333;
        }
      </style>

      <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for names..">

      <button class="show-button" onclick="openAddModal()">+ Add New Record</button>

      <table id="data-table">
        <thead>
          <tr>
            <th rowspan="2">GENERATE</th> <!-- Added Actions Column here -->
            <th rowspan="2">ACTIONS</th> <!-- Added Actions Column here -->
            <th rowspan="2">NO.</th>
            <th colspan="6">OFW'S INFORMATION</th>
            <th rowspan="2">REMARKS</th>
          </tr>
          <tr>
            <th>LAST NAME</th>
            <th>GIVEN NAME</th>
            <th>MIDDLE NAME</th>
            <th>SEX</th>
            <th>ADDRESS</th>
            <th>DESTINATION</th>
          </tr>
        </thead>
        <tbody>
          <?php
          try {
            $stmt = $pdo->query("SELECT bmid, last_name, given_name, middle_name, sex, address, destination, remarks FROM BM ORDER BY bmid");
            if ($stmt->rowCount() > 0) {
              while ($row = $stmt->fetch()) {
                echo "<tr>";
                
                echo "<td>
                        <button class='action-button' onclick='GenerateAC(" . $row['bmid'] . ")'>A</button>
                        <button class='action-button' onclick='GenerateNVC(" . $row['bmid'] . ")'>B</button>
                        <button class='action-button' onclick='GenerateCS(" . $row['bmid'] . ")'>C</button>
                        <button class='action-button' onclick='GenerateNCC(" . $row['bmid'] . ")'>D</button>
                        <button class='action-button' onclick='GenerateSP(" . $row['bmid'] . ")'>E</button>
                        <button class='action-button' onclick='GenerateWEC(" . $row['bmid'] . ")'>F</button>
                      </td>
                      <td>
                        <button class='action-button' onclick='updateRecord(" . $row['bmid'] . ")'>U</button>
                        <button class='action-button' onclick='deleteRecord(" . $row['bmid'] . ")'>D</button>
                      </td>";

                echo "<td>" . htmlspecialchars($row['bmid']) . "</td>";
                echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['given_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['sex']) . "</td>";
                echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                echo "<td>" . htmlspecialchars($row['destination']) . "</td>";
                echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
                echo "</tr>";
              }
            } else {
              echo "<tr><td colspan='9' class='text-center'>No data found.</td></tr>";
            }
          } catch (PDOException $e) {
            echo "<tr><td colspan='9'>Error: " . $e->getMessage() . "</td></tr>";
          }
          ?>
        </tbody>
      </table>

      <div id="editModal" style="display: none;">
  <h3>Edit Record</h3>
  <form id="editForm">
    <input type="hidden" name="bmid" id="editBmid"> <!-- Hidden ID -->
    <input type="date" id="editDateToday" name="dateToday" required>
    <div class="name-group">
      <input type="text" id="editLastName" name="last_name" placeholder="Last Name" required>
      <input type="text" id="editGivenName" name="given_name" placeholder="Given Name" required>
      <input type="text" id="editMiddleName" name="middle_name" placeholder="Middle Name" required>
    </div>
    <div class="info-group">
      <select name="sex" id="editSex" required>
        <option value="">Select Sex</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
      <input type="text" name="address" id="editAddress" placeholder="Address" required>
      <input type="text" name="destination" id="editDestination" placeholder="Destination" required>
    </div>
    <h4>More Details</h4>
    <div class="job-group">
      <input type="text" name="position" id="editPosition" placeholder="Position" required>
      <input type="text" name="salary" id="editSalary" placeholder="Salary" required>
      <input type="text" name="employer" id="editEmployer" placeholder="Employer" required>
      <input type="text" name="nameofthenewprincipal" id="editPrincipal" placeholder="Name of the New Principal" required>
    </div>
    <div>
      <h6 style="display: inline-block; margin-left: 255px;">Date of Arrival:</h6>
      <h6 style="display: inline-block; margin-left: 165px;">Date of Departure:</h6>
    </div>
    <div class="employment-group">
      <input type="date" name="employmentdurationstart" id="editStart" required>
      <input type="date" name="employmentdurationend" id="editEnd" required>
      <input type="date" name="dateofarrival" id="editArrival" required>
      <input type="date" name="dateofdeparture" id="editDeparture" required>
    </div>
    <button type="submit" class="action-button">Update</button>
    <button type="button" class="action-button cancel-button" onclick="closeEditModal()">Cancel</button>
  </form>
</div>

      <div id="addModal">
        <h3>Add New Record</h3>
        <form id="addForm">
        <input type="date" id="dateToday" name="dateToday" required>
        <div class="name-group">
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="given_name" placeholder="Given Name" required>
            <input type="text" name="middle_name" placeholder="Middle Name" required>
          </div>
          <div class="info-group">
            <select name="sex" required>
              <option value="">Select Sex</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <input type="text" name="address" placeholder="Address" required>
            <input type="text" name="destination" placeholder="Destination" required>
          </div>
          <h4>More Details</h4>
          <div class="job-group">
            <input type="text" name="position" placeholder="Position" required>
            <input type="text" name="salary" placeholder="Salary" required>
            <input type="text" name="employer" placeholder="Employer" required>
            <input type="text" name="nameofthenewprincipal" placeholder="Name of the New Principal" required>
          </div>
          <div>
            <h6 style="display: inline-block; margin-left: 255px;">Date of Arrival:</h6>
            <h6 style="display: inline-block; margin-left: 165px;">Date of Departure:</h6>
          </div>
          <div class="employment-group">
            <input type="date" name="employmentdurationstart" placeholder="Employment Duration Start" required>
            <input type="date" name="employmentdurationend" placeholder="Employment Duration End" required>
            <input type="date" name="dateofarrival" placeholder="Date of Arrival" required>
            <input type="date" name="dateofdeparture" placeholder="Date of Departure" required>

          </div>
          
          <button type="submit" class="action-button">Save</button>
          <button type="button" class="action-button cancel-button" onclick="closeAddModal()">Cancel</button>
        </form>
      </div>

      <button class="show-button" id="toggleButton" onclick="toggleTable()">Show All</button>

    </main>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function() {
  // Set the current date in the date picker input field
  document.getElementById('dateToday').value = new Date().toISOString().split('T')[0];
});

// OPEN the popup
function openAddModal() {
  document.getElementById('addModal').style.display = 'block';
}

// CLOSE the popup
function closeAddModal() {
  document.getElementById('addModal').style.display = 'none';
}

// HANDLE the form submission
document.getElementById('addForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);

  fetch('add_bm.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Append new row to table
      const table = document.querySelector("#data-table tbody");
      const newRow = document.createElement("tr");
      newRow.innerHTML = `
        <td>
          <button class='action-button' onclick='GenerateAC(${data.bmid})'>A</button>
          <button class='action-button' onclick='GenerateNVC(${data.bmid})'>B</button>
          <button class='action-button' onclick='GenerateCS(${data.bmid})'>C</button>
          <button class='action-button' onclick='GenerateNCC(${data.bmid})'>D</button>
          <button class='action-button' onclick='GenerateSP(${data.bmid})'>E</button>
          <button class='action-button' onclick='GenerateWEC(${data.bmid})'>F</button>
        </td>
        <td>
          <button class='action-button' onclick='updateRecord(${data.bmid})'>U</button>
          <button class='action-button' onclick='deleteRecord(${data.bmid})'>D</button>
        </td>
        <td>${data.bmid}</td>
        <td>${data.last_name}</td>
        <td>${data.given_name}</td>
        <td>${data.middle_name}</td>
        <td>${data.sex}</td>
        <td>${data.address}</td>
        <td>${data.destination}</td>
        <td>${data.remarks}</td>
      `;
      table.appendChild(newRow);

      closeAddModal();
      this.reset();
      alert("Record added successfully!");
    } else {
      alert("Error adding record.");
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert("Error saving data.");
  });
});

function searchTable() {
  var input = document.getElementById("searchInput").value.toUpperCase();
  var table = document.getElementById("data-table");
  var trs = table.querySelectorAll("tbody tr");

  trs.forEach(function(row) {
    var text = row.innerText.toUpperCase();
    if (text.indexOf(input) > -1) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }
  });
}

function GenerateAC(bmid) {
  //alert("For Generating Assessment Country chuchu.");
  window.location.href = `generate_ac.php?bmid=${bmid}`;
}
function GenerateNVC(bmid) {
  //alert("For Generating No Verified Contract Clearance chuchu.");
  window.location.href = `generate_nvc.php?bmid=${bmid}`;
}
function GenerateCS(bmid) {
  //alert("For Generating Critical Skills chuchu");
  window.location.href = `generate_cs.php?bmid=${bmid}`;
}
function GenerateNCC(bmid) {
  //alert("For Generating Non compliant country clearnce acheche.");
  window.location.href = `generate_ncc.php?bmid=${bmid}`;
}
function GenerateSP(bmid) {
  //alert("For Generating Seaferer's Position chuchu");
  window.location.href = `generate_sp.php?bmid=${bmid}`;
}
function GenerateWEC(bmid) {
  //alert("For Generating Watch Listed employer clearance chuchu.");
  window.location.href = `generate_wec.php?bmid=${bmid}`;
}

function updateRecord(bmid) {
  alert("Update function is disabled.");
}

function deleteRecord(bmid) {
  if (confirm("Are you sure you want to delete this record?")) {
    window.location.href = "delete.php?bmid=" + bmid;
  }
}

// Save original table when page loads
var originalTableHTML = document.getElementById("data-table").innerHTML;
var expanded = false; // tracking expanded state

function toggleTable() {
  var table = document.getElementById("data-table");
  var button = document.getElementById("toggleButton");

  if (!expanded) {
    // Expand the table with additional columns
    var thead = table.querySelector('thead');
    thead.innerHTML = `
      <tr>
        <th rowspan="2">GENERATE</th> <!-- Added Actions Column here -->
        <th rowspan="2">ACTIONS</th> <!-- Added Actions Column here -->
        <th rowspan="2">NO.</th>
        <th colspan="6">OFW'S INFORMATION</th>
        <th rowspan="2">COUNTER NO.</th>
        <th colspan="4">EVALUATION</th>
        <th rowspan="2">COUNTER NO.</th>
        <th colspan="3">PAYMENT</th>
        <th rowspan="2">REMARKS</th>
      </tr>
      <tr>
        <th>LAST NAME</th>
        <th>GIVEN NAME</th>
        <th>MIDDLE NAME</th>
        <th>SEX</th>
        <th>ADDRESS</th>
        <th>DESTINATION</th>
        <th>TYPE</th>
        <th>TIME IN</th>
        <th>TIME OUT</th>
        <th>TOTAL PCT</th>
        <th>TIME IN</th>
        <th>TIME OUT</th>
        <th>TOTAL PCT</th>
      </tr>
    `;

    // Fetch the expanded data
    var rows = <?php
      try {
        $stmt = $pdo->query("SELECT bmid, last_name, given_name, middle_name, sex, address, destination,
          eval_counter_no, eval_type, eval_time_in, eval_time_out, eval_total_pct,
          pay_counter_no, pay_time_in, pay_time_out, pay_total_pct, remarks FROM BM ORDER BY bmid");

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
          $data[] = $row;
        }
        echo json_encode($data);
      } catch (PDOException $e) {
        echo "[]";
      }
    ?>;

    var tbody = table.querySelector('tbody');
    tbody.innerHTML = "";

    if (rows.length === 0) {
      tbody.innerHTML = "<tr><td colspan='17' class='text-center'>No data found.</td></tr>";
    } else {
      rows.forEach(function(row) {
        var tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <button class='action-button' onclick='updateRecord(${row.bmid})'>A</button>
            <button class='action-button' onclick='deleteRecord(${row.bmid})'>B</button>
            <button class='action-button' onclick='deleteRecord(${row.bmid})'>C</button>
            <button class='action-button' onclick='deleteRecord(${row.bmid})'>D</button>
            <button class='action-button' onclick='deleteRecord(${row.bmid})'>E</button>
            <button class='action-button' onclick='deleteRecord(${row.bmid})'>F</button>
          </td>
          <td>
            <button class='action-button' onclick='updateRecord(${row.bmid})'>U</button>
            <button class='action-button' onclick='deleteRecord(${row.bmid})'>D</button>
          </td>
          <td>${row.bmid}</td>
          <td>${row.last_name}</td>
          <td>${row.given_name}</td>
          <td>${row.middle_name}</td>
          <td>${row.sex}</td>
          <td>${row.address}</td>
          <td>${row.destination}</td>
          <td>${row.eval_counter_no}</td>
          <td>${row.eval_type}</td>
          <td>${row.eval_time_in}</td>
          <td>${row.eval_time_out}</td>
          <td>${row.eval_total_pct}</td>
          <td>${row.pay_counter_no}</td>
          <td>${row.pay_time_in}</td>
          <td>${row.pay_time_out}</td>
          <td>${row.pay_total_pct}</td>
          <td>${row.remarks}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    button.innerText = "Hide"; // change button text
    expanded = true;

  } else {
    // Collapse back to original
    table.innerHTML = originalTableHTML;
    button.innerText = "Show All"; // change back
    expanded = false;
  }
}
</script>

</body>
</html>
>>>>>>> e676bef (Initial commit on updated_BM)
