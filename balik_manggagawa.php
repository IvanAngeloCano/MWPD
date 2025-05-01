<?php
include 'session.php';
require_once 'connection.php';
$pageTitle = "Balik Manggagawa - MWPD Filing System";
include '_head.php';
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
        .action-icons {
          display: flex;
          gap: 10px;
        }
        .action-icons a {
          text-decoration: none;
          color: inherit;
          padding: 0.25rem;
        }
        .action-icons a:hover {
          opacity: 0.8;
        }
        .action-icons i.fa-edit {
          color: #28a745; /* Green color for edit icon */
        }
        .action-icons i.fa-trash-alt {
          color: #dc3545; /* Red color for delete icon */
        }
        .action-icons i.fa-file-alt,
        .action-icons i.fa-file-pdf,
        .action-icons i.fa-file-word,
        .action-icons i.fa-file-contract,
        .action-icons i.fa-file-signature,
        .action-icons i.fa-file-invoice {
          color: #007bff; /* Blue color for file icons */
        }
        /* Generate Documents Modal */
        #generateModal {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          z-index: 9999;
          justify-content: center;
          align-items: center;
        }
        /* When the modal is shown, add this class to display it */
        #generateModal.show {
          display: flex;
        }
        #generateModal .modal-content {
          position: relative;
          background-color: #fff;
          width: 800px;
          border-radius: 8px;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
          overflow: visible;
        }
        #generateModal .modal-header {
          background-color: #007bff;
          color: white;
          padding: 15px 20px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          overflow: visible;
        }
        #generateModal .modal-body {
          padding: 20px;
          overflow: visible;
        }
        #generateModal .modal-header h2 {
          margin: 0;
          font-size: 1.5rem;
        }
        #generateModal .modal-header .close {
          color: white;
          font-size: 28px;
          font-weight: bold;
          cursor: pointer;
          transition: 0.3s;
        }
        #generateModal .modal-header .close:hover {
          color: #f8f9fa;
        }
        #generateModal .generate-options {
          display: grid;
          grid-template-columns: repeat(2, 1fr);
          gap: 15px;
          justify-content: center;
        }
        #generateModal .generate-btn {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 15px;
          background-color: #f8f9fa;
          border: 1px solid #dee2e6;
          border-radius: 6px;
          cursor: pointer;
          transition: all 0.2s ease;
          text-align: center;
          overflow: visible;
        }
        #generateModal .generate-btn:hover {
          background-color: #e9e9e9;
          border-color: #ced4da;
          transform: translateY(-2px);
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        #generateModal .generate-btn i {
          font-size: 24px;
          margin-bottom: 10px;
          color: #007bff;
        }
        #generateModal .generate-btn span {
          font-weight: 600;
          font-size: 16px;
          color: #212529;
          margin-bottom: 5px;
        }
        #generateModal .generate-btn small {
          font-size: 13px;
          color: #6c757d;
          line-height: 1.4;
        }
      </style>

      <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search for names..">

      <button class="show-button" onclick="openAddModal()">+ Add New Record</button>

      <table id="data-table" class="balik-mangagawa-table">
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
                        <div class='action-icons'>
                          <a href='javascript:void(0)' onclick='openGenerateModal(" . $row['bmid'] . ")' title='Generate Documents'>
                            <i class='fa fa-file-export'></i> Generate
                          </a>
                        </div>
                      </td>
                      <td class='action-icons'>
                        <a href='javascript:void(0)' onclick='openUpdateModal(" . $row['bmid'] . ")' title='Edit Record'>
                          <i class='fa fa-edit'></i>
                        </a>
                        <a href='javascript:void(0)' onclick='deleteRecord(" . $row['bmid'] . ")' title='Delete Record'>
                          <i class='fa fa-trash-alt'></i>
                        </a>
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

      <!-- Update Modal -->
      <div id="updateModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <span class="close" onclick="closeUpdateModal()">&times;</span>
            <h2>Update Balik Manggagawa Record</h2>
          </div>
          <form id="updateForm" method="post">
            <input type="hidden" name="bmid" id="update_bmid">
            <div class="form-group">
              <input type="text" name="last_name" id="update_last_name" placeholder="Last Name" required>
              <input type="text" name="given_name" id="update_given_name" placeholder="Given Name" required>
              <input type="text" name="middle_name" id="update_middle_name" placeholder="Middle Name" required>
            </div>
            <div class="form-group">
              <select name="sex" id="update_sex" required>
                <option value="">Select Sex</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
              <input type="text" name="address" id="update_address" placeholder="Address" required>
              <input type="text" name="destination" id="update_destination" placeholder="Destination" required>
            </div>
            <div class="form-group">
              <input type="text" name="remarks" id="update_remarks" placeholder="Remarks" required>
              <input type="text" name="nameoftheagency" id="update_nameoftheagency" placeholder="Name of the Agency" required>
              <input type="text" name="nameoftheprincipal" id="update_nameoftheprincipal" placeholder="Name of the Principal" required>
            </div>
            <div class="form-group">
              <input type="text" name="nameofthenewagency" id="update_nameofthenewagency" placeholder="Name of the New Agency" required>
              <input type="text" name="nameofthenewprincipal" id="update_nameofthenewprincipal" placeholder="Name of the New Principal" required>
            </div>
            <div>
              <h6 style="display: inline-block; margin-left: 255px;">Date of Arrival:</h6>
              <h6 style="display: inline-block; margin-left: 165px;">Date of Departure:</h6>
            </div>
            <div class="employment-group">
              <input type="date" name="employmentdurationstart" id="update_employmentdurationstart" placeholder="Employment Duration Start" required>
              <input type="date" name="employmentdurationend" id="update_employmentdurationend" placeholder="Employment Duration End" required>
              <input type="date" name="dateofarrival" id="update_dateofarrival" placeholder="Date of Arrival" required>
              <input type="date" name="dateofdeparture" id="update_dateofdeparture" placeholder="Date of Departure" required>
            </div>
            
            <button type="submit" class="action-button">Update</button>
            <button type="button" class="action-button cancel-button" onclick="closeUpdateModal()">Cancel</button>
          </form>
        </div>
      </div>

      <!-- Generate Documents Modal -->
      <div id="generateModal" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <span class="close" onclick="closeGenerateModal()">&times;</span>
            <h2>Generate Documents</h2>
          </div>
          <div class="modal-body">
            <p style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: #495057; text-align: center;">Select a document to generate:</p>
            <div class="generate-options">
              <button onclick="GenerateAC(currentBmid); closeGenerateModal();" class="generate-btn">
                <i class="fa fa-file-alt"></i>
                <span>Assessment Certificate</span>
                <small>Official assessment of OFW qualifications</small>
              </button>
              <button onclick="GenerateNVC(currentBmid); closeGenerateModal();" class="generate-btn">
                <i class="fa fa-file-pdf"></i>
                <span>NVC Document</span>
                <small>No Verification Certificate for employment</small>
              </button>
              <button onclick="GenerateCS(currentBmid); closeGenerateModal();" class="generate-btn">
                <i class="fa fa-file-word"></i>
                <span>Certification Sheet</span>
                <small>Official certification of OFW status</small>
              </button>
              <button onclick="GenerateNCC(currentBmid); closeGenerateModal();" class="generate-btn">
                <i class="fa fa-file-contract"></i>
                <span>NCC Document</span>
                <small>No Certification Certificate for processing</small>
              </button>
              <button onclick="GenerateSP(currentBmid); closeGenerateModal();" class="generate-btn">
                <i class="fa fa-file-signature"></i>
                <span>Seafarer Document</span>
                <small>Special documentation for seafarers</small>
              </button>
              <button onclick="GenerateWEC(currentBmid); closeGenerateModal();" class="generate-btn">
                <i class="fa fa-file-invoice"></i>
                <span>WEC Document</span>
                <small>Worker Eligibility Certificate</small>
              </button>
            </div>
          </div>
        </div>
      </div>

      <button class="show-button" id="toggleButton" onclick="toggleTable()">Show All</button>

    </main>
  </div>
