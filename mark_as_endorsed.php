<?php
require_once 'connection.php';

// This script handles marking records as endorsed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected IDs and memo information
    $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    $memo_reference = isset($_POST['memo_reference']) ? $_POST['memo_reference'] : '';
    $employer = isset($_POST['employer']) ? $_POST['employer'] : '';
    
    $response = [
        'success' => false,
        'message' => '',
        'endorsed_ids' => []
    ];
    
    if (empty($selected_ids)) {
        $response['message'] = 'No records selected for endorsement.';
        echo json_encode($response);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update each record's remarks to 'Endorsed'
        $update_stmt = $pdo->prepare("
            UPDATE gov_to_gov 
            SET 
                remarks = 'Endorsed',
                endorsement_date = NOW(),
                employer = ?,
                memo_reference = ?
            WHERE g2g = ?
        ");
        
        foreach ($selected_ids as $id) {
            $update_stmt->execute([$employer, $memo_reference, $id]);
            $response['endorsed_ids'][] = $id;
        }
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = count($selected_ids) . ' record(s) marked as endorsed.';
    } catch (Exception $e) {
        $pdo->rollBack();
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
