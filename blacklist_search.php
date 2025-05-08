<?php
include 'session.php';
require_once 'connection.php';
require_once 'includes/blacklist_checker.php';
$pageTitle = "Blacklist Check - MWPD Filing System";
include '_head.php';

// Initialize the blacklist checker
$blacklistChecker = new BlacklistChecker($pdo);

// Process search form submission
$searchResults = [];
$totalResults = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_blacklist'])) {
    // Prepare person data from form
    $personData = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null
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
    
    // Perform the blacklist check
    $results = $blacklistChecker->checkPerson($personData);
    
    $searchResults = array_merge(
        $results['internal_matches'] ?? [], 
        $results['external_matches'] ?? []
    );
    
    $totalResults = count($searchResults);
}

// Check if setup has been run
$setupNeeded = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
    if ($stmt->rowCount() === 0) {
        $setupNeeded = true;
    }
} catch (PDOException $e) {
    // Log error
    error_log("Error checking for blacklist table: " . $e->getMessage());
}
?>

<body>
  <div class="layout-wrapper">
    <?php include '_sidebar.php'; ?>

    <div class="content-wrapper">
      <?php include '_header.php'; ?>

      <main class="main-content">
        <div class="container-fluid">
          <div class="page-header">
            <h1>Blacklist Check</h1>
            <p class="text-muted">Search for individuals in the blacklist database</p>
          </div>
          
          <?php if ($setupNeeded): ?>
          <div class="alert alert-warning">
            <h4><i class="fa fa-exclamation-triangle"></i> Setup Required</h4>
            <p>The blacklist database tables have not been set up yet. Please run the setup script first.</p>
            <a href="setup_enhanced_features.php" class="btn btn-warning">Run Setup</a>
          </div>
          <?php else: ?>
          
          <!-- Search Form -->
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="fa fa-search"></i> Search Blacklist</h5>
            </div>
            <div class="card-body">
              <form method="POST" class="row">
                <div class="col-md-4 mb-3">
                  <label for="first_name">First Name</label>
                  <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="last_name">Last Name</label>
                  <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                  <label for="date_of_birth">Date of Birth</label>
                  <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
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
                
                <div class="col-12">
                  <button type="submit" name="check_blacklist" class="btn btn-primary">
                    <i class="fa fa-search"></i> Check Blacklist
                  </button>
                </div>
              </form>
            </div>
          </div>
          
          <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_blacklist'])): ?>
          <!-- Search Results -->
          <div class="card">
            <div class="card-header <?= $totalResults > 0 ? 'bg-danger text-white' : 'bg-success text-white' ?>">
              <h5 class="mb-0">
                <?php if ($totalResults > 0): ?>
                  <i class="fa fa-exclamation-triangle"></i> Warning: Blacklist Matches Found
                <?php else: ?>
                  <i class="fa fa-check-circle"></i> No Blacklist Matches Found
                <?php endif; ?>
              </h5>
            </div>
            <div class="card-body">
              <?php if ($totalResults > 0): ?>
                <div class="alert alert-danger">
                  <h4><i class="fa fa-exclamation-triangle"></i> WARNING: BLACKLISTED PERSON</h4>
                  <p>We found <?= $totalResults ?> potential match(es) in the blacklist database. Please review the details below.</p>
                  <p class="mb-0"><strong>DO NOT PROCESS</strong> this person's application without consulting your Regional Director.</p>
                </div>
                
                <?php foreach ($searchResults as $index => $result): ?>
                  <div class="blacklist-result mb-4">
                    <h5><?= htmlspecialchars($result['first_name'] ?? '') ?> <?= htmlspecialchars($result['last_name'] ?? '') ?></h5>
                    <div class="row">
                      <div class="col-md-6">
                        <table class="table table-bordered table-sm">
                          <tr>
                            <th style="width: 150px">Blacklisted Since</th>
                            <td><?= isset($result['blacklist_date']) ? date('F j, Y', strtotime($result['blacklist_date'])) : 'N/A' ?></td>
                          </tr>
                          <tr>
                            <th>Reason</th>
                            <td class="text-danger"><?= htmlspecialchars($result['reason'] ?? 'Not specified') ?></td>
                          </tr>
                          <tr>
                            <th>Severity</th>
                            <td>
                              <?php 
                                $severityLevel = $result['severity_level'] ?? 1;
                                if ($severityLevel >= 3) {
                                  echo '<span class="badge badge-danger">High</span>';
                                } elseif ($severityLevel == 2) {
                                  echo '<span class="badge badge-warning">Medium</span>';
                                } else {
                                  echo '<span class="badge badge-info">Low</span>';
                                }
                              ?>
                            </td>
                          </tr>
                          <tr>
                            <th>Source</th>
                            <td><?= htmlspecialchars($result['source'] ?? 'Internal') ?></td>
                          </tr>
                          <?php if (!empty($result['api_source'])): ?>
                          <tr>
                            <th>External Source</th>
                            <td><?= htmlspecialchars($result['api_source']) ?></td>
                          </tr>
                          <?php endif; ?>
                        </table>
                      </div>
                      <div class="col-md-6">
                        <h6>Documents</h6>
                        <?php if (!empty($result['documents'])): ?>
                          <ul class="list-group">
                            <?php foreach ($result['documents'] as $doc): ?>
                              <li class="list-group-item">
                                <strong><?= htmlspecialchars(ucfirst($doc['document_type'])) ?>:</strong> 
                                <?= htmlspecialchars($doc['document_number']) ?>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <p class="text-muted">No document information available</p>
                        <?php endif; ?>
                        
                        <h6 class="mt-3">Contacts</h6>
                        <?php if (!empty($result['contacts'])): ?>
                          <ul class="list-group">
                            <?php foreach ($result['contacts'] as $contact): ?>
                              <li class="list-group-item">
                                <strong><?= htmlspecialchars(ucfirst($contact['contact_type'])) ?>:</strong> 
                                <?= htmlspecialchars($contact['contact_value']) ?>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <p class="text-muted">No contact information available</p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <?php if ($index < count($searchResults) - 1): ?>
                    <hr>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="alert alert-success">
                  <h4><i class="fa fa-check-circle"></i> No Blacklist Matches Found</h4>
                  <p>The individual was not found in the blacklist database.</p>
                  <p class="mb-0">You may proceed with processing their application.</p>
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
    .blacklist-result {
      border-radius: 4px;
      padding: 15px;
      background-color: #f8f9fa;
    }
    .badge {
      font-size: 0.9rem;
      padding: 0.35em 0.65em;
    }
    .badge-danger {
      background-color: #dc3545;
      color: white;
    }
    .badge-warning {
      background-color: #ffc107;
      color: #212529;
    }
    .badge-info {
      background-color: #17a2b8;
      color: white;
    }
  </style>
</body>
</html>
