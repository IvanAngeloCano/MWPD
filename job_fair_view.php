<?php
include 'session.php';
require_once 'connection.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: job_fairs.php?error=No job fair ID specified');
    exit();
}

$job_fair_id = (int)$_GET['id'];

try {
    // Get job fair details
    $stmt = $pdo->prepare("SELECT * FROM job_fairs WHERE id = ?");
    $stmt->execute([$job_fair_id]);
    $job_fair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job_fair) {
        throw new Exception("Job fair not found");
    }
    
    // Get employers participating in this job fair
    $employers_stmt = $pdo->prepare("
        SELECT e.*, jfe.status as participation_status
        FROM employers e
        JOIN job_fair_employers jfe ON e.id = jfe.employer_id
        WHERE jfe.job_fair_id = ?
        ORDER BY e.name ASC
    ");
    $employers_stmt->execute([$job_fair_id]);
    $employers = $employers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    header('Location: job_fairs.php?error=' . urlencode($e->getMessage()));
    exit();
}

$pageTitle = "View Job Fair - MWPD Filing System";
include '_head.php';
?>

<div class="layout-wrapper">
  <?php include '_sidebar.php'; ?>

  <div class="content-wrapper">
    <?php include '_header.php'; ?>

    <main class="main-content">
      <div class="job-fair-view-wrapper">
        <!-- Job Fair Header -->
        <div class="job-fair-header">
          <div class="header-content">
            <h1><?= htmlspecialchars($job_fair['venue']) ?></h1>
            <div class="job-fair-meta">
              <div class="meta-item">
                <i class="fa fa-calendar"></i>
                <span><?= date('F j, Y', strtotime($job_fair['date'])) ?></span>
              </div>
              <div class="meta-item">
                <span class="status-badge <?= strtolower($job_fair['status']) ?>">
                  <?= ucfirst(htmlspecialchars($job_fair['status'])) ?>
                </span>
              </div>
            </div>
          </div>
          
          <div class="header-actions">
            <a href="job_fair_edit.php?id=<?= $job_fair['id'] ?>" class="btn btn-primary">
              <i class="fa fa-edit"></i> Edit
            </a>
            <a href="javascript:void(0)" onclick="confirmDelete(<?= $job_fair['id'] ?>)" class="btn btn-danger">
              <i class="fa fa-trash"></i> Delete
            </a>
            <a href="job_fairs.php" class="btn btn-secondary">
              <i class="fa fa-arrow-left"></i> Back to List
            </a>
          </div>
        </div>
        
        <!-- Job Fair Details -->
        <div class="job-fair-details">
          <div class="details-section">
            <h2>Contact Information</h2>
            <div class="details-grid">
              <div class="detail-item">
                <div class="detail-label">Contact Numbers</div>
                <div class="detail-value">
                  <?= !empty($job_fair['contact_numbers']) ? htmlspecialchars($job_fair['contact_numbers']) : '<span class="no-data">No contact numbers provided</span>' ?>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Email for Invitations</div>
                <div class="detail-value">
                  <?= !empty($job_fair['invitation_contact_email']) ? 
                      '<a href="mailto:' . htmlspecialchars($job_fair['invitation_contact_email']) . '">' . 
                      htmlspecialchars($job_fair['invitation_contact_email']) . '</a>' : 
                      '<span class="no-data">No email provided</span>' ?>
                </div>
              </div>
            </div>
          </div>
          
          <div class="details-section">
            <h2>Notes</h2>
            <div class="notes-container">
              <?php if (!empty($job_fair['note'])): ?>
              <div class="note-content"><?= nl2br(htmlspecialchars($job_fair['note'])) ?></div>
              <?php else: ?>
              <div class="no-data">No notes available</div>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="details-section">
            <div class="section-header">
              <h2>Participating Employers</h2>
              <a href="job_fair_employer_add.php?job_fair_id=<?= $job_fair['id'] ?>" class="btn btn-sm btn-primary">
                <i class="fa fa-plus"></i> Add Employer
              </a>
            </div>
            
            <?php if (count($employers) > 0): ?>
            <div class="employers-table-wrapper">
              <table class="employers-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Industry</th>
                    <th>Contact Person</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($employers as $employer): ?>
                  <tr>
                    <td><?= htmlspecialchars($employer['name']) ?></td>
                    <td><?= htmlspecialchars($employer['industry'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($employer['contact_person'] ?? 'N/A') ?></td>
                    <td>
                      <?php if (!empty($employer['contact_number'])): ?>
                      <div><?= htmlspecialchars($employer['contact_number']) ?></div>
                      <?php endif; ?>
                      
                      <?php if (!empty($employer['email'])): ?>
                      <div><a href="mailto:<?= htmlspecialchars($employer['email']) ?>"><?= htmlspecialchars($employer['email']) ?></a></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="employer-status <?= strtolower($employer['participation_status']) ?>">
                        <?= ucfirst(htmlspecialchars($employer['participation_status'])) ?>
                      </span>
                    </td>
                    <td class="action-buttons">
                      <a href="employer_view.php?id=<?= $employer['id'] ?>" class="btn btn-sm btn-info" title="View Employer">
                        <i class="fa fa-eye"></i>
                      </a>
                      <a href="job_fair_employer_edit.php?job_fair_id=<?= $job_fair['id'] ?>&employer_id=<?= $employer['id'] ?>" class="btn btn-sm btn-primary" title="Edit Participation">
                        <i class="fa fa-edit"></i>
                      </a>
                      <a href="javascript:void(0)" onclick="confirmRemoveEmployer(<?= $job_fair['id'] ?>, <?= $employer['id'] ?>)" class="btn btn-sm btn-danger" title="Remove from Job Fair">
                        <i class="fa fa-times"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php else: ?>
            <div class="no-data-large">No employers added to this job fair yet</div>
            <?php endif; ?>
          </div>
          
          <div class="details-section">
            <div class="section-header">
              <h2>System Information</h2>
            </div>
            <div class="details-grid">
              <div class="detail-item">
                <div class="detail-label">Created</div>
                <div class="detail-value"><?= date('F j, Y, g:i a', strtotime($job_fair['created_at'])) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Last Updated</div>
                <div class="detail-value"><?= date('F j, Y, g:i a', strtotime($job_fair['updated_at'])) ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">ID</div>
                <div class="detail-value"><?= $job_fair['id'] ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
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
      <p>Are you sure you want to delete this job fair? This action cannot be undone.</p>
      <p class="modal-warning"><i class="fa fa-exclamation-triangle"></i> This will also remove all employer associations with this job fair.</p>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Remove Employer Modal -->
<div id="removeEmployerModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Confirm Remove Employer</h3>
      <button class="modal-close" onclick="closeRemoveEmployerModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to remove this employer from the job fair?</p>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeRemoveEmployerModal()">Cancel</button>
        <button class="btn btn-danger" id="confirmRemoveBtn">Remove</button>
      </div>
    </div>
  </div>
</div>

<script>
  function confirmDelete(id) {
    const modal = document.getElementById('deleteModal');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    // Set the onclick event for the confirm button
    confirmBtn.onclick = function() {
      window.location.href = 'job_fair_delete.php?id=' + id;
    };
    
    // Show the modal
    modal.style.display = 'flex';
  }
  
  function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
  }
  
  function confirmRemoveEmployer(jobFairId, employerId) {
    const modal = document.getElementById('removeEmployerModal');
    const confirmBtn = document.getElementById('confirmRemoveBtn');
    
    // Set the onclick event for the confirm button
    confirmBtn.onclick = function() {
      window.location.href = 'job_fair_employer_remove.php?job_fair_id=' + jobFairId + '&employer_id=' + employerId;
    };
    
    // Show the modal
    modal.style.display = 'flex';
  }
  
  function closeRemoveEmployerModal() {
    const modal = document.getElementById('removeEmployerModal');
    modal.style.display = 'none';
  }
  
  // Close modals when clicking outside
  window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const removeEmployerModal = document.getElementById('removeEmployerModal');
    
    if (event.target === deleteModal) {
      closeDeleteModal();
    }
    
    if (event.target === removeEmployerModal) {
      closeRemoveEmployerModal();
    }
  };
