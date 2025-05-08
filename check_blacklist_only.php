<?php
/**
 * Simple Blacklist Check Endpoint
 * Only checks if a person is on the blacklist
 */
include 'session.php';
require_once 'connection.php';
require_once 'blacklist_check.php';

// Always return JSON
header('Content-Type: application/json');

// Get input value
$name = isset($_POST['name']) ? $_POST['name'] : '';

// Initialize response - only blacklist information
$response = [
    'success' => true,
    'blacklist' => [
        'match' => false,
        'details' => null
    ]
];

if (!empty($name)) {
    try {
        // Option 1: Use existing blacklist check function
        $blacklistResult = checkBlacklist($pdo, $name);
        
        if ($blacklistResult) {
            $response['blacklist']['match'] = true;
            $response['blacklist']['details'] = $blacklistResult;
        } else {
            // Option 2: Fallback direct check if function returns nothing
            $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
            if ($stmt->rowCount() > 0) {
                // Safely check the structure
                $columnsQuery = $pdo->query("DESCRIBE blacklist");
                $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
                
                $conditions = [];
                $params = [];
                
                // Look for name-related fields
                foreach ($columns as $column) {
                    if (strpos($column, 'name') !== false || 
                        $column == 'first_name' || 
                        $column == 'last_name' || 
                        $column == 'full_name') {
                        $conditions[] = "`$column` LIKE ?";
                        $params[] = "%$name%";
                    }
                }
                
                if (!empty($conditions)) {
                    $sql = "SELECT * FROM blacklist WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $directResult = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($directResult) {
                        $response['blacklist']['match'] = true;
                        $response['blacklist']['details'] = $directResult;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silent error handling - never show errors to client
        error_log("Blacklist check error: " . $e->getMessage());
    }
}

// Always output a valid JSON response
echo json_encode($response);
?>
