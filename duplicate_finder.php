<?php
include 'session.php';
require_once 'connection.php';
require_once 'includes/duplicate_detector.php';
require_once 'includes/data_transfer.php';
$pageTitle = "Duplicate Finder - MWPD Filing System";
include '_head.php';

// Initialize the duplicate detector and data transfer
$duplicateDetector = new DuplicateDetector($pdo);
$dataTransfer = new DataTransfer($pdo);

// Process search form submission
$searchResults = [];
$totalResults = 0;
$sourceModule = null;
$sourceId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_duplicates'])) {
    // Prepare person data from form
    $personData = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
        'gender' => $_POST['gender'] ?? null,
        'nationality' => $_POST['nationality'] ?? null
    ];
    
    // Add documents if provided
    if (!empty($_POST['passport_number'])) {
        $personData['documents'][] = [
            'type' => 'passport',
            'number' => $_POST['passport_number']
        ];
    }
    
    if (!empty($_POST['id_number'])) {
        $personData['documents'][] = [
            'type' => 'national_id',
            'number' => $_POST['id_number']
        ];
    }
    
    // Add contacts if provided
    if (!empty($_POST['email'])) {
        $personData['contacts'][] = [
            'type' => 'email',
            'value' => $_POST['email']
        ];
    }
    
    if (!empty($_POST['phone'])) {
        $personData['contacts'][] = [
            'type' => 'phone',
            'value' => $_POST['phone']
        ];
    }
    
    // Get source module and ID if provided
    if (!empty($_POST['source_module'])) {
        $sourceModule = $_POST['source_module'];
        $sourceId = !empty($_POST['source_id']) ? (int)$_POST['source_id'] : null;
    }
    
    // Perform the duplicate check
    $results = $duplicateDetector->checkDuplicates($personData, $sourceModule, $sourceId);
    
    $searchResults = $results['duplicates'] ?? [];
    $totalResults = $results['total_found'] ?? 0;
}

// Process data transfer if requested
$transferResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_data'])) {
    $transferSourceModule = $_POST['transfer_source_module'] ?? '';
    $transferSourceId = !empty($_POST['transfer_source_id']) ? (int)$_POST['transfer_source_id'] : null;
    $transferTargetModule = $_POST['transfer_target_module'] ?? '';
    $selectedFields = $_POST['selected_fields'] ?? [];
    
    if (!empty($transferSourceModule) && !empty($transferSourceId) && !empty($transferTargetModule)) {
        $transferResult = $dataTransfer->transferData(
            $transferSourceModule,
            $transferSourceId,
            $transferTargetModule,
            $selectedFields,
            $_SESSION['user_id'] ?? null
        );
    }
}

// Check if setup has been run
$setupNeeded = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'applicant_index'");
    if ($stmt->rowCount() === 0) {
        $setupNeeded = true;
    }
} catch (PDOException $e) {
    // Log error
    error_log("Error checking for applicant_index table: " . $e->getMessage());
}

