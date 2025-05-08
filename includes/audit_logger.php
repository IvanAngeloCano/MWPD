<?php
/**
 * Audit Logger
 * High-performance audit logging system with dashboard integration
 */
class AuditLogger {
    private $pdo;
    private $batchSize = 10; // Number of logs to batch before writing to database
    private $logQueue = []; // Queue for batched logging
    private $user = null; // Current user info
    
    public function __construct($pdo, $user = null) {
        $this->pdo = $pdo;
        $this->user = $user;
        
        // Register shutdown function to ensure logs are written even on script termination
        register_shutdown_function([$this, 'flushLogs']);
    }
    
    /**
     * Log an action with all relevant details
     * 
     * @param string $action The action performed (create, update, delete, view, etc.)
     * @param string $module The module where the action was performed
     * @param mixed $recordId The ID of the affected record (if applicable)
     * @param array $details Additional details about the action
     * @param mixed $oldValues Previous values (for updates)
     * @param mixed $newValues New values (for updates)
     * @return bool Success status
     */
    public function log($action, $module, $recordId = null, $details = null, $oldValues = null, $newValues = null) {
        // Skip if no user is set
        if (empty($this->user) || empty($this->user['id'])) {
            return false;
        }
        
        // Create log entry
        $logEntry = [
            'user_id' => $this->user['id'],
            'username' => $this->user['username'] ?? '',
            'role' => $this->user['role'] ?? '',
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'details' => is_array($details) ? json_encode($details) : $details,
            'old_values' => is_array($oldValues) ? json_encode($oldValues) : $oldValues,
            'new_values' => is_array($newValues) ? json_encode($newValues) : $newValues,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Add to queue
        $this->logQueue[] = $logEntry;
        
        // Write batch if queue is full
        if (count($this->logQueue) >= $this->batchSize) {
            $this->flushLogs();
        }
        
        return true;
    }
    
    /**
     * Write all queued logs to the database
     */
    public function flushLogs() {
        if (empty($this->logQueue)) {
            return true;
        }
        
        try {
            // Prepare batch insert
            $columns = array_keys($this->logQueue[0]);
            $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $placeholderSets = implode(',', array_fill(0, count($this->logQueue), $placeholders));
            
            $sql = "INSERT INTO audit_log (" . implode(',', $columns) . ") VALUES " . $placeholderSets;
            
            // Flatten values for execution
            $values = [];
            foreach ($this->logQueue as $log) {
                foreach ($log as $value) {
                    $values[] = $value;
                }
            }
            
            // Execute batch insert
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            
            // Clear queue
            $this->logQueue = [];
            
            return true;
        } catch (PDOException $e) {
            error_log("Error writing audit logs: " . $e->getMessage());
            
            // Try individual inserts if batch fails
            $this->insertLogsIndividually();
            
            return false;
        }
    }
    
    /**
     * Fall back to individual inserts if batch fails
     */
    private function insertLogsIndividually() {
        if (empty($this->logQueue)) {
            return;
        }
        
        $sql = "INSERT INTO audit_log (
            user_id, username, role, action, module, record_id, details,
            old_values, new_values, ip_address, user_agent, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($this->logQueue as $log) {
            try {
                $stmt->execute([
                    $log['user_id'],
                    $log['username'],
                    $log['role'],
                    $log['action'],
                    $log['module'],
                    $log['record_id'],
                    $log['details'],
                    $log['old_values'],
                    $log['new_values'],
                    $log['ip_address'],
                    $log['user_agent'],
                    $log['created_at']
                ]);
            } catch (PDOException $e) {
                error_log("Error writing individual audit log: " . $e->getMessage());
            }
        }
        
        // Clear queue
        $this->logQueue = [];
    }
    
    /**
     * Get recent activity logs for dashboard display
     * 
     * @param array $filters Optional filters (user_id, module, action, etc.)
     * @param int $limit Number of logs to return
     * @param int $offset Offset for pagination
     * @return array Logs with additional formatting
     */
    public function getRecentActivity($filters = [], $limit = 10, $offset = 0) {
        // Start with base query
        $sql = "SELECT al.*, u.full_name 
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Add filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['module'])) {
            $sql .= " AND al.module = ?";
            $params[] = $filters['module'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND al.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND al.created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Add order and limit
        $sql .= " ORDER BY al.created_at DESC LIMIT ?, ?";
        $params[] = (int)$offset;
        $params[] = (int)$limit;
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format logs for display
            foreach ($logs as &$log) {
                // Format action for display
                $log['action_display'] = ucfirst($log['action']);
                
                // Format module for display
                $log['module_display'] = str_replace('_', ' ', ucfirst($log['module']));
                
                // Format time differences
                $log['time_elapsed'] = $this->timeElapsed($log['created_at']);
                
                // Decode JSON fields
                if (!empty($log['details'])) {
                    $log['details_array'] = json_decode($log['details'], true);
                }
                
                if (!empty($log['old_values'])) {
                    $log['old_values_array'] = json_decode($log['old_values'], true);
                }
                
                if (!empty($log['new_values'])) {
                    $log['new_values_array'] = json_decode($log['new_values'], true);
                }
                
                // Generate activity description
                $log['activity_description'] = $this->generateActivityDescription($log);
            }
            
            return $logs;
        } catch (PDOException $e) {
            error_log("Error getting audit logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate human-readable activity description
     */
    private function generateActivityDescription($log) {
        $description = "";
        
        $user = !empty($log['full_name']) ? $log['full_name'] : $log['username'];
        $action = strtolower($log['action']);
        $module = strtolower(str_replace('_', ' ', $log['module']));
        
        switch ($action) {
            case 'create':
                $description = "$user created a new $module";
                if ($log['record_id']) {
                    $description .= " (#" . $log['record_id'] . ")";
                }
                break;
                
            case 'update':
                $description = "$user updated $module";
                if ($log['record_id']) {
                    $description .= " #" . $log['record_id'];
                }
                if (!empty($log['details_array']['fields'])) {
                    $description .= " - modified: " . implode(', ', $log['details_array']['fields']);
                }
                break;
                
            case 'delete':
                $description = "$user deleted $module";
                if ($log['record_id']) {
                    $description .= " #" . $log['record_id'];
                }
                break;
                
            case 'view':
                $description = "$user viewed $module";
                if ($log['record_id']) {
                    $description .= " #" . $log['record_id'];
                }
                break;
                
            case 'export':
                $description = "$user exported $module data";
                if (!empty($log['details_array']['format'])) {
                    $description .= " as " . strtoupper($log['details_array']['format']);
                }
                break;
                
            case 'login':
                $description = "$user logged in to the system";
                break;
                
            case 'logout':
                $description = "$user logged out of the system";
                break;
                
            default:
                $description = "$user performed $action on $module";
                if ($log['record_id']) {
                    $description .= " #" . $log['record_id'];
                }
        }
        
        return $description;
    }
    
    /**
     * Format time elapsed since a timestamp
     */
    private function timeElapsed($datetime) {
        $now = new DateTime();
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        
        $string = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        
        if (!empty($string)) {
            return reset($string) . ' ago';
        }
        
        return 'just now';
    }
    
    /**
     * Export audit logs to various formats
     */
    public function exportLogs($filters = [], $format = 'csv') {
        // Get logs with filters but no limit
        $sql = "SELECT 
                al.id, al.username, al.role, al.action, al.module, al.record_id, 
                al.details, al.ip_address, al.created_at, u.full_name
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Add filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['module'])) {
            $sql .= " AND al.module = ?";
            $params[] = $filters['module'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND al.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND al.created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Order by created_at
        $sql .= " ORDER BY al.created_at DESC";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process based on format
            switch (strtolower($format)) {
                case 'csv':
                    return $this->exportToCsv($logs);
                case 'json':
                    return $this->exportToJson($logs);
                case 'excel':
                    return $this->exportToExcel($logs);
                case 'pdf':
                    return $this->exportToPdf($logs);
                case 'docx':
                    return $this->exportToDocx($logs);
                default:
                    return false;
            }
        } catch (PDOException $e) {
            error_log("Error exporting audit logs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Export to CSV format
     */
    private function exportToCsv($logs) {
        if (empty($logs)) {
            return false;
        }
        
        // Output headers
        $output = fopen('php://temp', 'w');
        
        // Add header row
        fputcsv($output, array_keys(reset($logs)));
        
        // Add data rows
        foreach ($logs as $log) {
            fputcsv($output, $log);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Export to JSON format
     */
    private function exportToJson($logs) {
        return json_encode($logs);
    }
    
    /**
     * Export to Excel format (requires PHPSpreadsheet)
     */
    private function exportToExcel($logs) {
        // Placeholder - would implement with PHPSpreadsheet
        return false;
    }
    
    /**
     * Export to PDF format (requires TCPDF)
     */
    private function exportToPdf($logs) {
        // Placeholder - would implement with TCPDF
        return false;
    }
    
    /**
     * Export to DOCX format (requires PHPWord)
     */
    private function exportToDocx($logs) {
        // Placeholder - would implement with PHPWord
        return false;
    }
}
?>
