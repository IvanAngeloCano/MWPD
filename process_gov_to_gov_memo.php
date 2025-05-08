<?php
require_once 'connection.php';
require_once 'session.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Initialize the response array
    $response = [
        'success' => false,
        'message' => '',
        'memo_url' => ''
    ];
    
    // Handle status updates without generating memos
    if ($action === 'update_status') {
        // Get the selected IDs, memo reference, and employer name
        $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
        $memo_reference = isset($_POST['memo_reference']) ? trim($_POST['memo_reference']) : '';
        $employer = isset($_POST['employer']) ? trim($_POST['employer']) : '';
        
        // Check if we have all required data
        if (empty($selected_ids)) {
            $response['message'] = 'No records selected';
            echo json_encode($response);
            exit;
        }
        
        if (empty($memo_reference) || empty($employer)) {
            $response['message'] = 'Missing required information';
            echo json_encode($response);
            exit;
        }
        
        try {
            // Start a transaction
            $pdo->beginTransaction();
            
            // Update each selected record
            $update_count = 0;
            $current_date = date('Y-m-d H:i:s');
            
            foreach ($selected_ids as $id) {
                // Sanitize the ID
                $id = (int)$id;
                
                // Update the record to 'Endorsed' status
                $sql = "UPDATE gov_to_gov SET 
                        remarks = 'Endorsed', 
                        memo_reference = ?, 
                        employer = ?, 
                        endorsement_date = ? 
                        WHERE g2g = ?";
                        
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$memo_reference, $employer, $current_date, $id]);
                
                if ($result) {
                    $update_count += $stmt->rowCount();
                }
            }
            
            // Commit the transaction
            $pdo->commit();
            
            // Set success response
            $response['success'] = true;
            $response['message'] = "Successfully endorsed $update_count record(s)";
            
        } catch (PDOException $e) {
            // Rollback the transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Set error response
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
    // Handle memo generation
    else if ($action === 'generate_memo') {
        // Get the selected IDs, memo reference, and employer name
        $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
        $memo_reference = isset($_POST['memo_reference']) ? trim($_POST['memo_reference']) : '';
        $employer = isset($_POST['employer']) ? trim($_POST['employer']) : '';
        
        // Check if we have all required data
        if (empty($selected_ids)) {
            $response['message'] = 'No records selected';
            echo json_encode($response);
            exit;
        }
        
        if (empty($memo_reference) || empty($employer)) {
            $response['message'] = 'Missing required information';
            echo json_encode($response);
            exit;
        }
        
        try {
            // Start a transaction
            $pdo->beginTransaction();
            
            // Update each selected record
            $update_count = 0;
            $current_date = date('Y-m-d H:i:s');
            
            foreach ($selected_ids as $id) {
                // Sanitize the ID
                $id = (int)$id;
                
                // Update the record to 'Endorsed' status
                // Make it work even if the remarks field isn't exactly 'Approved'
                $sql = "UPDATE gov_to_gov SET 
                        remarks = 'Endorsed', 
                        memo_reference = ?, 
                        employer = ?, 
                        endorsement_date = ? 
                        WHERE g2g = ?";
                        
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$memo_reference, $employer, $current_date, $id]);
                
                if ($result) {
                    $update_count += $stmt->rowCount();
                }
            }
            
            // Check if any records were updated
            if ($update_count > 0) {
                // Instead of generating a file, we'll use our viewer page
                // Get the details of the endorsed workers to verify the update worked
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                $sql = "SELECT g2g, last_name, first_name, middle_name, passport_number FROM gov_to_gov WHERE g2g IN ($placeholders) AND remarks = 'Endorsed'";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($selected_ids);
                $endorsed_workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Create the memo URL that points to our viewer
                $memo_url = 'view_g2g_memo.php?ref=' . urlencode($memo_reference);
                
                // Log the successful endorsement
                error_log("Successfully endorsed " . count($endorsed_workers) . " record(s) with memo reference: $memo_reference");
                
                // Commit the transaction
                $pdo->commit();
                
                // Set success response
                $response['success'] = true;
                $response['message'] = "Successfully endorsed $update_count record(s)";
                $response['memo_url'] = $memo_url;
            } else {
                // No records were updated
                $pdo->rollBack();
                $response['message'] = 'No records were updated. Make sure the selected records are in approved status.';
            }
        } catch (PDOException $e) {
            // Rollback the transaction on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Set error response
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid action';
    }
    
    // Return the response
    echo json_encode($response);
} else {
    // Not a POST request
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
