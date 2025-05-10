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
        
        // SKIP checking for pending approvals in existing records. Allow re-submission.
        // This will ensure that records with 'Pending' remarks can be successfully
        // submitted without triggering an error message.
        //
        // We'll only add new entries to the pending_g2g_approvals table if they don't already exist
        $pending_ids = [];
        
        // Reset array keys after removing elements
        $selected_ids = array_values($selected_ids);
        
        // Check if we should update existing pending records
        $force_update = isset($_POST['force_update']) && $_POST['force_update'] == '1';
        
        if (!empty($pending_ids) && $force_update) {
            // Update existing pending approvals with new information
            $update_stmt = $pdo->prepare("UPDATE pending_g2g_approvals 
                SET employer = ?, memo_reference = ?
                WHERE g2g_id = ? AND status = 'Pending'");
                
            foreach ($pending_ids as $id) {
                $update_stmt->execute([$employer, $memo_reference, $id]);
                $response['updated_ids'][] = $id;
            }
            
            // Add these back to selected_ids so they'll be included in the success count
            $selected_ids = array_merge($selected_ids, $pending_ids);
            $pending_ids = [];
        } else if (!empty($pending_ids)) {
            $response['already_pending'] = true;
            if (empty($selected_ids)) {
                // Check if any of the records actually have pending status in the database
                // Query to see if there's at least one real conflict
                $real_conflict_check = $pdo->prepare("
                    SELECT COUNT(*) FROM pending_g2g_approvals p 
                    WHERE p.g2g_id IN (" . implode(',', array_map('intval', $pending_ids)) . ") 
                    AND p.status = 'Pending'
                ");
                $real_conflict_check->execute();
                $has_real_conflicts = ($real_conflict_check->fetchColumn() > 0);
                
                if ($has_real_conflicts) {
                    // Only show the warning if there are real conflicts in the approval system
                    $response['success'] = false;
                    $response['message'] = 'These records currently have pending approvals in the system. You can either:\n1. Select the "Update existing approvals" option to modify the current pending approvals, or\n2. Wait for the current approvals to be processed before submitting again.';
                    $response['pending_ids'] = $pending_ids;
                    $pdo->rollBack();
                    echo json_encode($response);
                    exit;
                } else {
                    // No real conflicts, treat it as if there are no pending approvals
                    // Reset pending_ids since they're not actually pending in the system
                    $pending_ids = [];
                    $selected_ids = $_POST['selected_ids']; // Restore original selection
                }
            }
        }
        
        // Insert or update records in pending approvals table
        if (!empty($selected_ids)) {
            // 1. Check if record already has a pending approval
            $check_exists_stmt = $pdo->prepare("
                SELECT approval_id FROM pending_g2g_approvals 
                WHERE g2g_id = ? AND status = 'Pending'
            ");
            
            // 2. Prepare an insert statement for new records
            $insert_stmt = $pdo->prepare("
                INSERT INTO pending_g2g_approvals 
                (g2g_id, submitted_by, employer, memo_reference, status) 
                VALUES (?, ?, ?, ?, 'Pending')
            ");
            
            // 3. Prepare an update statement for existing records
            $update_stmt = $pdo->prepare("
                UPDATE pending_g2g_approvals
                SET employer = ?, memo_reference = ?
                WHERE g2g_id = ? AND status = 'Pending'
            ");
            
            // 4. Update the gov_to_gov table to set remarks to 'Pending'
            $update_gov_stmt = $pdo->prepare("
                UPDATE gov_to_gov 
                SET remarks = 'Pending' 
                WHERE g2g = ?
            ");
            
            foreach ($selected_ids as $id) {
                // Check if the record already has a pending approval
                $check_exists_stmt->execute([$id]);
                $exists = $check_exists_stmt->rowCount() > 0;
                
                if ($exists) {
                    // Update the existing record
                    $update_stmt->execute([$employer, $memo_reference, $id]);
                } else {
                    // Insert a new record
                    $insert_stmt->execute([$id, $user_id, $employer, $memo_reference]);
                }
                
                // Always update the main table's remarks
                $update_gov_stmt->execute([$id]);
                
                $response['submitted_ids'][] = $id;
            }
            
            // Send notification to Regional Directors if available
            if (function_exists('notifyApprovalSubmitted')) {
                try {
                    // Get names of selected records
                    $names_query = $pdo->prepare("SELECT last_name, first_name FROM gov_to_gov WHERE g2g IN (" . implode(',', array_map('intval', $selected_ids)) . ")");
                    $names_query->execute();
                    $workers = $names_query->fetchAll(PDO::FETCH_ASSOC);
                    
                    $worker_names = [];
                    foreach ($workers as $worker) {
                        $worker_names[] = $worker['last_name'] . ', ' . $worker['first_name'];
                    }
                    
                    $worker_list = count($worker_names) > 2 
                        ? implode(', ', array_slice($worker_names, 0, 2)) . ' and ' . (count($worker_names) - 2) . ' more'
                        : implode(', ', $worker_names);
                    
                    // Send the notification
                    notifyApprovalSubmitted('gov_to_gov', $worker_list, 'g2g_pending_approvals.php');
                } catch (Exception $e) {
                    error_log("Error sending notification: " . $e->getMessage());
                }
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
