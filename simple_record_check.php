<?php
/**
 * Simple Record Check Endpoint
 * Lightweight API for checking blacklist and duplicates
 */
include 'session.php';
require_once 'connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

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
    ]
];

// Extract search parameters
$firstName = $input['first_name'] ?? '';
$lastName = $input['last_name'] ?? '';
$passport = $input['passport_number'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';

// Simple blacklist check - directly check the table without complex operations
try {
    // Build a simple query to check the blacklist table
    $conditions = [];
    $params = [];
    
    // Check if the table exists before proceeding
    $tableExists = false;
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'blacklist'");
    if ($tableCheck->rowCount() > 0) {
        $tableExists = true;
        
        // Check for basic name matching
        if (!empty($firstName)) {
            $conditions[] = "first_name LIKE ?";
            $params[] = "%$firstName%";
        }
        
        if (!empty($lastName)) {
            $conditions[] = "last_name LIKE ?";
            $params[] = "%$lastName%";
        }
        
        // Check for other identifiers if the table has extended fields
        $columnsCheck = $pdo->query("DESCRIBE blacklist");
        $columns = $columnsCheck->fetchAll(PDO::FETCH_COLUMN, 0);
        
        if (in_array('email', $columns) && !empty($email)) {
            $conditions[] = "email = ?";
            $params[] = $email;
        }
        
        if (in_array('phone', $columns) && !empty($phone)) {
            $conditions[] = "phone = ?";
            $params[] = $phone;
        }
        
        if (in_array('passport_number', $columns) && !empty($passport)) {
            $conditions[] = "passport_number = ?";
            $params[] = $passport;
        }
        
        // Run the blacklist check if we have conditions
        if (!empty($conditions)) {
            $sql = "SELECT * FROM blacklist WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $response['blacklist']['match'] = true;
                $response['blacklist']['details'] = $result;
            }
        }
    }
} catch (PDOException $e) {
    error_log("Simple blacklist check error: " . $e->getMessage());
    // Continue despite error
}

// Simple duplicate check across all modules
try {
    $duplicates = [];
    
    // Check Direct Hire
    if (!empty($firstName) || !empty($lastName)) {
        $conditions = [];
        $params = [];
        
        if (!empty($firstName)) {
            $conditions[] = "name LIKE ?";
            $params[] = "%$firstName%";
        }
        
        if (!empty($lastName)) {
            $conditions[] = "name LIKE ?";
            $params[] = "%$lastName%";
        }
        
        if (!empty($conditions)) {
            $sql = "SELECT id, control_no, name, jobsite, status, created_at FROM direct_hire 
                   WHERE " . implode(" OR ", $conditions) . " LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $dhResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($dhResults as $result) {
                $duplicates[] = [
                    'id' => $result['id'],
                    'source_id' => $result['id'],
                    'source_module' => 'direct_hire',
                    'first_name' => $result['name'],
                    'last_name' => '',
                    'module_data' => $result,
                    'control_number' => $result['control_no'],
                    'jobsite' => $result['jobsite'],
                    'status' => $result['status'],
                    'confidence' => 'medium',
                    'confidence_score' => 75
                ];
            }
        }
    }
    
    // Check Balik Manggagawa
    if (!empty($firstName) || !empty($lastName)) {
        $conditions = [];
        $params = [];
        
        if (!empty($firstName)) {
            $conditions[] = "given_name LIKE ?";
            $params[] = "%$firstName%";
        }
        
        if (!empty($lastName)) {
            $conditions[] = "last_name LIKE ?";
            $params[] = "%$lastName%";
        }
        
        if (!empty($conditions)) {
            $sql = "SELECT id, given_name, last_name, passport_number, remarks FROM bm 
                   WHERE " . implode(" OR ", $conditions) . " LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $bmResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($bmResults as $result) {
                $duplicates[] = [
                    'id' => $result['id'],
                    'source_id' => $result['id'],
                    'source_module' => 'bm',
                    'first_name' => $result['given_name'],
                    'last_name' => $result['last_name'],
                    'module_data' => $result,
                    'control_number' => $result['passport_number'],
                    'status' => $result['remarks'],
                    'confidence' => 'medium',
                    'confidence_score' => 70
                ];
            }
        }
    }
    
    // Check Gov to Gov
    if (!empty($firstName) || !empty($lastName)) {
        $conditions = [];
        $params = [];
        
        if (!empty($firstName)) {
            $conditions[] = "first_name LIKE ?";
            $params[] = "%$firstName%";
        }
        
        if (!empty($lastName)) {
            $conditions[] = "last_name LIKE ?";
            $params[] = "%$lastName%";
        }
        
        if (!empty($conditions)) {
            $sql = "SELECT id, first_name, last_name, passport_number, employment_site, status FROM gov_to_gov 
                   WHERE " . implode(" OR ", $conditions) . " LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $g2gResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($g2gResults as $result) {
                $duplicates[] = [
                    'id' => $result['id'],
                    'source_id' => $result['id'],
                    'source_module' => 'gov_to_gov',
                    'first_name' => $result['first_name'],
                    'last_name' => $result['last_name'],
                    'module_data' => $result,
                    'control_number' => $result['passport_number'],
                    'jobsite' => $result['employment_site'],
                    'status' => $result['status'],
                    'confidence' => 'medium',
                    'confidence_score' => 70
                ];
            }
        }
    }
    
    // Update response with duplicates
    if (!empty($duplicates)) {
        $response['duplicates']['found'] = true;
        $response['duplicates']['matches'] = $duplicates;
    }
} catch (PDOException $e) {
    error_log("Simple duplicate check error: " . $e->getMessage());
    // Continue despite error
}

// Return the response
echo json_encode($response);
?>
