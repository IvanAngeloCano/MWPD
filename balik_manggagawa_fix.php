<?php
// This file is a temporary fix to update the edit button in the balik_manggagawa.php file
// Copy the contents of this file to balik_manggagawa.php after reviewing

include 'session.php';
require_once 'connection.php';

$pageTitle = "Balik Manggagawa";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php
    $currentFile = basename($_SERVER['PHP_SELF']);
    $fileWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
    $pageTitle = 'Balik Manggagawa';
    $currentPage = 'balik_manggagawa.php';
    include '_header.php';
    ?>

    <main class="main-content">
      <h2>Balik Manggagawa</h2>

      <style>
        .balik-mangagawa-table {
          border-collapse: collapse;
          width: 100%;
          margin-top: 20px;
        }
        .balik-mangagawa-table th, .balik-mangagawa-table td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: left;
        }
        .balik-mangagawa-table th {
          background-color: #f2f2f2;
          color: #333;
        }
        .balik-mangagawa-table tr:nth-child(even) {
          background-color: #f9f9f9;
        }
        .balik-mangagawa-table tr:hover {
          background-color: #f1f1f1;
        }
        .show-button {
          background-color: #4CAF50;
          color: white;
          padding: 10px 15px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          margin-top: 10px;
          text-decoration: none;
          display: inline-block;
        }
        .show-button:hover {
          background-color: #45a049;
        }
        #searchInput {
          padding: 10px;
          width: 300px;
          margin-bottom: 10px;
          border: 1px solid #ddd;
          border-radius: 4px;
        }
        .edit-button {
          background-color: #2196F3;
          color: white;
          padding: 5px 10px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          margin-right: 5px;
          text-decoration: none;
          display: inline-block;
        }
        .edit-button:hover {
          background-color: #0b7dda;
        }
        .delete-button {
          background-color: #f44336;
          color: white;
          padding: 5px 10px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          margin-right: 5px;
        }
        .delete-button:hover {
          background-color: #da190b;
        }
        .action-icons {
          display: flex;
          gap: 5px;
        }
        .action-icons a {
          color: #333;
          text-decoration: none;
          display: flex;
          align-items: center;
          gap: 5px;
        }
        .action-icons a:hover {
          color: #007bff;
        }
        .action-icons i {
          font-size: 16px;
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

      <a href="balik_manggagawa_add.php" class="show-button">+ Add New Record</a>

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
                      <td>
                        <a href='balik_manggagawa_edit.php?bmid=" . $row['bmid'] . "' class='edit-button' title='Edit Record'>
                          <i class='fa fa-edit'></i>
                        </a>
                        <button class='delete-button' onclick='deleteRecord(" . $row['bmid'] . ")' title='Delete Record'>
                          <i class='fa fa-trash'></i>
                        </button>
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
    if (document.getElementById('dateToday')) {
      document.getElementById('dateToday').value = new Date().toISOString().split('T')[0];
    }
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
    window.open(`generate_ac.php?bmid=${bmid}`, '_blank');
  }
  
  function GenerateNVC(bmid) {
    window.open(`generate_nvc.php?bmid=${bmid}`, '_blank');
  }
  
  function GenerateCS(bmid) {
    window.open(`generate_cs.php?bmid=${bmid}`, '_blank');
  }
  
  function GenerateNCC(bmid) {
    window.open(`generate_ncc.php?bmid=${bmid}`, '_blank');
  }
  
  function GenerateSP(bmid) {
    window.open(`generate_sp.php?bmid=${bmid}`, '_blank');
  }
  
  function GenerateWEC(bmid) {
    window.open(`generate_wec.php?bmid=${bmid}`, '_blank');
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
          <th colspan="4">EMPLOYMENT DETAILS</th>
          <th colspan="2">TRAVEL INFORMATION</th>
          <th rowspan="2">REMARKS</th>
        </tr>
        <tr>
          <th>LAST NAME</th>
          <th>GIVEN NAME</th>
          <th>MIDDLE NAME</th>
          <th>SEX</th>
          <th>ADDRESS</th>
          <th>DESTINATION</th>
          <th>AGENCY</th>
          <th>PRINCIPAL</th>
          <th>NEW AGENCY</th>
          <th>NEW PRINCIPAL</th>
          <th>ARRIVAL</th>
          <th>DEPARTURE</th>
        </tr>
      `;

      // Update all rows with additional columns
      var rows = table.querySelectorAll('tbody tr');
      rows.forEach(function(row) {
        if (row.cells.length > 1) { // Skip "No data found" row
          var bmid = row.cells[2].innerText; // Get the BMID from the NO. column
          
          // Fetch additional data for this row
          fetch(`get_bm.php?bmid=${bmid}`)
            .then(response => response.json())
            .then(data => {
              if (!data.error) {
                // Add the additional cells
                row.innerHTML += `
                  <td>${data.nameoftheagency || ''}</td>
                  <td>${data.nameoftheprincipal || ''}</td>
                  <td>${data.nameofthenewagency || ''}</td>
                  <td>${data.nameofthenewprincipal || ''}</td>
                  <td>${data.dateofarrival || ''}</td>
                  <td>${data.dateofdeparture || ''}</td>
                `;
              }
            })
            .catch(error => {
              console.error('Error:', error);
            });
        }
      });

      button.textContent = "Show Less";
      expanded = true;
    } else {
      // Restore original table
      table.innerHTML = originalTableHTML;
      button.textContent = "Show All";
      expanded = false;
    }
  }
</script>
