<?php
/**
 * Data Transfer System
 * Handles cross-module data transfer when duplicates are found
 */
class DataTransfer {
    private $pdo;
    private $auditLogger;
    private $fieldMappings = [];
    
    public function __construct($pdo, $auditLogger = null) {
        $this->pdo = $pdo;
        $this->auditLogger = $auditLogger;
        $this->initializeFieldMappings();
    }
    
    /**
     * Initialize field mappings between different modules
     */
    private function initializeFieldMappings() {
        // Map fields between different modules
        // Format: [source_module][target_module][source_field] = target_field
        
        // Direct Hire to Balik Manggagawa
        $this->fieldMappings['direct_hire']['bm'] = [
            'name' => 'given_name',
            'last_name' => 'last_name',
            'gender' => 'gender',
            'date_of_birth' => 'dob',
            'passport_number' => 'passport_number',
            'address' => 'current_address',
            'contact_number' => 'contact_number',
            'email' => 'email',
            'nationality' => 'nationality'
        ];
        
        // Direct Hire to Gov-to-Gov
        $this->fieldMappings['direct_hire']['gov_to_gov'] = [
            'name' => 'first_name',
            'last_name' => 'last_name',
            'gender' => 'gender',
            'date_of_birth' => 'date_of_birth',
            'passport_number' => 'passport_number',
            'address' => 'address',
            'contact_number' => 'contact_number',
            'email' => 'email',
            'nationality' => 'nationality'
        ];
        
        // Balik Manggagawa to Direct Hire
        $this->fieldMappings['bm']['direct_hire'] = [
            'given_name' => 'name',
            'last_name' => 'last_name',
            'gender' => 'gender',
            'dob' => 'date_of_birth',
            'passport_number' => 'passport_number',
            'current_address' => 'address',
            'contact_number' => 'contact_number',
            'email' => 'email',
            'nationality' => 'nationality'
        ];
        
        // Gov-to-Gov to Direct Hire
        $this->fieldMappings['gov_to_gov']['direct_hire'] = [
            'first_name' => 'name',
            'last_name' => 'last_name',
            'gender' => 'gender',
            'date_of_birth' => 'date_of_birth',
            'passport_number' => 'passport_number',
            'address' => 'address',
            'contact_number' => 'contact_number',
            'email' => 'email',
            'nationality' => 'nationality'
        ];
    }
    
    /**
     * Transfer data from a source record to a target module
     * 
     * @param string $sourceModule Source module name
     * @param int $sourceId Source record ID
     * @param string $targetModule Target module
     * @param array $selectedFields Fields to transfer
     * @param int $userId User performing the transfer
     * @return array Transferable data
     */
    public function transferData($sourceModule, $sourceId, $targetModule, $selectedFields = [], $userId = null) {
        // Get source data
        $sourceData = $this->getModuleData($sourceModule, $sourceId);
        if (empty($sourceData)) {
            return ['success' => false, 'message' => 'Source record not found'];
        }
        
        // Check if we have field mappings for this combination
        if (empty($this->fieldMappings[$sourceModule][$targetModule])) {
            return ['success' => false, 'message' => 'No field mappings defined for this transfer'];
        }
        
        // Prepare data for transfer
        $transferData = [];
        $mappings = $this->fieldMappings[$sourceModule][$targetModule];
        
        foreach ($mappings as $sourceField => $targetField) {
            // Skip if field not selected or empty
            if (!empty($selectedFields) && !in_array($sourceField, $selectedFields)) {
                continue;
            }
            
            if (isset($sourceData[$sourceField]) && !empty($sourceData[$sourceField])) {
                $transferData[$targetField] = $sourceData[$sourceField];
            }
        }
        
        // Add metadata
        $transferData['_source_module'] = $sourceModule;
        $transferData['_source_id'] = $sourceId;
        $transferData['_transferred_by'] = $userId;
        $transferData['_transferred_at'] = date('Y-m-d H:i:s');
        
        // Log transfer if audit logger is available
        if ($this->auditLogger && $userId) {
            $this->auditLogger->log(
                'transfer',
                $targetModule,
                null,
                [
                    'source_module' => $sourceModule,
                    'source_id' => $sourceId,
                    'fields' => array_keys($transferData)
                ],
                null,
                $transferData
            );
        }
        
        return [
            'success' => true,
            'data' => $transferData,
            'source_module' => $sourceModule,
            'source_id' => $sourceId,
            'target_module' => $targetModule
        ];
    }
    