</script>

<style>
  .job-fair-view-wrapper {
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .job-fair-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
  }
  
  .header-content h1 {
    margin: 0 0 0.5rem 0;
    color: #343a40;
  }
  
  .job-fair-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #6c757d;
  }
  
  .meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .header-actions {
    display: flex;
    gap: 0.5rem;
  }
  
  .job-fair-details {
    display: flex;
    flex-direction: column;
    gap: 2rem;
  }
  
  .details-section {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    padding: 1.5rem;
  }
  
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }
  
  .details-section h2 {
    font-size: 1.25rem;
    margin: 0 0 1rem 0;
    color: #343a40;
  }
  
  .details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
  
  .detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .detail-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
  }
  
  .detail-value {
    font-size: 1rem;
  }
  
  .notes-container {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    border-left: 3px solid #dee2e6;
  }
  
  .note-content {
    white-space: pre-line;
  }
  
  .no-data {
    color: #6c757d;
    font-style: italic;
  }
  
  .no-data-large {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
    font-style: italic;
    background-color: #f8f9fa;
    border-radius: 4px;
  }
  
  .employers-table-wrapper {
    overflow-x: auto;
  }
  
  .employers-table {
    width: 100%;
    border-collapse: collapse;
  }
  
  .employers-table th,
  .employers-table td {
    padding: 0.75rem;
    border-bottom: 1px solid #dee2e6;
    text-align: left;
  }
  
  .employers-table th {
    background-color: #f8f9fa;
    font-weight: 600;
  }
  
  .employer-status {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
  }
  
  .employer-status.invited {
    background-color: #fff3cd;
    color: #664d03;
  }
  
  .employer-status.confirmed {
    background-color: #d1e7dd;
    color: #0f5132;
  }
  
  .employer-status.attended {
    background-color: #cff4fc;
    color: #055160;
  }
  
  .employer-status.cancelled {
    background-color: #f8d7da;
    color: #842029;
  }
  
  .action-buttons {
    display: flex;
    gap: 0.25rem;
  }
  
  .status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
  }
  
  .status-badge.planned {
    background-color: #cff4fc;
    color: #055160;
  }
  
  .status-badge.confirmed {
    background-color: #d1e7dd;
    color: #0f5132;
  }
  
  .status-badge.completed {
    background-color: #e2e3e5;
    color: #41464b;
  }
  
  .status-badge.cancelled {
    background-color: #f8d7da;
    color: #842029;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    font-weight: 500;
  }
  
  .btn-primary {
    background-color: #007bff;
    color: white;
  }
  
  .btn-danger {
    background-color: #dc3545;
    color: white;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-info {
    background-color: #17a2b8;
    color: white;
  }
  
  .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }
  
  /* Modal styles */
  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
  }
  
  .modal-content {
    background-color: white;
    border-radius: 8px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
  }
  
  .modal-header h3 {
    margin: 0;
  }
  
  .modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  .modal-warning {
    background-color: #fff3cd;
    color: #664d03;
    padding: 0.75rem;
    border-radius: 4px;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  
  .modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
  }
</style> 