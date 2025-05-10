<?php
// Super simple blacklist check
include 'session.php';
require_once 'connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get name from request
$name = isset($_POST['name']) ? trim($_POST['name']) : '';

// Only proceed with full names (containing a space or comma)
if (strpos($name, ' ') === false && strpos($name, ',') === false) {
    echo json_encode($response);
    exit;
}

// Check if name contains a comma (Last, First format)
if (strpos($name, ',') !== false) {
    $nameParts = explode(',', $name, 2);
    $lastName = trim($nameParts[0]);
    $firstName = isset($nameParts[1]) ? trim($nameParts[1]) : '';
} else {
    // Otherwise split at first space (First Last format)
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
}

// Default response (not blacklisted)
$response = [
    'success' => true,
    'blacklisted' => false,
    'details' => null
];

// Only proceed if we have a name
if (!empty($name)) {
    try {
        // First check if the blacklist table exists in the database
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'blacklist'");
        if ($tableCheck->rowCount() > 0) {
            // Get table structure to see what columns we have
            try {
                $columnsQuery = $pdo->query("DESCRIBE blacklist");
                $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN, 0);
                
                // For debugging
                error_log("Blacklist table columns: " . implode(", ", $columns));
                error_log("Checking for name: $name");
                
                // Try multiple column combinations to find a match
                $found = false;
                
                // Check 1: Try 'name' column if it exists
                if (in_array('name', $columns)) {
                    $stmt = $pdo->prepare("SELECT * FROM blacklist WHERE name LIKE ?");
                    $stmt->execute(["%$name%"]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) $found = true;
                }
                
                // Check 2: Try first_name and last_name columns if they exist
                if (!$found && in_array('first_name', $columns)) {
                    $stmt = $pdo->prepare("SELECT * FROM blacklist WHERE first_name LIKE ? OR last_name LIKE ?");
                    $stmt->execute(["%$name%", "%$name%"]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result) $found = true;
                }
                
                // Check 3: Try any column with 'name' in it
                if (!$found) {
                    $nameColumns = array_filter($columns, function($col) {
                        return strpos($col, 'name') !== false;
                    });
                    
                    if (!empty($nameColumns)) {
                        $conditions = [];
                        $params = [];
                        
                        foreach ($nameColumns as $col) {
                            $conditions[] = "$col LIKE ?"; 
                            $params[] = "%$name%";
                        }
                        
                        $sql = "SELECT * FROM blacklist WHERE " . implode(" OR ", $conditions);
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) $found = true;
                    }
                }
                
                // Check 4: Last resort - try all text columns
                if (!$found) {
                    $textColumns = [];
                    foreach ($columns as $col) {
                        $typeQuery = $pdo->query("SHOW COLUMNS FROM blacklist WHERE Field = '$col'");
                        $colData = $typeQuery->fetch(PDO::FETCH_ASSOC);
                        if (strpos(strtolower($colData['Type']), 'varchar') !== false || 
                            strpos(strtolower($colData['Type']), 'text') !== false) {
                            $textColumns[] = $col;
                        }
                    }
                    
                    if (!empty($textColumns)) {
                        $conditions = [];
                        $params = [];
                        
                        foreach ($textColumns as $col) {
                            $conditions[] = "$col LIKE ?"; 
                            $params[] = "%$name%";
                        }
                        
                        $sql = "SELECT * FROM blacklist WHERE " . implode(" OR ", $conditions);
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result) $found = true;
                    }
                }
                
                if ($found && $result) {
                    // We found a match!
                    if (count($result) > 0) {
                        // Blacklist match found - get first match details
                        $response['blacklisted'] = true;
                        $response['details'] = $result;
                        $response['message'] = 'Person appears on blacklist';
                    } else {
                        $response['blacklisted'] = false;
                        $response['message'] = 'Not blacklisted';
                    }
                }
            } catch (Exception $e) {
                error_log("Error examining blacklist table: " . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log("Basic blacklist check error: " . $e->getMessage());
        // Don't change response, we'll just return not blacklisted
    }
}

// Return response
echo json_encode($response);
?>
