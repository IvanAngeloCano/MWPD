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
        'updated_count' => 0
    ];
    
    // Handle record renewal
    if ($action === 'renew_records') {
        // Get the selected IDs
        $selected_ids = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
        
        // Check if we have any selected IDs
        if (empty($selected_ids)) {
            $response['message'] = 'No records selected';
            echo json_encode($response);
            exit;
        }
        
        try {
            // Start a transaction
            $pdo->beginTransaction();
            
            // Update each selected record
            $update_count = 0;
            
            foreach ($selected_ids as $id) {
                // Sanitize the ID
                $id = (int)$id;
                
                // Update the record to move it back to regular status
                $sql = "UPDATE gov_to_gov SET 
                        remarks = '', 
                        memo_reference = NULL, 
                        employer = NULL, 
                        endorsement_date = NULL 
                        WHERE g2g = ? AND remarks = 'Endorsed'";
                        
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$id]);
                
                if ($result) {
                    $update_count += $stmt->rowCount();
                }
            }
            
            // Check if any records were updated
            if ($update_count > 0) {
                // Commit the transaction
                $pdo->commit();
                
                // Set success response
                $response['success'] = true;
                $response['message'] = "Successfully renewed $update_count record(s)";
                $response['updated_count'] = $update_count;
            } else {
                // No records were updated
                $pdo->rollBack();
                $response['message'] = 'No records were updated. Make sure the selected records are in Endorsed status.';
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
