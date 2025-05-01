<?php
require_once 'connection.php';

// This script handles renewing endorsed records
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the record ID
    $record_id = isset($_POST['record_id']) ? (int)$_POST['record_id'] : 0;
    
    $response = [
        'success' => false,
        'message' => ''
    ];
    
    if ($record_id <= 0) {
        $response['message'] = 'Invalid record ID.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Update the record's remarks from 'Endorsed' to 'Pending'
        $update_stmt = $pdo->prepare("UPDATE gov_to_gov SET remarks = 'Pending' WHERE g2g = ? AND remarks = 'Endorsed'");
        $update_stmt->execute([$record_id]);
        
        if ($update_stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Record successfully renewed.';
        } else {
            $response['message'] = 'Record not found or already renewed.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo "Method not allowed.";
    exit;
}
?>