    /**
     * Get available fields that can be transferred from one module to another
     * 
     * @param string $sourceModule Source module name
     * @param string $targetModule Target module name
     * @return array Available fields with labels
     */
    public function getTransferableFields($sourceModule, $targetModule) {
        if (empty($this->fieldMappings[$sourceModule][$targetModule])) {
            return [];
        }
        
        $fields = [];
        $mappings = $this->fieldMappings[$sourceModule][$targetModule];
        
        foreach ($mappings as $sourceField => $targetField) {
            $fields[$sourceField] = [
                'source_field' => $sourceField,
                'target_field' => $targetField,
                'label' => $this->getFieldLabel($sourceField),
                'target_label' => $this->getFieldLabel($targetField)
            ];
        }
        
        return $fields;
    }
    
    /**
     * Get human-readable field label
     */
    private function getFieldLabel($field) {
        $label = str_replace('_', ' ', $field);
        return ucwords($label);
    }
    
    /**
     * Get record data from a specific module
     */
    private function getModuleData($module, $id) {
        $table = $this->getTableNameForModule($module);
        if (!$table) {
            return [];
        }
        
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Get table name for a module
     */
    private function getTableNameForModule($module) {
        $tables = [
            'direct_hire' => 'direct_hire',
            'bm' => 'bm',
            'gov_to_gov' => 'gov_to_gov',
            'job_fairs' => 'job_fairs',
            'info_sheet' => 'info_sheet'
        ];
        
        return $tables[$module] ?? null;
    }
    
    /**
     * Index a record in the applicant_index table for cross-module searching
     * 
     * @param string $module Module name
     * @param int $id Record ID
     * @param array $data Record data (optional, will be fetched if not provided)
     * @return bool Success status
     */
    public function indexApplicant($module, $id, $data = null) {
        // Get data if not provided
        if (!$data) {
            $data = $this->getModuleData($module, $id);
            if (empty($data)) {
                return false;
            }
        }
        
        // Extract standard fields based on module
        $indexData = $this->extractIndexFields($module, $data);
        if (empty($indexData['first_name']) || empty($indexData['last_name'])) {
            return false; // Required fields missing
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Check if already indexed
            $sql = "SELECT id FROM applicant_index WHERE source_module = ? AND source_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$module, $id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate data hash for change detection
            $dataHash = md5(json_encode($indexData));
            
            if ($existing) {
                // Update existing index
                $sql = "UPDATE applicant_index SET
                    first_name = ?,
                    last_name = ?,
                    middle_name = ?,
                    date_of_birth = ?,
                    gender = ?,
                    nationality = ?,
                    data_hash = ?,
                    updated_at = NOW()
                WHERE id = ?";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $indexData['first_name'],
                    $indexData['last_name'],
                    $indexData['middle_name'] ?? null,
                    $indexData['date_of_birth'] ?? null,
                    $indexData['gender'] ?? null,
                    $indexData['nationality'] ?? null,
                    $dataHash,
                    $existing['id']
                ]);
                
                $indexId = $existing['id'];
                
                // Delete existing documents and contacts
                $this->pdo->exec("DELETE FROM applicant_documents_index WHERE applicant_index_id = {$indexId}");
                $this->pdo->exec("DELETE FROM applicant_contacts_index WHERE applicant_index_id = {$indexId}");
                
            } else {
                // Insert new index
                $sql = "INSERT INTO applicant_index (
                    first_name, last_name, middle_name, date_of_birth, gender, nationality,
                    source_module, source_id, data_hash, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $indexData['first_name'],
                    $indexData['last_name'],
                    $indexData['middle_name'] ?? null,
                    $indexData['date_of_birth'] ?? null,
                    $indexData['gender'] ?? null,
                    $indexData['nationality'] ?? null,
                    $module,
                    $id,
                    $dataHash
                ]);
                
