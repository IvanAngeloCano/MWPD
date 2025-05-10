<?php
require_once 'session.php';
require_once 'includes/audit_logger.php';
require_once '_head.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Set page title
$pageTitle = "System Activity Logs";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '_head.php'; ?>
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .activity-table th {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 10;
        }
        
        .activity-table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .log-module {
            font-weight: 600;
        }
        
        .log-timestamp {
            white-space: nowrap;
            color: #6c757d;
        }
        
        .log-action {
            font-weight: 500;
        }
        
        .log-details {
            max-width: 350px;
            overflow-wrap: break-word;
        }
        
        .badge-module {
            background-color: #6c757d;
        }
        
        .badge-module.direct-hire {
            background-color: #28a745;
        }
        
        .badge-module.gov-to-gov {
            background-color: #17a2b8;
        }
        
        .badge-module.balik-manggagawa {
            background-color: #fd7e14;
        }
        
        .badge-module.job-fair {
            background-color: #6f42c1;
        }
        
        .badge-module.approvals {
            background-color: #dc3545;
        }
        
        .badge-module.accounts {
            background-color: #0062cc;
        }
    </style>
</head>
<body class="bg-light">
    <?php include '_header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '_sidebar.php'; ?>
            
            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fa fa-history"></i> Detailed System Activity Logs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button id="refreshLogs" class="btn btn-sm btn-outline-secondary mr-2">
                            <i class="fa fa-sync"></i> Refresh
                        </button>
                        <button id="exportLogs" class="btn btn-sm btn-outline-primary">
                            <i class="fa fa-download"></i> Export
                        </button>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="moduleFilter">Module</label>
                                <select class="form-control" id="moduleFilter">
                                    <option value="">All Modules</option>
                                    <option value="direct-hire">Direct Hire</option>
                                    <option value="gov-to-gov">Gov-to-Gov</option>
                                    <option value="balik-manggagawa">Balik Manggagawa</option>
                                    <option value="job-fair">Job Fair</option>
                                    <option value="approvals">Approvals</option>
                                    <option value="accounts">Accounts</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="userFilter">User</label>
                                <select class="form-control" id="userFilter">
                                    <option value="">All Users</option>
                                    <?php
                                    // Get all users
                                    $db = new PDO('mysql:host=localhost;dbname=mwpd', 'root', '');
                                    $stmt = $db->prepare("SELECT id, username, fullname FROM users ORDER BY username");
                                    $stmt->execute();
                                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($users as $user) {
                                        echo "<option value=\"{$user['id']}\">{$user['fullname']} ({$user['username']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dateFrom">Date From</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="dateTo">Date To</label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="actionFilter">Action</label>
                                <select class="form-control" id="actionFilter">
                                    <option value="">All Actions</option>
                                    <option value="create">Create</option>
                                    <option value="update">Update</option>
                                    <option value="delete">Delete</option>
                                    <option value="login">Login</option>
                                    <option value="logout">Logout</option>
                                    <option value="submit">Submit</option>
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="searchFilter">Search</label>
                                <input type="text" class="form-control" id="searchFilter" placeholder="Search in details...">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button id="applyFilters" class="btn btn-primary w-100">
                                <i class="fa fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Logs Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: calc(100vh - 280px); overflow-y: auto;">
                            <table class="table table-striped table-sm activity-table">
                                <thead>
                                    <tr>
                                        <th width="8%">Module</th>
                                        <th width="15%">Timestamp</th>
                                        <th width="15%">User</th>
                                        <th width="12%">Action</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody id="activityLogsTable">
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <i class="fa fa-spinner fa-spin"></i> Loading comprehensive activity logs...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span id="logCount" class="text-muted">Showing 0 logs</span>
                    </div>
                    <nav aria-label="Activity logs pagination">
                        <ul class="pagination pagination-sm" id="logsPagination">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Variables for pagination
        let currentPage = 1;
        const logsPerPage = 50;
        let totalLogs = 0;
        
        // Load activity logs via AJAX
        function loadActivityLogs(page = 1) {
            const moduleFilter = document.getElementById('moduleFilter').value;
            const userFilter = document.getElementById('userFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const actionFilter = document.getElementById('actionFilter').value;
            const searchFilter = document.getElementById('searchFilter').value;
            
            currentPage = page;
            
            const activityLogsTable = document.getElementById('activityLogsTable');
            activityLogsTable.innerHTML = '<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading activity logs...</td></tr>';
            
            // Fetch logs from server
            fetch('get_activity_logs.php?detailed=1&page=' + page + 
                  '&module=' + encodeURIComponent(moduleFilter) +
                  '&user=' + encodeURIComponent(userFilter) +
                  '&date_from=' + encodeURIComponent(dateFrom) +
                  '&date_to=' + encodeURIComponent(dateTo) +
                  '&action=' + encodeURIComponent(actionFilter) +
                  '&search=' + encodeURIComponent(searchFilter))
                .then(response => response.json())
                .then(data => {
                    if (data.logs.length === 0) {
                        activityLogsTable.innerHTML = '<tr><td colspan="5" class="text-center">No activity logs found</td></tr>';
                        document.getElementById('logCount').textContent = 'Showing 0 logs';
                        updatePagination(0);
                        return;
                    }
                    
                    // Update total logs and pagination
                    totalLogs = data.total;
                    updatePagination(totalLogs);
                    
                    // Display logs in table
                    let tableContent = '';
                    data.logs.forEach(log => {
                        const moduleClass = getModuleClass(log.module);
                        const timestamp = formatTimestamp(log.timestamp);
                        
                        tableContent += `
                            <tr>
                                <td><span class="badge badge-module ${moduleClass}">${log.module}</span></td>
                                <td class="log-timestamp">${timestamp}</td>
                                <td>${log.username}</td>
                                <td class="log-action">${formatAction(log.action)}</td>
                                <td class="log-details">${log.details}</td>
                            </tr>
                        `;
                    });
                    
                    activityLogsTable.innerHTML = tableContent;
                    
                    // Update log count
                    const startCount = (currentPage - 1) * logsPerPage + 1;
                    const endCount = Math.min(currentPage * logsPerPage, totalLogs);
                    document.getElementById('logCount').textContent = `Showing ${startCount}-${endCount} of ${totalLogs} logs`;
                })
                .catch(error => {
                    console.error('Error loading detailed activity logs:', error);
                    activityLogsTable.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading activity logs</td></tr>';
                });
        }
        
        // Helper function to get module class for styling
        function getModuleClass(module) {
            const moduleMapping = {
                'Direct Hire': 'direct-hire',
                'Gov-to-Gov': 'gov-to-gov',
                'Balik Manggagawa': 'balik-manggagawa',
                'Job Fair': 'job-fair',
                'Approvals': 'approvals',
                'Accounts': 'accounts'
            };
            
            return moduleMapping[module] || '';
        }
        
        // Format timestamp to readable date and time
        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        
        // Format action type for display
        function formatAction(action) {
            // Convert action to title case and add icons
            const actionIcons = {
                'create': '<i class="fa fa-plus-circle text-success"></i> ',
                'update': '<i class="fa fa-edit text-primary"></i> ',
                'delete': '<i class="fa fa-trash text-danger"></i> ',
                'login': '<i class="fa fa-sign-in-alt text-info"></i> ',
                'logout': '<i class="fa fa-sign-out-alt text-secondary"></i> ',
                'submit': '<i class="fa fa-paper-plane text-primary"></i> ',
                'approve': '<i class="fa fa-check-circle text-success"></i> ',
                'reject': '<i class="fa fa-times-circle text-danger"></i> '
            };
            
            const formattedAction = action.charAt(0).toUpperCase() + action.slice(1);
            return (actionIcons[action.toLowerCase()] || '') + formattedAction;
        }
        
        // Update pagination controls
        function updatePagination(totalLogs) {
            const totalPages = Math.ceil(totalLogs / logsPerPage);
            const pagination = document.getElementById('logsPagination');
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }
            
            let paginationHTML = `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;
            
            // Show at most 5 page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, startPage + 4);
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }
            
            paginationHTML += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;
            
            pagination.innerHTML = paginationHTML;
            
            // Add event listeners to pagination links
            const pageLinks = pagination.querySelectorAll('.page-link');
            pageLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = parseInt(this.getAttribute('data-page'));
                    if (!isNaN(page) && page > 0 && page <= totalPages) {
                        loadActivityLogs(page);
                    }
                });
            });
        }
        
        // Event listener for filter button
        document.getElementById('applyFilters').addEventListener('click', function() {
            loadActivityLogs(1); // Reset to first page when applying filters
        });
        
        // Event listener for refresh button
        document.getElementById('refreshLogs').addEventListener('click', function() {
            loadActivityLogs(currentPage);
        });
        
        // Event listener for export button
        document.getElementById('exportLogs').addEventListener('click', function() {
            exportLogs();
        });
        
        // Function to export logs as CSV
        function exportLogs() {
            const moduleFilter = document.getElementById('moduleFilter').value;
            const userFilter = document.getElementById('userFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            const actionFilter = document.getElementById('actionFilter').value;
            const searchFilter = document.getElementById('searchFilter').value;
            
            // Create export URL with current filters
            const exportUrl = 'export_activity_logs.php?' +
                  'module=' + encodeURIComponent(moduleFilter) +
                  '&user=' + encodeURIComponent(userFilter) +
                  '&date_from=' + encodeURIComponent(dateFrom) +
                  '&date_to=' + encodeURIComponent(dateTo) +
                  '&action=' + encodeURIComponent(actionFilter) +
                  '&search=' + encodeURIComponent(searchFilter);
            
            // Open export URL in new window
            window.open(exportUrl, '_blank');
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date range to last 30 days
            const today = new Date();
            const thirtyDaysAgo = new Date(today);
            thirtyDaysAgo.setDate(today.getDate() - 30);
            
            document.getElementById('dateFrom').value = thirtyDaysAgo.toISOString().split('T')[0];
            document.getElementById('dateTo').value = today.toISOString().split('T')[0];
            
            // Load initial logs
            loadActivityLogs();
        });
    </script>
</body>
</html>
