<?php
/**
 * Duplicate Detector
 * High-performance cross-module duplicate detection with data transfer capabilities
 */
class DuplicateDetector {
    private $pdo;
    private $applicantCache = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check for duplicate applicants across all modules
     * 
     * @param array $applicantData Applicant information to check
     * @param string $sourceModule Current module
     * @param int $sourceId Current record ID (to exclude from matches)
     * @return array Found duplicates with confidence scores
     */
    public function checkDuplicates($applicantData, $sourceModule = null, $sourceId = null) {
        // Start with empty results
        $results = [
            'duplicates' => [],
            'total_found' => 0,
            'modules_checked' => []
        ];
        
        // Generate queries for different confidence levels
        $highConfidenceMatches = $this->findHighConfidenceMatches($applicantData, $sourceModule, $sourceId);
        $mediumConfidenceMatches = $this->findMediumConfidenceMatches($applicantData, $sourceModule, $sourceId);
        $lowConfidenceMatches = $this->findLowConfidenceMatches($applicantData, $sourceModule, $sourceId);
        
        // Combine results, ensuring no duplicates
        $allMatches = [];
        $foundIds = [];
        
        // Process high confidence matches first
        foreach ($highConfidenceMatches as $match) {
            $key = $match['source_module'] . '-' . $match['source_id'];
            if (!isset($foundIds[$key])) {
                $match['confidence'] = 'high';
                $match['confidence_score'] = 90 + rand(0, 10); // 90-100%
                $allMatches[] = $match;
                $foundIds[$key] = true;
                
                // Track modules checked
                if (!in_array($match['source_module'], $results['modules_checked'])) {
                    $results['modules_checked'][] = $match['source_module'];
                }
            }
        }
        
        // Process medium confidence matches
        foreach ($mediumConfidenceMatches as $match) {
            $key = $match['source_module'] . '-' . $match['source_id'];
            if (!isset($foundIds[$key])) {
                $match['confidence'] = 'medium';
                $match['confidence_score'] = 60 + rand(0, 30); // 60-90%
                $allMatches[] = $match;
                $foundIds[$key] = true;
                
                // Track modules checked
                if (!in_array($match['source_module'], $results['modules_checked'])) {
                    $results['modules_checked'][] = $match['source_module'];
                }
            }
        }
        
        // Process low confidence matches
        foreach ($lowConfidenceMatches as $match) {
            $key = $match['source_module'] . '-' . $match['source_id'];
            if (!isset($foundIds[$key])) {
                $match['confidence'] = 'low';
                $match['confidence_score'] = 30 + rand(0, 30); // 30-60%
                $allMatches[] = $match;
                $foundIds[$key] = true;
                
                // Track modules checked
                if (!in_array($match['source_module'], $results['modules_checked'])) {
                    $results['modules_checked'][] = $match['source_module'];
                }
            }
        }
        
        // Sort by confidence score
        usort($allMatches, function($a, $b) {
            return $b['confidence_score'] <=> $a['confidence_score'];
        });
        
        // Enrich matches with additional data
        foreach ($allMatches as &$match) {
            $match = $this->enrichDuplicateData($match);
        }
        
        $results['duplicates'] = $allMatches;
        $results['total_found'] = count($allMatches);
        
        return $results;
    }
    
    /**
     * Find high confidence matches (exact name + DOB or document match)
     */
    private function findHighConfidenceMatches($applicantData, $sourceModule, $sourceId) {
        $matches = [];
        $params = [];
        $conditions = [];
        
        // Skip if we don't have enough data
        if (empty($applicantData['first_name']) || empty($applicantData['last_name'])) {
            return $matches;
        }
        
        // Exact name + DOB match
        if (!empty($applicantData['date_of_birth'])) {
            $conditions[] = "(LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND date_of_birth = ?)";
            $params[] = $applicantData['first_name'];
            $params[] = $applicantData['last_name'];
            $params[] = $applicantData['date_of_birth'];
        }
        
        // Document number match (if available)
        if (!empty($applicantData['documents'])) {
            foreach ($applicantData['documents'] as $doc) {
                if (!empty($doc['type']) && !empty($doc['number'])) {
                    $docConditions[] = "EXISTS (
                        SELECT 1 FROM applicant_documents_index adi 
                        WHERE adi.applicant_index_id = ai.id 
                        AND adi.document_type = ? AND adi.document_number = ?
                    )";
                    $params[] = $doc['type'];
                    $params[] = $doc['number'];
                }
            }
            
            if (!empty($docConditions)) {
                $conditions[] = "(" . implode(" OR ", $docConditions) . ")";
            }
        }
        
        // Skip if no conditions
        if (empty($conditions)) {
            return $matches;
        }
        
        // Exclude current record
        $exclusion = "";
        if (!empty($sourceModule) && !empty($sourceId)) {
            $exclusion = "AND NOT (source_module = ? AND source_id = ?)";
            $params[] = $sourceModule;
            $params[] = $sourceId;
        }
        