                $indexId = $this->pdo->lastInsertId();
            }
            
            // Index documents if available
            if (!empty($indexData['documents'])) {
                $docSql = "INSERT INTO applicant_documents_index (
                    applicant_index_id, document_type, document_number, source_module, source_id
                ) VALUES (?, ?, ?, ?, ?)";
                
                $docStmt = $this->pdo->prepare($docSql);
                
                foreach ($indexData['documents'] as $doc) {
                    if (!empty($doc['type']) && !empty($doc['number'])) {
                        $docStmt->execute([
                            $indexId,
                            $doc['type'],
                            $doc['number'],
                            $module,
                            $id
                        ]);
                    }
                }
            }
            
            // Index contacts if available
            if (!empty($indexData['contacts'])) {
                $contactSql = "INSERT INTO applicant_contacts_index (
                    applicant_index_id, contact_type, contact_value, source_module, source_id
                ) VALUES (?, ?, ?, ?, ?)";
                
                $contactStmt = $this->pdo->prepare($contactSql);
                
                foreach ($indexData['contacts'] as $contact) {
                    if (!empty($contact['type']) && !empty($contact['value'])) {
                        $contactStmt->execute([
                            $indexId,
                            $contact['type'],
                            $contact['value'],
                            $module,
                            $id
                        ]);
                    }
                }
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error indexing applicant: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract standard fields for indexing based on module
     */
    private function extractIndexFields($module, $data) {
        $indexData = [
            'first_name' => '',
            'last_name' => '',
            'middle_name' => null,
            'date_of_birth' => null,
            'gender' => null,
            'nationality' => null,
            'documents' => [],
            'contacts' => []
        ];
        
        switch ($module) {
            case 'direct_hire':
                // Map direct hire fields
                $indexData['first_name'] = $data['name'] ?? '';
                $indexData['last_name'] = $data['last_name'] ?? '';
                $indexData['middle_name'] = $data['middle_name'] ?? null;
                $indexData['date_of_birth'] = $data['date_of_birth'] ?? null;
                $indexData['gender'] = $data['gender'] ?? null;
                $indexData['nationality'] = $data['nationality'] ?? null;
                
                // Add passport
                if (!empty($data['passport_number'])) {
                    $indexData['documents'][] = [
                        'type' => 'passport',
                        'number' => $data['passport_number']
                    ];
                }
                
                // Add contacts
                if (!empty($data['contact_number'])) {
                    $indexData['contacts'][] = [
                        'type' => 'phone',
                        'value' => $data['contact_number']
                    ];
                }
                
                if (!empty($data['email'])) {
                    $indexData['contacts'][] = [
                        'type' => 'email',
                        'value' => $data['email']
                    ];
                }
                break;
                
            case 'bm':
                // Map Balik Manggagawa fields
                $indexData['first_name'] = $data['given_name'] ?? '';
                $indexData['last_name'] = $data['last_name'] ?? '';
                $indexData['middle_name'] = $data['middle_name'] ?? null;
                $indexData['date_of_birth'] = $data['dob'] ?? null;
                $indexData['gender'] = $data['gender'] ?? null;
                $indexData['nationality'] = $data['nationality'] ?? null;
                
                // Add passport
                if (!empty($data['passport_number'])) {
                    $indexData['documents'][] = [
                        'type' => 'passport',
                        'number' => $data['passport_number']
                    ];
                }
                
                // Add contacts
                if (!empty($data['contact_number'])) {
                    $indexData['contacts'][] = [
                        'type' => 'phone',
                        'value' => $data['contact_number']
                    ];
                }
                
                if (!empty($data['email'])) {
                    $indexData['contacts'][] = [
                        'type' => 'email',
                        'value' => $data['email']
                    ];
                }
                break;
                
            case 'gov_to_gov':
                // Map Gov-to-Gov fields
                $indexData['first_name'] = $data['first_name'] ?? '';
                $indexData['last_name'] = $data['last_name'] ?? '';
                $indexData['middle_name'] = $data['middle_name'] ?? null;
                $indexData['date_of_birth'] = $data['date_of_birth'] ?? null;
                $indexData['gender'] = $data['gender'] ?? null;
                $indexData['nationality'] = $data['nationality'] ?? null;
                
                // Add passport
                if (!empty($data['passport_number'])) {
                    $indexData['documents'][] = [
                        'type' => 'passport',
                        'number' => $data['passport_number']
                    ];
                }
                
                // Add contacts
                if (!empty($data['contact_number'])) {
                    $indexData['contacts'][] = [
                        'type' => 'phone',
                        'value' => $data['contact_number']
                    ];
                }
                
                if (!empty($data['email'])) {
                    $indexData['contacts'][] = [
                        'type' => 'email',
                        'value' => $data['email']
                    ];
                }
                break;
        }
        
        return $indexData;
    }
}
?>
