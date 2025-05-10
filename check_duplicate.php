<?php
// Minimalist duplicate check - only checks Direct Hire module
include 'session.php';
require_once 'connection.php';

// Always return JSON
header('Content-Type: application/json');

// Get input value - extremely simple
$name = isset($_POST['name']) ? $_POST['name'] : '';

// Initialize response
$response = [
    'success' => true,
    'duplicates' => [
        'found' => false,
        'matches' => []
    ]
];

if (!empty($name)) {
    try {
        // Simple query with minimal conditions
        $stmt = $pdo->prepare("SELECT id, control_no, name, jobsite, status FROM direct_hire WHERE name LIKE ?");
        $stmt->execute(["%$name%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            $response['duplicates']['found'] = true;
            foreach ($results as $record) {
                $response['duplicates']['matches'][] = [
                    'id' => $record['id'],
                    'first_name' => $record['name'],
                    'source_module' => 'direct_hire',
                    'control_number' => $record['control_no'] ?? '',
                    'jobsite' => $record['jobsite'] ?? '',
                    'status' => $record['status'] ?? '',
                    'confidence_score' => 90
                ];
            }
        }
    } catch (Exception $e) {
        // Silent error handling - never show errors to client
        error_log("Duplicate check error: " . $e->getMessage());
    }
}

// Always output a valid JSON response
echo json_encode($response);
?>
