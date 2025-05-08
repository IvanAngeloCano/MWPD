<?php
/**
 * AJAX endpoint for real-time blacklist and duplicate checking
 */
include 'session.php';
require_once 'connection.php';
require_once 'includes/blacklist_checker.php';
require_once 'includes/duplicate_detector.php';
require_once 'includes/audit_logger.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request is valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Initialize response
$response = [
    'blacklist' => [
        'match' => false,
        'details' => null
    ],
    'duplicates' => [
        'found' => false,
        'matches' => []
    ]
];

// Initialize enhanced feature classes
$blacklistChecker = new BlacklistChecker($pdo);
$duplicateDetector = new DuplicateDetector($pdo);
$auditLogger = new AuditLogger($pdo, [
    'id' => $_SESSION['user_id'] ?? 0,
    'username' => $_SESSION['username'] ?? 'unknown',
    'role' => $_SESSION['role'] ?? 'unknown'
]);

// Extract person data from input
$personData = [
    'first_name' => $input['first_name'] ?? '',
    'last_name' => $input['last_name'] ?? '',
    'middle_name' => $input['middle_name'] ?? '',
    'date_of_birth' => !empty($input['date_of_birth']) ? $input['date_of_birth'] : null
];

// Add documents if provided
if (!empty($input['passport_number'])) {
    $personData['documents'][] = [
        'type' => 'passport',
        'number' => $input['passport_number']
    ];
}

if (!empty($input['id_number'])) {
    $personData['documents'][] = [
        'type' => 'national_id',
        'number' => $input['id_number']
    ];
}

// Add contacts if provided
if (!empty($input['email'])) {
    $personData['contacts'][] = [
        'type' => 'email',
        'value' => $input['email']
    ];
}

if (!empty($input['phone'])) {
    $personData['contacts'][] = [
        'type' => 'phone',
        'value' => $input['phone']
    ];
}

// Check if we have at least one identifier
if (empty($personData['first_name']) && empty($personData['last_name'])) {
    echo json_encode($response);
    exit;
}

