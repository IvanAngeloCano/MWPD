<?php
include 'session.php';
require_once 'connection.php';

// This script handles submitting Gov-to-Gov records for approval

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected IDs and memo information
    $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    $memo_reference = isset($_POST['memo_reference']) ? $_POST['memo_reference'] : '';
    $employer = isset($_POST['employer']) ? $_POST['employer'] : '';
    
    $response = [
        'success' => false,
        'message' => '',
        'submitted_ids' => []
    ];
    
    if (empty($selected_ids)) {
        $response['message'] = 'No records selected for approval.';
        echo json_encode($response);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        $user_id = $_SESSION['user_id'] ?? 0;
        
        // Insert into pending approvals table
        $insert_stmt = $pdo->prepare("
            INSERT INTO pending_g2g_approvals 
            (g2g_id, submitted_by, employer, memo_reference, status) 
            VALUES (?, ?, ?, ?, 'Pending')
        ");
        
        foreach ($selected_ids as $id) {
            $insert_stmt->execute([$id, $user_id, $employer, $memo_reference]);
            $response['submitted_ids'][] = $id;
        }
        
        $pdo->commit();
        
        $response['success'] = true;
        $response['message'] = count($selected_ids) . ' record(s) submitted for approval.';
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