</div>

<script>
  // Global variable to track the current bmid for document generation
  let currentBmid = null;
  
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
          <div class='action-icons'>
            <a href='javascript:void(0)' onclick='openGenerateModal(${data.bmid})' title='Generate Documents'>
              <i class='fa fa-file-export'></i> Generate
            </a>
          </div>
        </td>
        <td class='action-icons'>
          <a href='javascript:void(0)' onclick='openUpdateModal(${data.bmid})' title='Edit Record'>
            <i class='fa fa-edit'></i>
          </a>
          <a href='javascript:void(0)' onclick='deleteRecord(${data.bmid})' title='Delete Record'>
            <i class='fa fa-trash-alt'></i>
          </a>
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

function openGenerateModal(bmid) {
  currentBmid = bmid;
  document.getElementById('generateModal').classList.add('show');
}

function closeGenerateModal() {
  document.getElementById('generateModal').classList.remove('show');
}

function GenerateAC(bmid) {
  //alert("For Generating Assessment Country chuchu.");
  window.open(`generate_ac.php?bmid=${bmid}`, '_blank');
}
function GenerateNVC(bmid) {
  //alert("For Generating No Verified Contract Clearance chuchu.");
  window.open(`generate_nvc.php?bmid=${bmid}`, '_blank');
}
function GenerateCS(bmid) {
  //alert("For Generating Critical Skills chuchu");
  window.open(`generate_cs.php?bmid=${bmid}`, '_blank');
}
function GenerateNCC(bmid) {
  //alert("For Generating Non compliant country clearnce acheche.");
  window.open(`generate_ncc.php?bmid=${bmid}`, '_blank');
}
function GenerateSP(bmid) {
  //alert("For Generating Seaferer's Position chuchu");
  window.open(`generate_sp.php?bmid=${bmid}`, '_blank');
}
function GenerateWEC(bmid) {
  //alert("For Generating Watch Listed employer clearance chuchu.");
  window.open(`generate_wec.php?bmid=${bmid}`, '_blank');
}

