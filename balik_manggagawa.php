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
        .balik-mangagawa-table {
          border-collapse: collapse;
          width: 100%;
          margin-top: 20px;
          border: 1px solid #ddd;
        }
        .balik-mangagawa-table th, .balik-mangagawa-table td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: left;
        }
        .balik-mangagawa-table th {
          background-color: #007bff;
          color: white;
          font-weight: 500;
          border: none;
        }
        .balik-mangagawa-table tr:nth-child(even) {
          background-color: #f9f9f9;
        }
        .balik-mangagawa-table tr:hover {
          background-color: #f1f1f1;
        }
        .show-button {
          background-color: #4CAF50;
          color: white !important;
          padding: 10px 15px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          text-decoration: none;
          display: flex;
          align-items: center;
          gap: 5px;
          font-weight: 500;
          height: 40px;
        }
        
        .show-button:hover {
          background-color: #45a049;
        }
        
        /* Process page top styling */
        .process-page-top {
          display: flex;
          flex-direction: column;
          gap: 0.5rem;
          margin-bottom: 15px;
        }
        
        .controls {
          display: flex;
          justify-content: space-between;
          align-items: center;
          gap: 0.5rem;
        }
        
        /* Search form styling */
        .search-form {
          display: flex;
          align-items: center;
          flex: 1;
          max-width: 800px;
        }
        
        .search-bar {
          border: 1px solid #ced4da;
          padding: 6px 10px;
          border-radius: 4px;
          flex-grow: 1;
          min-width: 500px;
          height: 34px;
          outline: none;
          font-size: 13px;
          margin-right: 10px;
        }
        
        .search-btn {
          background-color: #343a40;
          border: none;
          color: white;
          padding: 0 8px;
          height: 34px;
          border-radius: 4px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        
        .search-btn i {
          font-size: 12px;
        }
        
        .search-btn:hover {
          background-color: #23272b;
        }
        
        /* Button styling */
        .add-btn {
          background-color: #28a745;
          color: white;
          border: none;
          padding: 8px 15px;
          border-radius: 4px;
          text-decoration: none;
          display: flex;
          align-items: center;
          gap: 5px;
        }
        
        .add-btn:hover {
          background-color: #218838;
          color: white;
        }
        
        /* Pagination styling */
        .pagination-container {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-top: 20px;
          flex-wrap: wrap;
          gap: 10px;
        }
        
        .showing-results {
          font-size: 14px;
          color: #666;
        }
        
        .pagination {
          display: flex;
          align-items: center;
          gap: 10px;
        }
        
        .prev-btn, .next-btn {
          padding: 6px 12px;
          border: 1px solid #ddd;
          color: #007bff;
          text-decoration: none;
          border-radius: 4px;
          background: white;
          cursor: pointer;
        }
        
        .prev-btn:hover, .next-btn:hover {
          background-color: #f1f1f1;
        }
        
        .page-numbers {
          display: flex;
          align-items: center;
          gap: 5px;
        }
        
        .page-numbers a, .page-numbers button {
          padding: 6px 12px;
          border: 1px solid #ddd;
          color: #007bff;
          text-decoration: none;
          border-radius: 4px;
          background: white;
          cursor: pointer;
        }
        
        .page-numbers a.active, .page-numbers button.active {
          background-color: #007bff;
          color: white;
          border-color: #007bff;
        }
        
        .page-numbers a:hover, .page-numbers button:hover {
          background-color: #f1f1f1;
        }
        
        .go-to-page {
          display: flex;
          align-items: center;
          gap: 10px;
        }
        
        .go-to-page input {
          width: 50px;
          padding: 5px;
          border-radius: 4px;
          border: 1px solid #ddd;
          text-align: center;
        }
        
        .go-to-page button {
          padding: 5px 10px;
          background-color: #007bff;
          color: white;
          border: none;
          border-radius: 4px;
          cursor: pointer;
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
        
        /* Delete Modal Styles */
        #deleteModal {
          display: none;
          position: fixed;
          z-index: 1000;
          left: 0;
          top: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          justify-content: center;
          align-items: center;
        }
        
        #deleteModal.show {
          display: flex;
        }
        
        #deleteModal .modal-content {
          background-color: #fff;
          border-radius: 5px;
          width: 400px;
          max-width: 90%;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        #deleteModal .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 15px 20px;
          background-color: #ffc107; /* Changed from red to yellow */
          color: white;
          border-top-left-radius: 5px;
          border-top-right-radius: 5px;
        }
        
        #deleteModal .modal-header h3 {
          margin: 0;
          font-size: 1.25rem;
        }
        
        #deleteModal .modal-close {
          background: none;
          border: none;
          color: white;
          font-size: 24px;
          cursor: pointer;
        }
        
        #deleteModal .modal-body {
          padding: 20px;
        }
        
        #deleteModal .modal-actions {
          display: flex;
          justify-content: flex-end;
          gap: 10px;
          margin-top: 20px;
        }
        
        #deleteModal .btn {
          padding: 8px 16px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          font-weight: 500;
        }
        
        #deleteModal .btn-secondary {
          background-color: #6c757d;
          color: white;
        }
        
        #deleteModal .btn-danger {
          background-color: #ffc107; /* Changed from red to yellow */
          color: white;
        }
        
        #deleteModal .btn-danger:hover {
          background-color: #e0a800; /* Darker yellow on hover */
        }
        
        #deleteModal .btn:hover {
          opacity: 0.9;
        }
        
        /* Delete button styles */
        .delete-button {
          background: none;
          border: none;
          color: #dc3545;
          cursor: pointer;
          padding: 5px;
          margin-left: 5px;
        }
        
        .delete-button:hover {
          color: #bd2130;
        }
        
        /* Action icons styling */
        .action-icons {
          display: flex;
          flex-direction: row;
          align-items: center;
          gap: 8px;
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
        
        /* Edit and delete buttons styling */
        .edit-button {
          background: none;
          border: none;
          color: #28a745; /* Green color */
          padding: 5px;
          cursor: pointer;
          font-size: 16px;
          text-decoration: none;
        }
        
        .edit-button:hover {
          color: #218838; /* Darker green on hover */
        }
        
        .edit-button i {
          font-size: 16px;
        }
        
        /* Reset button styling */
        .reset-btn {
          background-color: #007bff;
          color: white;
          padding: 5px 10px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          margin-left: 10px;
        }
        
        /* Button group styling */
        .button-group {
          display: flex;
          align-items: center;
          gap: 10px;
        }
        
        /* Add New Record button styling */
        .add-button, .show-button {
          background-color: #4CAF50;
          color: white !important;
          padding: 6px 12px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          text-decoration: none;
          display: flex;
          align-items: center;
          gap: 5px;
          font-weight: 500;
          height: 32px;
          font-size: 13px;
        }
        
        .add-button:hover, .show-button:hover {
          background-color: #45a049;
        }
        
        /* Rows per page input styling */
        .rows-input {
          width: 60px;
          padding: 4px 8px;
          border: 1px solid #ccc;
          border-radius: 4px;
          margin: 0 5px;
        }
      </style>

      <!-- Main content starts here -->
      <div class="process-page-top" style="margin-top: 10px; margin-bottom: 0px;">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
          <div style="display: flex; align-items: center; flex: 1; margin-right: 10px;">
            <form action="" method="GET" style="display: flex; align-items: center; width: 100%;">
              <div style="display: flex; align-items: center; width: 100%;">
                <input type="text" name="search" placeholder="Search" style="border: 1px solid #ced4da; padding: 6px 10px; border-radius: 4px; flex-grow: 1; min-width: 500px; height: 31px; outline: none; font-size: 13px; margin-right: 10px;" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit" style="background-color: #343a40; border: none; color: white; padding: 0 8px; height: 31px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fa fa-search" style="font-size: 14px;"></i></button>
              </div>
            </form>
          </div>
          
          <div style="display: flex; white-space: nowrap;">
            <a href="balik_manggagawa_add.php" class="btn" style="background-color: #28a745; color: white; border: none; border-radius: 4px; font-weight: 500; height: 31px; line-height: 31px; padding: 0 1rem; margin-right: 3px; display: inline-flex; align-items: center;">
              <i class="fa fa-plus" style="margin-right: 5px;"></i> Add New Record
            </a>
            <button class="btn" id="toggleButton" onclick="toggleTable()" style="background-color: #007bff; color: white; border: none; border-radius: 4px; font-weight: 500; height: 31px; line-height: 31px; padding: 0 1rem; display: inline-flex; align-items: center;">
              <i class="fa fa-table" style="margin-right: 5px;"></i> Show All
            </button>
          </div>
        </div>
      </div>
      
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0px; margin-top: 10px;">
        <div class="showing-results" style="font-size: 14px;">
          <span>Showing <span id="startRecord">1</span>-<span id="endRecord">7</span> of <span id="totalRecords">0</span> records</span>
        </div>
        
        <div class="rows-per-page" style="display:inline-block;">
          <label style="margin-right: 5px; font-weight: normal;">
            Rows per page:
            <input type="number" min="1" id="rowsPerPage" value="7" style="width: 45px; border: 1px solid #ced4da; border-radius: 3px; padding: 2px 5px;">
          </label>
          <button type="button" id="resetRowsBtn" style="background-color:#007bff;color:#fff;border:none;border-radius:3px;padding:2px 8px;font-size: 12px;">Reset</button>
        </div>
      </div>

      <table id="data-table" class="balik-mangagawa-table">
        <thead>
          <tr>
            <th rowspan="2">Generate</th>
            <th rowspan="2">Actions</th>
            <th rowspan="2">No.</th>
            <th colspan="6" style="text-align: center;">OFW's information</th>
            <th rowspan="2">Remarks</th>
          </tr>
          <tr>
            <th>Last name</th>
            <th>Given name</th>
            <th>Middle name</th>
            <th>Sex</th>
            <th>Address</th>
            <th>Destination</th>
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
                        <a href='balik_manggagawa_edit.php?bmid=" . $row['bmid'] . "&view=true' title='View Record' style='color: #007bff; text-decoration: none; margin-right: 10px;'>
                          <i class='fa fa-eye'></i>
                        </a>
                        <a href='balik_manggagawa_edit.php?bmid=" . $row['bmid'] . "' title='Edit Record' style='color: #28a745; text-decoration: none; margin-right: 10px;'>
                          <i class='fa fa-edit'></i>
                        </a>
                        <button class='delete-button' onclick='openDeleteModal(" . $row['bmid'] . ")' title='Delete Record'>
                          <i class='fa fa-trash-alt'></i>
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

      <div class="pagination-container">
        <div class="pagination">
          <button class="prev-btn" id="prevPageBtn" onclick="prevPage()">
            <i class="fa fa-chevron-left"></i> Previous
          </button>
          
          <div id="pageNumbers" class="page-numbers">
            <!-- Page numbers will be generated by JavaScript -->
          </div>
          
          <button class="next-btn" id="nextPageBtn" onclick="nextPage()">
            Next <i class="fa fa-chevron-right"></i>
          </button>
        </div>
        
        <div class="go-to-page">
          <label>Go to Page:</label>
          <input type="number" id="goToPage" min="1" placeholder="1">
          <button type="button" class="btn go-btn" onclick="goToPageBtn()">Go</button>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
  // Global variable to track the current bmid for document generation
  let currentBmid = null;
  let currentPage = 1;
  let rowsPerPage = 7; // Changed default to 7
  let totalPages = 1;
  let deleteBmid = null;
  
  document.addEventListener("DOMContentLoaded", function() {
    // Set the current date in the date picker input field
    if (document.getElementById('dateToday')) {
      document.getElementById('dateToday').value = new Date().toISOString().split('T')[0];
    }
    
    // Initialize pagination
    initPagination();
    
    // Set default rows per page
    document.getElementById('rowsPerPage').value = rowsPerPage;
    
    // Add reset button event listener
    document.getElementById('resetRowsBtn').addEventListener('click', function() {
      rowsPerPage = 7;
      document.getElementById('rowsPerPage').value = rowsPerPage;
      currentPage = 1;
      initPagination();
    });
    
    // Add input event for rows per page
    document.getElementById('rowsPerPage').addEventListener('input', function() {
      let newValue = parseInt(this.value);
      
      // Handle empty or invalid values
      if (isNaN(newValue) || newValue <= 0) {
        // Don't update yet, wait for valid input
        return;
      }
      
      // Update rows per page and refresh pagination
      rowsPerPage = newValue;
      currentPage = 1;
      initPagination();
    });
    
    // Handle blur event to catch when user leaves the field
    document.getElementById('rowsPerPage').addEventListener('blur', function() {
      let newValue = parseInt(this.value);
      
      // If empty or invalid, set to 1
      if (isNaN(newValue) || newValue <= 0) {
        this.value = 1;
        rowsPerPage = 1;
        currentPage = 1;
        initPagination();
      }
    });
  });
  
  function initPagination() {
    const table = document.getElementById("data-table");
    const rows = table.querySelectorAll("tbody tr");
    
    // Store rows globally for search and pagination
    window.allRows = Array.from(rows);
    
    // Calculate total pages
    totalPages = Math.ceil(window.allRows.length / rowsPerPage);
    
    // Update showing records information
    updateShowingInfo(window.allRows.length);
    
    // Show first page
    showPage(currentPage);
    
    // Log for debugging
    console.log(`Pagination initialized: ${window.allRows.length} rows, ${rowsPerPage} per page, ${totalPages} pages`);
  }
  
  function updateShowingInfo(totalRecords) {
    document.getElementById('totalRecords').textContent = totalRecords;
    const start = totalRecords > 0 ? (currentPage - 1) * rowsPerPage + 1 : 0;
    const end = Math.min(currentPage * rowsPerPage, totalRecords);
    document.getElementById('startRecord').textContent = start;
    document.getElementById('endRecord').textContent = end;
    
    // Log for debugging
    console.log(`Showing ${start}-${end} of ${totalRecords} records`);
  }
  
  function showPage(page) {
    // Ensure we have the rows
    if (!window.allRows || window.allRows.length === 0) {
      console.log("No rows found for pagination");
      return;
    }
    
    // Hide all rows
    window.allRows.forEach(row => {
      row.style.display = "none";
    });
    
    // Calculate start and end index for current page
    const startIndex = (page - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, window.allRows.length);
    
    // Show rows for current page
    let visibleCount = 0;
    for (let i = startIndex; i < endIndex; i++) {
      if (window.allRows[i]) {
        window.allRows[i].style.display = "";
        visibleCount++;
      }
    }
    
    // Update current page
    currentPage = page;
    
    // Update pagination display
    updatePaginationDisplay();
    
    // Update showing records information
    updateShowingInfo(window.allRows.length);
    
    // Log for debugging
    console.log(`Showing page ${page}: rows ${startIndex+1}-${endIndex} (${visibleCount} visible rows)`);
  }
  
  function updatePaginationDisplay() {
    const prevButton = document.querySelector(".prev-btn");
    const nextButton = document.querySelector(".next-btn");
    const pageNumbers = document.getElementById("pageNumbers");
    const startRecord = document.getElementById("startRecord");
    const endRecord = document.getElementById("endRecord");
    const totalRecords = document.getElementById("totalRecords");
    
    // Disable/enable prev button
    if (currentPage === 1) {
      prevButton.disabled = true;
    } else {
      prevButton.disabled = false;
    }
    
    // Disable/enable next button
    if (currentPage === totalPages || totalPages === 0) {
      nextButton.disabled = true;
    } else {
      nextButton.disabled = false;
    }
    
    // Update page numbers
    pageNumbers.innerHTML = "";
    for (let i = 1; i <= totalPages; i++) {
      const pageNumber = document.createElement("button");
      pageNumber.textContent = i;
      pageNumber.onclick = function() {
        showPage(i);
      };
      if (i === currentPage) {
        pageNumber.classList.add("active");
      }
      pageNumbers.appendChild(pageNumber);
    }
    
    // Update showing records information
    startRecord.textContent = (currentPage - 1) * rowsPerPage + 1;
    endRecord.textContent = Math.min(currentPage * rowsPerPage, window.allRows.length);
    totalRecords.textContent = window.allRows.length;
  }
  
  function prevPage() {
    if (currentPage > 1) {
      showPage(currentPage - 1);
    }
  }
  
  function nextPage() {
    if (currentPage < totalPages) {
      showPage(currentPage + 1);
    }
  }
  
  function changeRowsPerPage() {
    rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
    currentPage = 1; // Reset to first page
    initPagination();
  }
  
  function goToPageBtn() {
    const pageInput = document.getElementById('goToPage');
    let page = parseInt(pageInput.value);
    
    if (isNaN(page)) return;
    
    // Ensure page is within valid range
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    
    showPage(page);
    pageInput.value = page;
  }

  function searchTable() {
    var input = document.getElementById("searchInput").value.toUpperCase();
    var table = document.getElementById("data-table");
    var rows = Array.from(table.querySelectorAll("tbody tr"));
    var filteredRows = [];

    rows.forEach(function(row) {
      var text = row.innerText.toUpperCase();
      if (text.indexOf(input) > -1) {
        filteredRows.push(row);
      }
    });
    
    // Hide all rows first
    rows.forEach(function(row) {
      row.style.display = "none";
    });
    
    // Store filtered rows for pagination
    window.allRows = filteredRows;
    
    // Reset to first page and update pagination
    currentPage = 1;
    totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    
    // Update pagination display
    updatePaginationDisplay();
    
    // Show first page of filtered results
    showPage(1);
    
    console.log(`Search results: ${filteredRows.length} matches found for "${input}"`);
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

  function openDeleteModal(bmid) {
    deleteBmid = bmid;
    document.getElementById('deleteModal').classList.add('show');
  }

  function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
  }

  function confirmDelete() {
    window.location.href = "delete.php?bmid=" + deleteBmid;
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
          <th rowspan="2">Generate</th>
          <th rowspan="2">Actions</th>
          <th rowspan="2">No.</th>
          <th colspan="6">OFW's information</th>
          <th colspan="4">Employment details</th>
          <th colspan="2">Travel information</th>
          <th rowspan="2">Remarks</th>
        </tr>
        <tr>
          <th>Last name</th>
          <th>Given name</th>
          <th>Middle name</th>
          <th>Sex</th>
          <th>Address</th>
          <th>Destination</th>
          <th>Agency</th>
          <th>Principal</th>
          <th>New agency</th>
          <th>New principal</th>
          <th>Arrival</th>
          <th>Departure</th>
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

  // Add double-click functionality to table rows
  document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('.balik-mangagawa-table tbody tr');
    
    tableRows.forEach(row => {
      row.style.cursor = 'pointer';
      
      row.addEventListener('dblclick', function(e) {
        // Make sure we're not clicking on a button or link
        if (e.target.tagName.toLowerCase() !== 'button' && 
            e.target.tagName.toLowerCase() !== 'a' && 
            e.target.tagName.toLowerCase() !== 'i' &&
            !e.target.closest('button') && 
            !e.target.closest('a')) {
          
          // Get the bmid from the row
          const bmid = this.querySelector('td:nth-child(3)').textContent.trim();
          
          // Navigate to the edit page
          window.location.href = 'balik_manggagawa_edit.php?bmid=' + bmid;
        }
      });
    });
  });
</script>

</div>
</body>
</html>

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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Confirm Delete</h3>
      <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to delete this record? This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <button class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">Delete</button>
      </div>
    </div>
  </div>
</div>