// Ensure we're checking the blacklist database correctly
// Make sure we have the proper structure required by the blacklist checker
try {
    // First, make a direct database check against the blacklist table for testing
    // This ensures we're definitely checking the database
    $directCheck = false;
    try {
        // Check if the blacklist table exists first
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'blacklist'");
        if ($tableCheck->rowCount() > 0) {
            // Get the table structure to determine which columns exist
            $columnsQuery = $pdo->query("SHOW COLUMNS FROM blacklist");
            $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
            
            // Build a safe query based on available columns
            $conditions = [];
            $params = [];
            
            if (in_array('first_name', $columns) && !empty($personData['first_name'])) {
                $conditions[] = "LOWER(first_name) = LOWER(?)"; 
                $params[] = $personData['first_name'];
            }
            
            if (in_array('last_name', $columns) && !empty($personData['last_name'])) {
                $conditions[] = "LOWER(last_name) = LOWER(?)"; 
                $params[] = $personData['last_name'];
            }
            
            // Add active check if column exists
            if (in_array('active', $columns)) {
                $conditions[] = "active = 1";
            }
            
            if (!empty($conditions)) {
                $sql = "SELECT * FROM blacklist WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $directResult = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($directResult) {
                    // Direct match found in database, set flag
                    $directCheck = true;
                    
                    // If blacklist results aren't found through the class, add direct result
                    if (empty($blacklistResults['internal_matches'])) {
                        $blacklistResults['internal_matches'] = [$directResult];
                        $blacklistResults['match_found'] = true;
                    }
                }
            }
        }
    } catch (PDOException $e) {
        // Table might not exist or have different structure, continue with normal check
        error_log("Direct blacklist check error: " . $e->getMessage());
    }
    
    // Now use the blacklist checker class which has more comprehensive checks
    $blacklistResults = $blacklistChecker->checkPerson($personData);
    
    // Log the blacklist check
    try {
        // Check if blacklist_check_log table exists, if not create it
        $tableCheckStmt = $pdo->query("SHOW TABLES LIKE 'blacklist_check_log'");
        if ($tableCheckStmt->rowCount() === 0) {
            // Table doesn't exist, create it
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `blacklist_check_log` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) NOT NULL,
                  `check_date` datetime NOT NULL,
                  `first_name` varchar(100) NOT NULL,
                  `last_name` varchar(100) NOT NULL,
                  `identifier_type` varchar(50) NOT NULL,
                  `identifier_value` varchar(255) NOT NULL,
                  `module` varchar(50) NOT NULL,
                  `match_found` tinyint(1) NOT NULL DEFAULT 0,
                  `blacklist_id` int(11) DEFAULT NULL,
                  `action_taken` varchar(100) DEFAULT NULL,
                  `ip_address` varchar(45) NOT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_user` (`user_id`),
                  KEY `idx_date` (`check_date`),
                  KEY `idx_name` (`last_name`,`first_name`),
                  KEY `idx_identifier` (`identifier_type`,`identifier_value`(191))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }
        
        // Log to blacklist_check_log table
        $stmt = $pdo->prepare("
            INSERT INTO blacklist_check_log 
                (user_id, check_date, first_name, last_name, identifier_type, identifier_value, module, match_found, blacklist_id, action_taken, ip_address)
            VALUES 
                (:user_id, NOW(), :first_name, :last_name, :identifier_type, :identifier_value, :module, :match_found, :blacklist_id, :action_taken, :ip_address)
        ");
        
        $identifier_type = 'name';
        $identifier_value = ($personData['first_name'] . ' ' . $personData['last_name']);
        
        if (!empty($personData['documents'][0]['type'])) {
            $identifier_type = $personData['documents'][0]['type'];
            $identifier_value = $personData['documents'][0]['number'];
        } elseif (!empty($personData['contacts'][0]['type'])) {
            $identifier_type = $personData['contacts'][0]['type'];
            $identifier_value = $personData['contacts'][0]['value'];
        }
        
        $params = [
            ':user_id' => $_SESSION['user_id'] ?? 0,
            ':first_name' => $personData['first_name'],
            ':last_name' => $personData['last_name'],
            ':identifier_type' => $identifier_type,
            ':identifier_value' => $identifier_value,
            ':module' => $input['module'] ?? 'direct_hire',
            ':match_found' => !empty($blacklistResults['internal_matches']) || !empty($blacklistResults['external_matches']),
            ':blacklist_id' => !empty($blacklistResults['internal_matches'][0]['id']) ? $blacklistResults['internal_matches'][0]['id'] : null,
            ':action_taken' => 'Real-time check',
            ':ip_address' => $_SERVER['REMOTE_ADDR']
        ];
        
        $stmt->execute($params);
    } catch (PDOException $e) {
        // If logging fails, just continue - don't affect the main process
        error_log("Error logging blacklist check: " . $e->getMessage());
    }
    
    // Combine internal and external matches
    $matches = array_merge(
        $blacklistResults['internal_matches'] ?? [], 
        $blacklistResults['external_matches'] ?? []
    );
    
    if (!empty($matches)) {
        $response['blacklist']['match'] = true;
        $response['blacklist']['details'] = $matches[0];
    }
} catch (Exception $e) {
    // Log error but don't expose to client
    error_log("Blacklist check error: " . $e->getMessage());
}

// Check duplicates if not blacklisted or force check
if (!$response['blacklist']['match'] || ($input['check_duplicates'] ?? false)) {
    try {
        // Ensure we're checking all modules
        $modulesToCheck = ['direct_hire', 'bm', 'gov_to_gov', 'info_sheet', 'job_fairs'];
        
        // Get direct matches from the database for testing
        $directDuplicates = [];
        try {
            // Check direct_hire table
            if (!empty($personData['first_name']) || !empty($personData['last_name'])) {
                $stmt = $pdo->prepare("SELECT id, control_no as control_number, name, jobsite, status, created_at, 'direct_hire' as source_module FROM direct_hire WHERE name LIKE ? OR name LIKE ? LIMIT 5");
                $stmt->execute([
                    '%' . $personData['first_name'] . '%', 
                    '%' . $personData['last_name'] . '%'
                ]);
                $directHireMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $directDuplicates = array_merge($directDuplicates, $directHireMatches);
            }
            
            // Check balik_manggagawa table
            if (!empty($personData['first_name']) || !empty($personData['last_name'])) {
                $stmt = $pdo->prepare("SELECT id, passport_number as control_number, CONCAT(given_name, ' ', last_name) as name, remarks as status, NOW() as created_at, 'bm' as source_module FROM bm WHERE last_name LIKE ? OR given_name LIKE ? LIMIT 5");
                $stmt->execute([
                    '%' . $personData['last_name'] . '%', 
                    '%' . $personData['first_name'] . '%'
                ]);
                $bmMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $directDuplicates = array_merge($directDuplicates, $bmMatches);
            }
            
            // Check gov_to_gov table
            if (!empty($personData['first_name']) || !empty($personData['last_name'])) {
                $stmt = $pdo->prepare("SELECT id, passport_number as control_number, CONCAT(first_name, ' ', last_name) as name, employment_site as jobsite, status, created_at, 'gov_to_gov' as source_module FROM gov_to_gov WHERE last_name LIKE ? OR first_name LIKE ? LIMIT 5");
                $stmt->execute([
                    '%' . $personData['last_name'] . '%', 
                    '%' . $personData['first_name'] . '%'
                ]);
                $g2gMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $directDuplicates = array_merge($directDuplicates, $g2gMatches);
            }
        } catch (PDOException $e) {
            // Table might not exist or schema might be different
            error_log("Direct duplicate check error: " . $e->getMessage());
        }
        
        // Now use the duplicate detector class for more comprehensive checks
        $duplicateResults = $duplicateDetector->checkDuplicates(
            $personData, 
            $input['module'] ?? null, 
            $input['record_id'] ?? null,
            $modulesToCheck // Explicitly pass all modules to check
        );
        
        // If we have direct matches but no matches from the detector, add them
        if (empty($duplicateResults['duplicates']) && !empty($directDuplicates)) {
            foreach ($directDuplicates as &$directMatch) {
                $directMatch['confidence'] = 'medium';
                $directMatch['confidence_score'] = 75;
                $directMatch['source_id'] = $directMatch['id'];
            }
            $duplicateResults['duplicates'] = $directDuplicates;
            $duplicateResults['total_found'] = count($directDuplicates);
        }
        
        if (!empty($duplicateResults['duplicates'])) {
            $response['duplicates']['found'] = true;
            $response['duplicates']['matches'] = $duplicateResults['duplicates'];
        }
    } catch (Exception $e) {
        // Log error but don't expose to client
        error_log("Duplicate check error: " . $e->getMessage());
    }
}

// Return the response
echo json_encode($response);
?>
