<?php
/**
 * Blacklist Checker
 * High-performance blacklist checking class with caching and optimized queries
 */
class BlacklistChecker {
    private $pdo;
    private $cache = [];
    private $cacheExpiry = 300; // 5 minutes
    private $apiConfig = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadApiConfig();
    }
    
    /**
     * Load API configuration from secure location
     */
    private function loadApiConfig() {
        $configFile = dirname(__DIR__) . '/config/api_config.php';
        if (file_exists($configFile)) {
            $this->apiConfig = include $configFile;
        }
    }
    
    /**
     * Check if a person is blacklisted using multiple identifiers
     * 
     * @param array $personData Array with person information
     * @return array Results of blacklist check
     */
    public function checkPerson($personData) {
        // Generate a cache key based on input data
        $cacheKey = md5(json_encode($personData));
        
        // Check cache first
        if (isset($this->cache[$cacheKey]) && (time() - $this->cache[$cacheKey]['time'] < $this->cacheExpiry)) {
            return $this->cache[$cacheKey]['data'];
        }
        
        // Start with empty results
        $results = [
            'internal_matches' => [],
            'external_matches' => [],
            'match_found' => false,
            'highest_severity' => 0
        ];
        
        // Check internal blacklist
        $internalMatches = $this->checkInternalBlacklist($personData);
        if (!empty($internalMatches)) {
            $results['internal_matches'] = $internalMatches;
            $results['match_found'] = true;
            
            // Find highest severity
            foreach ($internalMatches as $match) {
                if ($match['severity_level'] > $results['highest_severity']) {
                    $results['highest_severity'] = $match['severity_level'];
                }
            }
        }
        
        // Only check external if needed and enabled
        if (empty($results['internal_matches']) && !empty($this->apiConfig['enabled_apis'])) {
            $externalMatches = $this->checkExternalBlacklists($personData);
            if (!empty($externalMatches)) {
                $results['external_matches'] = $externalMatches;
                $results['match_found'] = true;
            }
        }
        
        // Store in cache
        $this->cache[$cacheKey] = [
            'time' => time(),
            'data' => $results
        ];
        
        return $results;
    }
    
    /**
     * Check internal blacklist with optimized query
     */
    private function checkInternalBlacklist($person) {
        $matches = [];
        $params = [];
        $conditions = [];
        
        // Prepare name query with soundex matching for names
        if (!empty($person['first_name']) && !empty($person['last_name'])) {
            $conditions[] = "(LOWER(b.first_name) = LOWER(?) AND LOWER(b.last_name) = LOWER(?))";
            $params[] = $person['first_name'];
            $params[] = $person['last_name'];
            
            // Add phonetic search
            $conditions[] = "(SOUNDEX(b.first_name) = SOUNDEX(?) AND SOUNDEX(b.last_name) = SOUNDEX(?))";
            $params[] = $person['first_name'];
            $params[] = $person['last_name'];
        }
        
        // Add DOB if available
        if (!empty($person['date_of_birth'])) {
            $conditions[] = "b.date_of_birth = ?";
            $params[] = $person['date_of_birth'];
        }
        
        // Check documents (passport, ID, etc.)
        if (!empty($person['documents'])) {
            foreach ($person['documents'] as $doc) {
                if (!empty($doc['number'])) {
                    $documentConditions[] = "(bd.document_type = ? AND bd.document_number = ?)";
                    $params[] = $doc['type'];
                    $params[] = $doc['number'];
                }
            }
            if (!empty($documentConditions)) {
                $conditions[] = "EXISTS (
                    SELECT 1 FROM blacklist_documents bd 
                    WHERE bd.blacklist_id = b.id AND (" . implode(" OR ", $documentConditions) . ")
                )";
            }
        }
        
        // Check contacts (email, phone)
        if (!empty($person['contacts'])) {
            foreach ($person['contacts'] as $contact) {
                if (!empty($contact['value'])) {
                    $contactConditions[] = "(bc.contact_type = ? AND bc.contact_value = ?)";
                    $params[] = $contact['type'];
                    $params[] = $contact['value'];
                }
            }
            if (!empty($contactConditions)) {
                $conditions[] = "EXISTS (
                    SELECT 1 FROM blacklist_contacts bc 
                    WHERE bc.blacklist_id = b.id AND (" . implode(" OR ", $contactConditions) . ")
                )";
            }
        }
        
        // Only active records
        $conditions[] = "b.active = 1";
        
        // Check if blacklist not expired
        $conditions[] = "(b.blacklist_expiry IS NULL OR b.blacklist_expiry >= CURDATE())";
        
        // Build query
        $sql = "SELECT 
                b.*, 
                u.username as added_by_username
            FROM 
                blacklist b
                LEFT JOIN users u ON b.added_by = u.id
            WHERE 
                " . implode(" OR ", $conditions) . "
            ORDER BY 
                b.severity_level DESC, 
                b.blacklist_date DESC
            LIMIT 5"; // Limit to top 5 matches for performance
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get related documents and contacts for each match
            foreach ($matches as &$match) {
                $match['documents'] = $this->getBlacklistDocuments($match['id']);
                $match['contacts'] = $this->getBlacklistContacts($match['id']);
            }
        } catch (PDOException $e) {
            error_log("Blacklist check error: " . $e->getMessage());
        }
        
        return $matches;
    }
    
    /**
     * Get documents for a blacklist entry
     */
    private function getBlacklistDocuments($blacklistId) {
        $sql = "SELECT * FROM blacklist_documents WHERE blacklist_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$blacklistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get contacts for a blacklist entry
     */
    private function getBlacklistContacts($blacklistId) {
        $sql = "SELECT * FROM blacklist_contacts WHERE blacklist_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$blacklistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check external blacklists via APIs
     */
    private function checkExternalBlacklists($person) {
        $results = [];
        
        if (empty($this->apiConfig['enabled_apis'])) {
            return $results;
        }
        
        foreach ($this->apiConfig['enabled_apis'] as $apiName) {
            if (empty($this->apiConfig['apis'][$apiName])) {
                continue;
            }
            
            $api = $this->apiConfig['apis'][$apiName];
            
            // Skip if API is disabled
            if (empty($api['enabled'])) {
                continue;
            }
            
            try {
                // Simple API call example - would customize for each specific API
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api['endpoint']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'api_key' => $api['api_key'],
                    'first_name' => $person['first_name'] ?? '',
                    'last_name' => $person['last_name'] ?? '',
                    'date_of_birth' => $person['date_of_birth'] ?? '',
                    // Add passport number, national ID, etc. if available
                ]));
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout for performance
                
                $response = curl_exec($ch);
                curl_close($ch);
                
                if ($response) {
                    $data = json_decode($response, true);
                    if (!empty($data['matches'])) {
                        foreach ($data['matches'] as $match) {
                            $results[] = [
                                'api_source' => $apiName,
                                'first_name' => $match['first_name'] ?? '',
                                'last_name' => $match['last_name'] ?? '',
                                'reason' => $match['reason'] ?? 'Not specified',
                                'severity_level' => $match['severity'] ?? 1,
                                'match_confidence' => $match['confidence'] ?? 0,
                                'raw_data' => $match
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("API check error ({$apiName}): " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Add a person to the blacklist
     */
    public function addToBlacklist($personData) {
        try {
            $this->pdo->beginTransaction();
            
            // Insert main record
            $sql = "INSERT INTO blacklist (
                first_name, last_name, middle_name, date_of_birth, gender, nationality,
                reason, severity_level, blacklist_date, blacklist_expiry, source,
                api_source, notes, added_by, added_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $personData['first_name'] ?? '',
                $personData['last_name'] ?? '',
                $personData['middle_name'] ?? null,
                $personData['date_of_birth'] ?? null,
                $personData['gender'] ?? null,
                $personData['nationality'] ?? null,
                $personData['reason'] ?? '',
                $personData['severity_level'] ?? 1,
                $personData['blacklist_date'] ?? date('Y-m-d'),
                $personData['blacklist_expiry'] ?? null,
                $personData['source'] ?? 'internal',
                $personData['api_source'] ?? null,
                $personData['notes'] ?? null,
                $personData['added_by'] ?? 0
            ]);
            
            $blacklistId = $this->pdo->lastInsertId();
            
            // Add documents
            if (!empty($personData['documents'])) {
                $docSql = "INSERT INTO blacklist_documents (
                    blacklist_id, document_type, document_number, issuing_country,
                    issue_date, expiry_date, added_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $docStmt = $this->pdo->prepare($docSql);
                
                foreach ($personData['documents'] as $doc) {
                    $docStmt->execute([
                        $blacklistId,
                        $doc['type'] ?? '',
                        $doc['number'] ?? '',
                        $doc['issuing_country'] ?? null,
                        $doc['issue_date'] ?? null,
                        $doc['expiry_date'] ?? null
                    ]);
                }
            }
            
            // Add contacts
            if (!empty($personData['contacts'])) {
                $contactSql = "INSERT INTO blacklist_contacts (
                    blacklist_id, contact_type, contact_value, verified, added_at
                ) VALUES (?, ?, ?, ?, NOW())";
                
                $contactStmt = $this->pdo->prepare($contactSql);
                
                foreach ($personData['contacts'] as $contact) {
                    $contactStmt->execute([
                        $blacklistId,
                        $contact['type'] ?? '',
                        $contact['value'] ?? '',
                        $contact['verified'] ?? 0
                    ]);
                }
            }
            
            $this->pdo->commit();
            return $blacklistId;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error adding to blacklist: " . $e->getMessage());
            return false;
        }
    }
}
?>
