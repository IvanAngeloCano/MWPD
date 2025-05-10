<?php
/**
 * Helper functions for checking and handling blacklisted individuals
 */

/**
 * Get all column names for a table
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @return array Array of column names
 */
function getTableColumns($pdo, $table) {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (PDOException $e) {
        error_log("Error getting table columns: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if a person is blacklisted based on their name, passport, email, or phone
 * 
 * @param PDO $pdo Database connection
 * @param string $name Full name to check
 * @param string $passport Passport number to check (optional)
 * @param string $email Email address to check (optional)
 * @param string $phone Phone number to check (optional)
 * @return array|false Returns blacklist record if found, false otherwise
 */
function checkBlacklist($pdo, $name, $passport = '', $email = '', $phone = '') {
    // First check if the blacklist table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'blacklist'");
        if ($stmt->rowCount() === 0) {
            return false; // Table doesn't exist, no blacklist to check
        }
        
        // Build query to check for matches
        $conditions = [];
        $params = [];
        
        // Only check approved blacklist entries
        $conditions[] = "status = 'approved'";
        
        // Check name (case insensitive)
        if (!empty($name)) {
            // Add debug logging
            error_log("Checking blacklist for name: $name");
            
            // Use LIKE operator for more flexible matching
            $conditions[] = "LOWER(full_name) LIKE LOWER(?)"; 
            $params[] = "%$name%";
            
            // Also try matching against name field if it exists
            // This will be part of the OR conditions
            if (in_array('name', array_map('strtolower', getTableColumns($pdo, 'blacklist')))) {
                $conditions[] = "LOWER(name) LIKE LOWER(?)"; 
                $params[] = "%$name%";
            }
        }
        
        // Check passport if provided
        if (!empty($passport)) {
            $conditions[] = "passport_number = ?"; 
            $params[] = $passport;
        }
        
        // Check email if provided
        if (!empty($email)) {
            $conditions[] = "email = ?"; 
            $params[] = $email;
        }
        
        // Check phone if provided
        if (!empty($phone)) {
            $conditions[] = "phone = ?"; 
            $params[] = $phone;
        }
        
        // If no conditions to check, return false
        if (count($conditions) <= 1) { // Only the status condition
            return false;
        }
        
        // Build and execute the query
        $sql = "SELECT * FROM blacklist WHERE " . implode(" OR ", $conditions);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: false;
    } catch (PDOException $e) {
        // Log error silently and return false
        error_log("Blacklist check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate a warning HTML for a blacklisted person
 * 
 * @param array $blacklist_record The blacklist record
 * @return string HTML for the warning
 */
function generateBlacklistWarning($blacklist_record) {
    $html = '<div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 5px solid #dc3545;">';
    $html .= '<h4 style="margin-top: 0; color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> WARNING: BLACKLISTED PERSON</h4>';
    $html .= '<p><strong>' . htmlspecialchars($blacklist_record['full_name']) . '</strong> is on the blacklist.</p>';
    $html .= '<p><strong>Reason:</strong> ' . htmlspecialchars($blacklist_record['reason']) . '</p>';
    
    if (!empty($blacklist_record['notes'])) {
        $html .= '<p><strong>Additional Notes:</strong> ' . htmlspecialchars($blacklist_record['notes']) . '</p>';
    }
    
    $html .= '<p><strong>Date Added:</strong> ' . date('F j, Y', strtotime($blacklist_record['approved_date'])) . '</p>';
    $html .= '<p class="mb-0"><strong>DO NOT PROCESS</strong> this person\'s application without consulting your Regional Director.</p>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Show a JavaScript alert for a blacklisted person
 * 
 * @param array $blacklist_record The blacklist record
 * @return string JavaScript for the alert
 */
function generateBlacklistAlert($blacklist_record) {
    $reason = addslashes($blacklist_record['reason']);
    $name = addslashes($blacklist_record['full_name']);
    
    $js = '<script>';
    $js .= 'alert("WARNING: BLACKLISTED PERSON\\n\\n';
    $js .= $name . ' is on the blacklist.\\n';
    $js .= 'Reason: ' . $reason . '\\n\\n';
    $js .= 'DO NOT PROCESS this person\'s application without consulting your Regional Director.");';
    $js .= '</script>';
    
    return $js;
}
?>
