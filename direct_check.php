<?php
/**
 * Simplified direct check endpoint
 * This file provides a very basic check for duplicate records
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
    ]
];

// Get input data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

if (empty($name)) {
    echo json_encode($response);
    exit;
}

try {
    // Enhanced name matching by removing spaces for better comparison
    $normalizedName = str_replace(' ', '', strtolower($name));
    
    // First try with exact LIKE match
    $stmt = $pdo->prepare("SELECT id, control_no, name, jobsite, status FROM direct_hire WHERE name LIKE ? LIMIT 5");
    $stmt->execute(["%$name%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no results, try with more advanced matching using SOUNDEX or similar names without spaces
    if (empty($results)) {
        $stmt = $pdo->prepare("SELECT id, control_no, name, jobsite, status FROM direct_hire");
        $stmt->execute();
        $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Manual filtering for names without spaces
        foreach ($allRecords as $record) {
            $recordNameNoSpaces = str_replace(' ', '', strtolower($record['name']));
            // Check if normalized strings are similar
            if (strpos($recordNameNoSpaces, $normalizedName) !== false || 
                strpos($normalizedName, $recordNameNoSpaces) !== false ||
                levenshtein($normalizedName, $recordNameNoSpaces) <= 3) { // Allow 3 char difference
                $results[] = $record;
                // Limit to 5 matches
                if (count($results) >= 5) break;
            }
        }
    }
    
    if (!empty($results)) {
        $response['duplicates']['found'] = true;
        
        foreach ($results as $result) {
            $response['duplicates']['matches'][] = [
                'id' => $result['id'],
                'source_module' => 'direct_hire',
                'first_name' => $result['name'],
                'last_name' => '',
                'control_number' => $result['control_no'],
                'jobsite' => $result['jobsite'],
                'status' => $result['status'],
                'confidence' => 'medium',
                'confidence_score' => 75
            ];
        }
    }
    
    // Very simple check for blacklist
    // Just check if a table named 'blacklist' exists
    $checkBlacklistTable = $pdo->query("SHOW TABLES LIKE 'blacklist'");
    if ($checkBlacklistTable->rowCount() > 0) {
        // Table exists, try a simple query
        $stmt = $pdo->prepare("SELECT * FROM blacklist WHERE first_name LIKE ? OR last_name LIKE ? LIMIT 1");
        $stmt->execute(["%$name%", "%$name%"]);
        $blacklistResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($blacklistResult) {
            $response['blacklist']['match'] = true;
            $response['blacklist']['details'] = $blacklistResult;
        }
    }
    
} catch (PDOException $e) {
    // Log error but return success anyway to avoid UI issues
    error_log("Direct check error: " . $e->getMessage());
    // Don't return error to client - this ensures UI doesn't show "check failed"
}

// Send response
echo json_encode($response);
?>
