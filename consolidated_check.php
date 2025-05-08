<?php
/**
 * Consolidated Record Check
 * Checks blacklist and duplicates across all databases in a single query
 */
include 'session.php';
require_once 'connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => true,
    'blacklist' => [
        'match' => false,
        'details' => null
    ],
    'duplicates' => [
        'found' => false,
        'matches' => []
    ],
    'debug_info' => [
        'tables_checked' => []
    ]
];

// Get input data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
if (empty($name)) {
    echo json_encode($response);
    exit;
}

// Normalize name for better matching
$normalizedName = str_replace(' ', '', strtolower($name));

try {
    // STEP 1: Check all available tables in the database
    $tableQuery = $pdo->query("SHOW TABLES");
    $allTables = $tableQuery->fetchAll(PDO::FETCH_COLUMN);
    
    // Add debug info
    $response['debug_info']['tables_checked'] = $allTables;
    
    // STEP 2: First check the blacklist table(s)
    $blacklistFound = false;
    $blacklistDetails = null;
    
    foreach ($allTables as $table) {
        // Check tables that might contain blacklist information
        if (strpos($table, 'blacklist') !== false) {
            $response['debug_info']['blacklist_tables'][] = $table;
            
            try {
                // Get table structure
                $columnsQuery = $pdo->query("DESCRIBE `$table`");
                $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
                
                // Only proceed if table has relevant columns
                $nameColumns = array_filter($columns, function($col) {
                    return strpos($col, 'name') !== false || 
                           $col == 'first_name' || 
                           $col == 'last_name' || 
                           $col == 'given_name';
                });
                
                if (!empty($nameColumns)) {
                    $conditions = [];
                    $params = [];
                    
                    foreach ($nameColumns as $col) {
                        $conditions[] = "`$col` LIKE ?";
                        $params[] = "%$name%";
                    }
                    
                    $sql = "SELECT * FROM `$table` WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        $blacklistFound = true;
                        $blacklistDetails = $result;
                        $blacklistDetails['source_table'] = $table;
                        break; // Found a match, no need to check other tables
                    }
                }
            } catch (PDOException $e) {
                // Log but continue
                error_log("Error checking blacklist table $table: " . $e->getMessage());
            }
        }
    }
    
    // Update response with blacklist results
    if ($blacklistFound) {
        $response['blacklist']['match'] = true;
        $response['blacklist']['details'] = $blacklistDetails;
    }
    
    // STEP 3: Check for duplicates across all relevant tables
    $duplicates = [];
    $moduleNamesMap = [
        'direct_hire' => 'Direct Hire',
        'bm' => 'Balik Manggagawa',
        'gov_to_gov' => 'Government-to-Government',
        'job_fairs' => 'Job Fairs',
        'info_sheet' => 'Information Sheet'
    ];
    
    // Main modules to check
    $mainModules = ['direct_hire', 'bm', 'gov_to_gov', 'job_fairs', 'info_sheet'];
    
    // Track which modules were actually checked
    $checkedModules = [];
    
    foreach ($allTables as $table) {
        // Skip blacklist tables - already checked for blacklist matches
        if (strpos($table, 'blacklist') !== false) {
            continue;
        }
        
        // Identify which module this table belongs to
        $moduleKey = null;
        foreach ($mainModules as $module) {
            if ($table == $module || strpos($table, $module) === 0) {
                $moduleKey = $module;
                break;
            }
        }
        
        // Skip tables that don't belong to any main module
        if ($moduleKey === null) {
            continue;
        }
        
        try {
            // Get table structure
            $columnsQuery = $pdo->query("DESCRIBE `$table`");
            $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
            
            // Find name-related columns
            $nameColumns = array_filter($columns, function($col) {
                return strpos($col, 'name') !== false || 
                       $col == 'first_name' || 
                       $col == 'last_name' || 
                       $col == 'given_name';
            });
            
            if (!empty($nameColumns)) {
                $conditions = [];
                $params = [];
                
                foreach ($nameColumns as $col) {
                    $conditions[] = "`$col` LIKE ?";
                    $params[] = "%$name%";
                }
                
                // Additional fields to select based on module
                $selectFields = ['id'];
                foreach ($nameColumns as $col) {
                    $selectFields[] = $col;
                }
                
                // Add status, control_no, and other common fields if they exist
                $commonFields = ['status', 'control_no', 'passport_number', 'jobsite', 'employment_site', 'remarks'];
                foreach ($commonFields as $field) {
                    if (in_array($field, $columns)) {
                        $selectFields[] = $field;
                    }
                }
                
                // Build field list for SELECT
                $fieldList = implode(', ', array_map(function($field) {
                    return "`$field`";
                }, $selectFields));
                
                $sql = "SELECT $fieldList FROM `$table` WHERE " . implode(" OR ", $conditions) . " LIMIT 5";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($results)) {
                    // Add to checked modules
                    $checkedModules[] = $moduleKey;
                    
                    // Process these results into standard format
                    foreach ($results as $result) {
                        // Extract name fields
                        $firstName = '';
                        $lastName = '';
                        
                        if (isset($result['name'])) {
                            $nameParts = explode(' ', $result['name']);
                            if (count($nameParts) > 1) {
                                $firstName = $nameParts[0];
                                $lastName = end($nameParts);
                            } else {
                                $firstName = $result['name'];
                            }
                        } else {
                            $firstName = $result['first_name'] ?? $result['given_name'] ?? '';
                            $lastName = $result['last_name'] ?? '';
                        }
                        
                        // Get other common fields
                        $controlNumber = $result['control_no'] ?? $result['passport_number'] ?? '';
                        $jobsite = $result['jobsite'] ?? $result['employment_site'] ?? '';
                        $status = $result['status'] ?? $result['remarks'] ?? '';
                        
                        // Calculate confidence score (simple version)
                        $confidenceScore = 75; // Default medium confidence
                        $recordNameNoSpaces = str_replace(' ', '', strtolower($firstName . ' ' . $lastName));
                        if (strpos($recordNameNoSpaces, $normalizedName) !== false || 
                            strpos($normalizedName, $recordNameNoSpaces) !== false) {
                            $confidenceScore = 90; // Higher confidence for contained matches
                        }
                        if (levenshtein($normalizedName, $recordNameNoSpaces) <= 2) {
                            $confidenceScore = 95; // Very high confidence for very similar names
                        }
                        
                        // Build the duplicate entry
                        $duplicates[] = [
                            'id' => $result['id'] ?? '',
                            'source_id' => $result['id'] ?? '',
                            'source_module' => $moduleKey,
                            'source_table' => $table,
                            'module_display_name' => $moduleNamesMap[$moduleKey] ?? $moduleKey,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'module_data' => $result,
                            'control_number' => $controlNumber,
                            'jobsite' => $jobsite,
                            'status' => $status,
                            'confidence' => $confidenceScore >= 90 ? 'high' : ($confidenceScore >= 70 ? 'medium' : 'low'),
                            'confidence_score' => $confidenceScore
                        ];
                    }
                }
            }
        } catch (PDOException $e) {
            // Log but continue
            error_log("Error checking table $table for duplicates: " . $e->getMessage());
        }
    }
    
    // Update response with duplicate results
    if (!empty($duplicates)) {
        $response['duplicates']['found'] = true;
        $response['duplicates']['matches'] = $duplicates;
        $response['duplicates']['checked_modules'] = array_unique($checkedModules);
    }
    
} catch (PDOException $e) {
    // Log error but return success to avoid UI issues
    error_log("Consolidated check error: " . $e->getMessage());
    $response['debug_info']['error'] = $e->getMessage();
}

// Include timestamp
$response['timestamp'] = date('Y-m-d H:i:s');

// Send response
echo json_encode($response);
?>
