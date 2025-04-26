<?php
include 'session.php';
require_once 'connection.php';

// Ensure only regional directors can access this page
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    $_SESSION['error_message'] = "Access denied. Only Regional Directors can access this page.";
    header('Location: index.php');
    exit();
}

$pageTitle = "Pending Approvals - Regional Director";
include '_head.php';

// Get all pending direct hire records
try {
    // Debug log
    file_put_contents('approval_debug.txt', "Attempting to fetch pending direct hires\n", FILE_APPEND);
    
    // Use direct_hire table directly to get pending records
    $stmt = $pdo->prepare("SELECT dh.id, dh.control_no, dh.name, dh.jobsite, dh.type, dh.status, 
                          dh.updated_at as submission_date, u.name as submitted_by_name
                          FROM direct_hire dh 
                          LEFT JOIN users u ON dh.created_by = u.id
                          WHERE dh.status = 'pending'
                          ORDER BY dh.updated_at DESC");
    $stmt->execute();
    $pending_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug log
    file_put_contents('approval_debug.txt', "Successfully fetched " . count($pending_records) . " records\n", FILE_APPEND);
} catch (PDOException $e) {
    // Log the error
    file_put_contents('approval_debug.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Show error message
    $_SESSION['error_message'] = "Error fetching pending approvals: " . $e->getMessage();
    $pending_records = [];
}
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="pending-approvals-wrapper">
        <div class="page-header">
          <div class="header-content">
            <h2>Pending Approvals</h2>
            <p>Review and approve direct hire clearance requests</p>
          </div>
        </div>
        
        <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="success-message">
          <i class="fa fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); endif; ?>
        
        <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="error-message">
          <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>
        
        <?php if (!empty($_SESSION['warning_message'])): ?>
        <div class="warning-message">
          <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['warning_message']) ?>
        </div>
        <?php unset($_SESSION['warning_message']); endif; ?>

        <!-- Pending Approvals List -->
        <div class="records-container">
          <?php if (count($pending_records) > 0): ?>
            <div class="records-table-wrapper">
              <table class="records-table">
                <thead>
                  <tr>
                    <th>Control No.</th>
                    <th>Name</th>
                    <th>Jobsite</th>
                    <th>Type</th>
                    <th>Submitted By</th>
                    <th>Submission Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pending_records as $record): ?>
                  <tr>
                    <td><?= htmlspecialchars($record['control_no'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($record['name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($record['jobsite'] ?? 'N/A') ?></td>
                    <td><?= ucfirst(htmlspecialchars($record['type'] ?? 'N/A')) ?></td>
                    <td><?= htmlspecialchars($record['submitted_by_name'] ?? 'N/A') ?></td>
                    <td><?= isset($record['submission_date']) ? date('M j, Y', strtotime($record['submission_date'])) : 'N/A' ?></td>
                    <td><?= htmlspecialchars($record['status'] ?? 'N/A') ?></td>
                    <td class="actions-cell">
                      <a href="clearance_approval_view.php?id=<?= $record['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-eye"></i> View
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="no-records">
              <i class="fa fa-info-circle"></i>
              <p>No pending approvals found.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</div>

<style>
  .pending-approvals-wrapper {
    padding: 20px;
  }
  
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
  }
  
  .header-content h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
  }
  
  .header-content p {
    margin: 0;
    color: #666;
  }
  
  .success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .warning-message {
    background-color: #fff3cd;
    color: #856404;
    padding: 12px 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .records-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    overflow: hidden;
  }
  
  .records-table-wrapper {
    overflow-x: auto;
  }
  
  .records-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .records-table th,
  .records-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
  }
  
  .records-table th {
    background-color: #f9f9f9;
    font-weight: 600;
    color: #333;
  }
  
  .records-table tr:hover {
    background-color: #f5f5f5;
  }
  
  .actions-cell {
    white-space: nowrap;
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
  
  .btn-sm {
    padding: 4px 8px;
    font-size: 13px;
  }
  
  .btn-primary {
    background-color: #007bff;
    border: 1px solid #007bff;
    color: white;
  }
  
  .no-records {
    padding: 30px;
    text-align: center;
    color: #666;
  }
  
  .no-records i {
    font-size: 48px;
    margin-bottom: 10px;
    color: #ccc;
  }
  
  .no-records p {
    margin: 0;
    font-size: 16px;
  }
</style>
