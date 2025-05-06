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
        'submitted_ids' => [],
        'rejected_ids' => [],
        'already_pending' => false
    ];
    
    if (empty($selected_ids)) {
        $response['message'] = 'No records selected for approval.';
        echo json_encode($response);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        $user_id = $_SESSION['user_id'] ?? 0;
        
        // First check if any of the selected records already have pending approvals
        $check_stmt = $pdo->prepare("
            SELECT g2g_id FROM pending_g2g_approvals 
            WHERE g2g_id = ? AND status = 'Pending'
        ");
        
        $pending_ids = [];
        foreach ($selected_ids as $key => $id) {
            $check_stmt->execute([$id]);
            if ($check_stmt->rowCount() > 0) {
                // This record already has a pending approval
                $pending_ids[] = $id;
                unset($selected_ids[$key]); // Remove from the list to be submitted
                $response['rejected_ids'][] = $id;
            }
        }
        
        // Reset array keys after removing elements
        $selected_ids = array_values($selected_ids);
        
        if (!empty($pending_ids)) {
            $response['already_pending'] = true;
            if (empty($selected_ids)) {
                // All selected records already have pending approvals
                $response['message'] = 'All selected records already have pending approvals.';
                $pdo->rollBack();
                echo json_encode($response);
                exit;
            }
        }
        
        // Insert into pending approvals table for records that don't already have pending approvals
        if (!empty($selected_ids)) {
            $insert_stmt = $pdo->prepare("
                INSERT INTO pending_g2g_approvals 
                (g2g_id, submitted_by, employer, memo_reference, status) 
                VALUES (?, ?, ?, ?, 'Pending')
            ");
            
            foreach ($selected_ids as $id) {
                $insert_stmt->execute([$id, $user_id, $employer, $memo_reference]);
                $response['submitted_ids'][] = $id;
            }
            
            $response['success'] = true;
            
            if (!empty($pending_ids)) {
                $response['message'] = count($selected_ids) . ' record(s) submitted for approval. ' . 
                                     count($pending_ids) . ' record(s) already had pending approvals.';
            } else {
                $response['message'] = count($selected_ids) . ' record(s) submitted for approval.';
            }
        }
        
        $pdo->commit();
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
