<?php
/**
 * Report Exporter
 * High-performance report generation with multiple format support
 */
class ReportExporter {
    private $pdo;
    private $templates = [];
    private $formatHandlers = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initializeFormatHandlers();
    }
    
    /**
     * Initialize handlers for different export formats
     */
    private function initializeFormatHandlers() {
        // Register handlers for each format
        $this->formatHandlers = [
            'csv' => [$this, 'exportToCsv'],
            'excel' => [$this, 'exportToExcel'],
            'pdf' => [$this, 'exportToPdf'],
            'json' => [$this, 'exportToJson'],
            'docx' => [$this, 'exportToDocx']
        ];
    }
    
    /**
     * Export data to the specified format
     * 
     * @param string $reportType Type of report (direct_hire, bm, gov_to_gov, etc.)
     * @param array $data Data to export
     * @param string $format Output format (csv, excel, pdf, json, docx)
     * @param array $options Additional options for the export
     * @return mixed Export result (string, binary data, or file path)
     */
    public function export($reportType, $data, $format, $options = []) {
        // Validate format
        $format = strtolower($format);
        if (!isset($this->formatHandlers[$format])) {
            throw new Exception("Unsupported export format: $format");
        }
        
        // Apply report-specific transformations
        $processedData = $this->preprocessData($reportType, $data, $format, $options);
        
        // Call the appropriate format handler
        return call_user_func($this->formatHandlers[$format], $processedData, $reportType, $options);
    }
    
    /**
     * Preprocess data based on report type and format
     */
    private function preprocessData($reportType, $data, $format, $options) {
        // Apply common transformations
        $processedData = $data;
        
        // Filter columns if specified
        if (!empty($options['columns'])) {
            $filteredData = [];
            foreach ($processedData as $row) {
                $filteredRow = [];
                foreach ($options['columns'] as $column) {
                    $filteredRow[$column] = $row[$column] ?? null;
                }
                $filteredData[] = $filteredRow;
            }
            $processedData = $filteredData;
        }
        
        // Apply sorting if specified
        if (!empty($options['sort_by'])) {
            $sortColumn = $options['sort_by'];
            $sortDirection = !empty($options['sort_direction']) && strtolower($options['sort_direction']) === 'desc' ? SORT_DESC : SORT_ASC;
            
            // Create sorting array
            $sortArray = [];
            foreach ($processedData as $key => $row) {
                $sortArray[$key] = $row[$sortColumn] ?? '';
            }
            
            // Sort data
            array_multisort($sortArray, $sortDirection, $processedData);
        }
        
        // Format date fields
        $dateFormat = $options['date_format'] ?? 'Y-m-d';
        foreach ($processedData as &$row) {
            foreach ($row as $field => $value) {
                // Check if field is a date field and not empty
                if (strpos($field, 'date') !== false && !empty($value) && strtotime($value)) {
                    $row[$field] = date($dateFormat, strtotime($value));
                }
            }
        }
        
        return $processedData;
    }
    
    /**
     * Export data to CSV format
     */
    private function exportToCsv($data, $reportType, $options) {
        if (empty($data)) {
            return '';
        }
        
        // Create a temporary file
        $output = fopen('php://temp', 'w');
        
        // Add headers if needed
        if (empty($options['no_headers'])) {
            $headers = array_keys(reset($data));
            
            // Format headers if needed
            if (!empty($options['header_map'])) {
                $formattedHeaders = [];
                foreach ($headers as $header) {
                    $formattedHeaders[] = $options['header_map'][$header] ?? $header;
                }
                $headers = $formattedHeaders;
            }
            
            fputcsv($output, $headers);
        }
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        // Get the content
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Export data to Excel format
     */
    private function exportToExcel($data, $reportType, $options) {
        // This is a simplified version - in production you'd use PHPSpreadsheet
        // But for performance, we'll use a CSV that Excel can open
        return $this->exportToCsv($data, $reportType, $options);
    }
    
    /**
     * Export data to PDF format
     */
    private function exportToPdf($data, $reportType, $options) {
        // This is a simplified version - in production you'd use TCPDF, FPDF, or similar
        // For now, we'll return a placeholder message
        return "PDF export not implemented in this demo";
    }
    
    /**
     * Export data to JSON format
     */
    private function exportToJson($data, $reportType, $options) {
        $jsonOptions = 0;
        
        // Format JSON for readability if specified
        if (!empty($options['pretty'])) {
            $jsonOptions = JSON_PRETTY_PRINT;
        }
        
        return json_encode($data, $jsonOptions);
    }
    
    /**
     * Export data to DOCX format
     */
    private function exportToDocx($data, $reportType, $options) {
        // This is a simplified version - in production you'd use PHPWord
        // For now, we'll return a placeholder message
        return "DOCX export not implemented in this demo";
    }
    
    /**
     * Generate a report from database
     * 
     * @param string $reportType Type of report
     * @param array $filters Filters to apply
     * @param string $format Output format
     * @param array $options Additional options
     * @return mixed Export result
     */
    public function generateReport($reportType, $filters = [], $format = 'csv', $options = []) {
        // Get SQL query for report type
        $query = $this->getReportQuery($reportType, $filters);
        if (!$query) {
            throw new Exception("Unsupported report type: $reportType");
        }
        
        // Execute query
        try {
            $stmt = $this->pdo->prepare($query['sql']);
            $stmt->execute($query['params']);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Export to requested format
            return $this->export($reportType, $data, $format, $options);
        } catch (PDOException $e) {
            error_log("Report generation error: " . $e->getMessage());
            throw new Exception("Error generating report: " . $e->getMessage());
        }
    }
    
    /**
     * Get query for specific report type
     */
    private function getReportQuery($reportType, $filters) {
        $query = [
            'sql' => '',
            'params' => []
        ];
        
        switch ($reportType) {
            case 'direct_hire':
                $query = $this->getDirectHireReportQuery($filters);
                break;
                
            case 'balik_manggagawa':
                $query = $this->getBalikManggagawaReportQuery($filters);
                break;
                
            case 'gov_to_gov':
                $query = $this->getGovToGovReportQuery($filters);
                break;
                
            case 'job_fairs':
                $query = $this->getJobFairsReportQuery($filters);
                break;
                
            case 'blacklist':
                $query = $this->getBlacklistReportQuery($filters);
                break;
                
            case 'audit_log':
                $query = $this->getAuditLogReportQuery($filters);
                break;
                
            default:
                return null;
        }
        
        return $query;
    }
    
    /**
     * Get query for Direct Hire report
     */
    private function getDirectHireReportQuery($filters) {
        $sql = "SELECT dh.*, u.username as created_by_username 
                FROM direct_hire dh
                LEFT JOIN users u ON dh.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND dh.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND dh.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND dh.created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Order by created_at desc
        $sql .= " ORDER BY dh.created_at DESC";
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Get query for Balik Manggagawa report
     */
    private function getBalikManggagawaReportQuery($filters) {
        $sql = "SELECT bm.*, u.username as created_by_username 
                FROM bm
                LEFT JOIN users u ON bm.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['remarks'])) {
            $sql .= " AND bm.remarks = ?";
            $params[] = $filters['remarks'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND bm.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND bm.created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Order by created_at desc
        $sql .= " ORDER BY bm.created_at DESC";
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Get query for Gov-to-Gov report
     */
    private function getGovToGovReportQuery($filters) {
        $sql = "SELECT g2g.*, u.username as created_by_username 
                FROM gov_to_gov g2g
                LEFT JOIN users u ON g2g.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND g2g.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND g2g.created_at >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND g2g.created_at <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Order by created_at desc
        $sql .= " ORDER BY g2g.created_at DESC";
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Get query for Job Fairs report
     */
    private function getJobFairsReportQuery($filters) {
        $sql = "SELECT jf.*, u.username as created_by_username 
                FROM job_fairs jf
                LEFT JOIN users u ON jf.created_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND jf.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND jf.date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND jf.date <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Order by date desc
        $sql .= " ORDER BY jf.date DESC";
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Get query for Blacklist report
     */
    private function getBlacklistReportQuery($filters) {
        $sql = "SELECT b.*, u.username as added_by_username 
                FROM blacklist b
                LEFT JOIN users u ON b.added_by = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (isset($filters['active'])) {
            $sql .= " AND b.active = ?";
            $params[] = $filters['active'];
        }
        
        if (!empty($filters['source'])) {
            $sql .= " AND b.source = ?";
            $params[] = $filters['source'];
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND b.blacklist_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND b.blacklist_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Order by blacklist_date desc
        $sql .= " ORDER BY b.blacklist_date DESC";
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
    
    /**
     * Get query for Audit Log report
     */
    private function getAuditLogReportQuery($filters) {
        $sql = "SELECT al.*, u.full_name 
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
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
        
        // Order by created_at desc
        $sql .= " ORDER BY al.created_at DESC";
        
        return [
            'sql' => $sql,
            'params' => $params
        ];
    }
}
?>