function openUpdateModal(bmid) {
  fetch(`get_bm.php?bmid=${bmid}`)
  .then(response => response.json())
  .then(data => {
    document.getElementById('update_bmid').value = data.bmid;
    document.getElementById('update_last_name').value = data.last_name;
    document.getElementById('update_given_name').value = data.given_name;
    document.getElementById('update_middle_name').value = data.middle_name;
    document.getElementById('update_sex').value = data.sex;
    document.getElementById('update_address').value = data.address;
    document.getElementById('update_destination').value = data.destination;
    document.getElementById('update_remarks').value = data.remarks;
    document.getElementById('update_nameoftheagency').value = data.nameoftheagency;
    document.getElementById('update_nameoftheprincipal').value = data.nameoftheprincipal;
    document.getElementById('update_nameofthenewagency').value = data.nameofthenewagency;
    document.getElementById('update_nameofthenewprincipal').value = data.nameofthenewprincipal;
    document.getElementById('update_employmentdurationstart').value = data.employmentdurationstart;
    document.getElementById('update_employmentdurationend').value = data.employmentdurationend;
    document.getElementById('update_dateofarrival').value = data.dateofarrival;
    document.getElementById('update_dateofdeparture').value = data.dateofdeparture;

    document.getElementById('updateModal').style.display = 'block';
  })
  .catch(error => {
    console.error('Error:', error);
    alert("Error fetching data.");
  });
}

function closeUpdateModal() {
  document.getElementById('updateModal').style.display = 'none';
}

document.getElementById('updateForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);

  fetch('update_bm.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Record updated successfully!");
      closeUpdateModal();
      location.reload();
    } else {
      alert("Error updating record.");
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert("Error updating data.");
  });
});

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
            <div class='action-icons'>
              <a href='javascript:void(0)' onclick='openGenerateModal(${row.bmid})' title='Generate Documents'>
                <i class='fa fa-file-export'></i> Generate
              </a>
            </div>
          </td>
          <td class='action-icons'>
            <a href='javascript:void(0)' onclick='openUpdateModal(${row.bmid})' title='Edit Record'>
              <i class='fa fa-edit'></i>
            </a>
            <a href='javascript:void(0)' onclick='deleteRecord(${row.bmid})' title='Delete Record'>
              <i class='fa fa-trash-alt'></i>
            </a>
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