// Get module names for display
$moduleNames = [
    'direct_hire' => 'Direct Hire',
    'bm' => 'Balik Manggagawa',
    'gov_to_gov' => 'Gov-to-Gov',
    'job_fairs' => 'Job Fair'
];
?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="container-fluid">
          <div class="page-header">
            <h1>Duplicate Finder</h1>
            <p class="text-muted">Search for duplicate records across all modules</p>
          </div>
          
          <?php if ($setupNeeded): ?>
          <div class="alert alert-warning">
            <h4><i class="fa fa-exclamation-triangle"></i> Setup Required</h4>
            <p>The duplicate detection database tables have not been set up yet. Please run the setup script first.</p>
            <a href="setup_enhanced_features.php" class="btn btn-warning">Run Setup</a>
          </div>
          <?php else: ?>
          
          <!-- Search Form -->
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="fa fa-search"></i> Search for Duplicates</h5>
            </div>
            <div class="card-body">
              <form method="POST" class="row">
                <div class="col-md-4 mb-3">
                  <label for="first_name">First Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="last_name">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="middle_name">Middle Name</label>
                  <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="date_of_birth">Date of Birth</label>
                  <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="gender">Gender</label>
                  <select class="form-control" id="gender" name="gender">
                    <option value="">-- Select Gender --</option>
                    <option value="Male" <?= isset($_POST['gender']) && $_POST['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= isset($_POST['gender']) && $_POST['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                  </select>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="nationality">Nationality</label>
                  <input type="text" class="form-control" id="nationality" name="nationality" value="<?= htmlspecialchars($_POST['nationality'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="passport_number">Passport Number</label>
                  <input type="text" class="form-control" id="passport_number" name="passport_number" value="<?= htmlspecialchars($_POST['passport_number'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="id_number">National ID / Other ID</label>
                  <input type="text" class="form-control" id="id_number" name="id_number" value="<?= htmlspecialchars($_POST['id_number'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="email">Email Address</label>
                  <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="phone">Phone Number</label>
                  <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="source_module">Current Module (Optional)</label>
                  <select class="form-control" id="source_module" name="source_module">
                    <option value="">-- Not Applicable --</option>
                    <option value="direct_hire" <?= isset($_POST['source_module']) && $_POST['source_module'] === 'direct_hire' ? 'selected' : '' ?>>Direct Hire</option>
                    <option value="bm" <?= isset($_POST['source_module']) && $_POST['source_module'] === 'bm' ? 'selected' : '' ?>>Balik Manggagawa</option>
                    <option value="gov_to_gov" <?= isset($_POST['source_module']) && $_POST['source_module'] === 'gov_to_gov' ? 'selected' : '' ?>>Gov-to-Gov</option>
                    <option value="job_fairs" <?= isset($_POST['source_module']) && $_POST['source_module'] === 'job_fairs' ? 'selected' : '' ?>>Job Fair</option>
                  </select>
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="source_id">Record ID (Optional)</label>
                  <input type="number" class="form-control" id="source_id" name="source_id" value="<?= htmlspecialchars($_POST['source_id'] ?? '') ?>" placeholder="Only if excluding a specific record">
                </div>
                
                <div class="col-12">
                  <button type="submit" name="find_duplicates" class="btn btn-primary">
                    <i class="fa fa-search"></i> Find Duplicates
                  </button>
                </div>
              </form>
            </div>
          </div>
          
          <?php if ($transferResult): ?>
          <!-- Transfer Result -->
          <div class="alert <?= $transferResult['success'] ? 'alert-success' : 'alert-danger' ?>">
            <?php if ($transferResult['success']): ?>
              <h4><i class="fa fa-check-circle"></i> Data Transfer Successful</h4>
              <p>Successfully transferred data from <?= $moduleNames[$transferResult['source_module']] ?? $transferResult['source_module'] ?> 
                 to <?= $moduleNames[$transferResult['target_module']] ?? $transferResult['target_module'] ?>.</p>
              <p>You can now continue working with the data in the target module.</p>
            <?php else: ?>
              <h4><i class="fa fa-times-circle"></i> Data Transfer Failed</h4>
              <p><?= htmlspecialchars($transferResult['message'] ?? 'Unknown error occurred during data transfer.') ?></p>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_duplicates'])): ?>
          <!-- Search Results -->
          <div class="card">
            <div class="card-header <?= $totalResults > 0 ? 'bg-warning' : 'bg-success' ?> text-white">
              <h5 class="mb-0">
                <?php if ($totalResults > 0): ?>
                  <i class="fa fa-exclamation-circle"></i> Potential Duplicates Found
                <?php else: ?>
                  <i class="fa fa-check-circle"></i> No Duplicates Found
                <?php endif; ?>
              </h5>
            </div>
            <div class="card-body">
              <?php if ($totalResults > 0): ?>
                <div class="alert alert-warning">
                  <h4><i class="fa fa-exclamation-circle"></i> Potential Duplicates Found</h4>
                  <p>We found <?= $totalResults ?> potential duplicate(s) across <?= count($results['modules_checked'] ?? []) ?> modules.</p>
                  <p class="mb-0">Review the matches below and use the data transfer option if needed.</p>
                </div>
                
                <?php foreach ($searchResults as $index => $result): ?>
                  <div class="duplicate-result mb-4">
                    <div class="duplicate-header d-flex justify-content-between align-items-center">
                      <h5>
                        <?= htmlspecialchars($result['first_name'] ?? '') ?> <?= htmlspecialchars($result['last_name'] ?? '') ?>
                        <span class="badge ml-2 <?= $result['confidence'] === 'high' ? 'badge-danger' : ($result['confidence'] === 'medium' ? 'badge-warning' : 'badge-info') ?>">
                          <?= ucfirst($result['confidence']) ?> Match (<?= $result['confidence_score'] ?>%)
                        </span>
                      </h5>
                      <span class="module-badge badge badge-primary">
                        <?= $moduleNames[$result['source_module']] ?? $result['source_module'] ?> #<?= $result['source_id'] ?>
                      </span>
                    </div>
                    
                    <div class="row mt-3">
                      <div class="col-md-6">
                        <h6>Applicant Details</h6>
                        <table class="table table-bordered table-sm">
                          <?php if (!empty($result['module_data'])): ?>
                            <?php foreach ($result['module_data'] as $field => $value): ?>
                              <?php if (!empty($value) && !is_array($value) && $field !== 'id' && strpos($field, '_at') === false): ?>
                                <tr>
                                  <th style="width: 150px"><?= ucwords(str_replace('_', ' ', $field)) ?></th>
                                  <td><?= htmlspecialchars($value) ?></td>
                                </tr>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <tr>
                              <td colspan="2" class="text-center">Detailed information not available</td>
                            </tr>
                          <?php endif; ?>
                        </table>
                      </div>
                      
                      <div class="col-md-6">
                        <h6>Transfer Data</h6>
                        <form method="POST" action="">
                          <input type="hidden" name="transfer_source_module" value="<?= htmlspecialchars($result['source_module']) ?>">
                          <input type="hidden" name="transfer_source_id" value="<?= htmlspecialchars($result['source_id']) ?>">
                          
                          <div class="form-group">
                            <label for="transfer_target_module">Transfer To:</label>
                            <select class="form-control" id="transfer_target_module" name="transfer_target_module" required>
                              <option value="">-- Select Target Module --</option>
                              <?php foreach ($moduleNames as $moduleKey => $moduleName): ?>
                                <?php if ($moduleKey !== $result['source_module']): ?>
                                  <option value="<?= $moduleKey ?>"><?= $moduleName ?></option>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          
                          <div class="form-group">
                            <label>Select Fields to Transfer:</label>
                            <div class="select-fields">
                              <?php if (!empty($result['available_fields'])): ?>
                                <?php foreach ($result['available_fields'] as $field): ?>
                                  <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="field_<?= $index ?>_<?= $field ?>" name="selected_fields[]" value="<?= $field ?>">
                                    <label class="custom-control-label" for="field_<?= $index ?>_<?= $field ?>">
                                      <?= ucwords(str_replace('_', ' ', $field)) ?>
                                    </label>
                                  </div>
                                <?php endforeach; ?>
                              <?php else: ?>
                                <p class="text-muted">No transferable fields available</p>
                              <?php endif; ?>
                            </div>
                          </div>
                          
                          <?php if (!empty($result['available_fields'])): ?>
                            <button type="submit" name="transfer_data" class="btn btn-info btn-sm">
                              <i class="fa fa-exchange-alt"></i> Transfer Selected Data
                            </button>
                          <?php endif; ?>
                        </form>
                      </div>
                    </div>
                  </div>
                  <?php if ($index < count($searchResults) - 1): ?>
                    <hr>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="alert alert-success">
                  <h4><i class="fa fa-check-circle"></i> No Duplicates Found</h4>
                  <p>We did not find any duplicate records matching your search criteria.</p>
                  <p class="mb-0">You may proceed with creating a new record.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>

  <style>
    .duplicate-result {
      border-radius: 4px;
      padding: 15px;
      background-color: #f8f9fa;
    }
    .duplicate-header {
      padding-bottom: 10px;
      border-bottom: 1px solid #dee2e6;
    }
    .module-badge {
      font-size: 0.9rem;
    }
    .badge {
      font-size: 0.8rem;
      padding: 0.35em 0.65em;
    }
    .badge-danger {
      background-color: #dc3545;
    }
    .badge-warning {
      background-color: #ffc107;
      color: #212529;
    }
    .badge-info {
      background-color: #17a2b8;
    }
    .badge-primary {
      background-color: #007bff;
    }
    .select-fields {
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #dee2e6;
      padding: 10px;
      border-radius: 4px;
    }
    .custom-control {
      margin-bottom: 5px;
    }
    .nav-section-divider {
      height: 1px;
      background-color: rgba(255,255,255,0.1);
      margin: 10px 0;
    }
    .nav-section-title {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: rgba(255,255,255,0.5);
      padding: 0 10px;
      margin: 5px 0;
    }
  </style>
</body>
</html>