        // Build and execute query
        $sql = "SELECT * FROM applicant_index 
                WHERE (" . implode(" OR ", $conditions) . ") 
                $exclusion
                LIMIT 5";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding high confidence matches: " . $e->getMessage());
        }
        
        return $matches;
    }
    
    /**
     * Find medium confidence matches (similar name + partial info match)
     */
    private function findMediumConfidenceMatches($applicantData, $sourceModule, $sourceId) {
        $matches = [];
        $params = [];
        $conditions = [];
        
        // Skip if we don't have enough data
        if (empty($applicantData['first_name']) || empty($applicantData['last_name'])) {
            return $matches;
        }
        
        // Similar name with SOUNDEX match
        $conditions[] = "(SOUNDEX(first_name) = SOUNDEX(?) AND SOUNDEX(last_name) = SOUNDEX(?))";
        $params[] = $applicantData['first_name'];
        $params[] = $applicantData['last_name'];
        
        // Add gender match if available
        if (!empty($applicantData['gender'])) {
            $conditions[] = "gender = ?";
            $params[] = $applicantData['gender'];
        }
        
        // Add nationality match if available
        if (!empty($applicantData['nationality'])) {
            $conditions[] = "nationality = ?";
            $params[] = $applicantData['nationality'];
        }
        
        // Check contacts (email, phone) if available
        if (!empty($applicantData['contacts'])) {
            foreach ($applicantData['contacts'] as $contact) {
                if (!empty($contact['type']) && !empty($contact['value'])) {
                    $contactConditions[] = "EXISTS (
                        SELECT 1 FROM applicant_contacts_index aci 
                        WHERE aci.applicant_index_id = ai.id 
                        AND aci.contact_type = ? AND aci.contact_value = ?
                    )";
                    $params[] = $contact['type'];
                    $params[] = $contact['value'];
                }
            }
            
            if (!empty($contactConditions)) {
                $conditions[] = "(" . implode(" OR ", $contactConditions) . ")";
            }
        }
        
        // Exclude current record
        $exclusion = "";
        if (!empty($sourceModule) && !empty($sourceId)) {
            $exclusion = "AND NOT (source_module = ? AND source_id = ?)";
            $params[] = $sourceModule;
            $params[] = $sourceId;
        }
        
        // Build and execute query
        $sql = "SELECT * FROM applicant_index 
                WHERE " . implode(" AND ", $conditions) . " 
                $exclusion
                LIMIT 10";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding medium confidence matches: " . $e->getMessage());
        }
        
        return $matches;
    }
    
    /**
     * Find low confidence matches (partial name match + other similarities)
     */
    private function findLowConfidenceMatches($applicantData, $sourceModule, $sourceId) {
        $matches = [];
        
        // Skip if we don't have enough data
        if (empty($applicantData['first_name']) || empty($applicantData['last_name'])) {
            return $matches;
        }
        
        // Use fulltext search
        $searchTerms = $applicantData['first_name'] . ' ' . $applicantData['last_name'];
        if (!empty($applicantData['middle_name'])) {
            $searchTerms .= ' ' . $applicantData['middle_name'];
        }
        
        // Exclusion for current record
        $exclusion = "";
        $params = [$searchTerms];
        
        if (!empty($sourceModule) && !empty($sourceId)) {
            $exclusion = "AND NOT (source_module = ? AND source_id = ?)";
            $params[] = $sourceModule;
            $params[] = $sourceId;
        }
        
        // Build and execute query
        $sql = "SELECT *, MATCH(first_name, last_name, middle_name) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance 
                FROM applicant_index 
                WHERE MATCH(first_name, last_name, middle_name) AGAINST(? IN NATURAL LANGUAGE MODE)
                $exclusion
                ORDER BY relevance DESC
                LIMIT 10";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $params = array_merge($params, $params); // Duplicate because we use the search terms twice
            $stmt->execute($params);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding low confidence matches: " . $e->getMessage());
        }
        
        return $matches;
    }
    
    /**
     * Enrich duplicate data with additional information
     */
    private function enrichDuplicateData($match) {
        // Skip if already cached
        $cacheKey = $match['source_module'] . '-' . $match['source_id'];
        if (isset($this->applicantCache[$cacheKey])) {
            return array_merge($match, $this->applicantCache[$cacheKey]);
        }
        
        // Get module-specific data based on source
        $moduleData = [];
        $availableFields = [];
        
        switch ($match['source_module']) {
            case 'direct_hire':
                $moduleData = $this->getDirectHireData($match['source_id']);
                break;
                
            case 'bm':
                $moduleData = $this->getBalikManggagawaData($match['source_id']);
                break;
                
            case 'gov_to_gov':
                $moduleData = $this->getGovToGovData($match['source_id']);
                break;
                
            case 'job_fairs':
                $moduleData = $this->getJobFairData($match['source_id']);
                break;
        }
        
        // Get available fields for transfer
        if (!empty($moduleData)) {
            // Exclude internal fields
            $excludedFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
            
            foreach ($moduleData as $field => $value) {
                if (!in_array($field, $excludedFields) && !empty($value)) {
                    $availableFields[] = $field;
                }
            }
            
            // Add module data to the match
            $match['module_data'] = $moduleData;
            $match['available_fields'] = $availableFields;
            
            // Cache for future use
            $this->applicantCache[$cacheKey] = [
                'module_data' => $moduleData,
                'available_fields' => $availableFields
            ];
        }
        
        return $match;
    }
    
    /**
     * Get Direct Hire data
     */
    private function getDirectHireData($id) {
        $sql = "SELECT * FROM direct_hire WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get Balik Manggagawa data
     */
    private function getBalikManggagawaData($id) {
        $sql = "SELECT * FROM bm WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get Gov-to-Gov data
     */
    private function getGovToGovData($id) {
        $sql = "SELECT * FROM gov_to_gov WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get Job Fair data
     */
    private function getJobFairData($id) {
        $sql = "SELECT * FROM job_fairs WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
?>
