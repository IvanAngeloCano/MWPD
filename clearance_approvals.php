<?php
include 'session.php';
require_once 'connection.php';

// Ensure only regional directors can access this page
if ($_SESSION['role'] !== 'regional director' && $_SESSION['role'] !== 'Regional Director') {
    $_SESSION['error_message'] = "Access denied. Only Regional Directors can access this page.";
    header('Location: index.php');
    exit();
}

$pageTitle = "Clearance Approvals - Regional Director";
include '_head.php';

// Get all pending clearance approvals
try {
    $stmt = $pdo->prepare("SELECT a.id as approval_id, a.direct_hire_id, a.status as approval_status, 
                          a.submitted_by, a.approved_by, a.comments, a.created_at, a.updated_at,
                          dh.control_no, dh.name, dh.jobsite, dh.type, dh.status as record_status,
                          u.name as submitted_by_name
                          FROM direct_hire_clearance_approvals a
                          JOIN direct_hire dh ON a.direct_hire_id = dh.id
                          LEFT JOIN users u ON a.submitted_by = u.id
                          WHERE a.status = 'pending'
                          ORDER BY a.created_at DESC");
    $stmt->execute();
    $pending_approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If the approvals table doesn't exist yet, show an error
    $_SESSION['error_message'] = "Error fetching pending approvals: " . $e->getMessage();
    $pending_approvals = [];
}
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="clearance-approvals-wrapper">
        <div class="page-header">
          <div class="header-content">
            <h2>Clearance Approvals</h2>
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
          <?php if (count($pending_approvals) > 0): ?>
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
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pending_approvals as $approval): ?>
                  <tr>
                    <td><?= htmlspecialchars($approval['control_no']) ?></td>
                    <td><?= htmlspecialchars($approval['name']) ?></td>
                    <td><?= htmlspecialchars($approval['jobsite']) ?></td>
                    <td><?= ucfirst(htmlspecialchars($approval['type'])) ?></td>
                    <td><?= htmlspecialchars($approval['submitted_by_name'] ?? 'N/A') ?></td>
                    <td><?= date('M j, Y', strtotime($approval['created_at'])) ?></td>
                    <td class="actions-cell">
                      <a href="clearance_approval_view.php?id=<?= $approval['direct_hire_id'] ?>&approval_id=<?= $approval['approval_id'] ?>" class="btn btn-sm btn-primary">
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
              <p>No pending clearance approvals found.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</div>

<style>
  .clearance-approvals-wrapper {
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
