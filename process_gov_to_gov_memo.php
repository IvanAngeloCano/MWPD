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
    
    // Handle memo generation
    if ($action === 'generate_memo') {
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
                        WHERE g2g = ? AND remarks = 'Approved'";
                        
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$memo_reference, $employer, $current_date, $id]);
                
                if ($result) {
                    $update_count += $stmt->rowCount();
                }
            }
            
            // Check if any records were updated
            if ($update_count > 0) {
                // Generate a simple memo file (placeholder)
                $memo_filename = 'memo_' . date('YmdHis') . '.pdf';
                $memo_path = 'generated_files/' . $memo_filename;
                
                // Create the memos directory if it doesn't exist
                if (!file_exists('generated_files')) {
                    mkdir('generated_files', 0755, true);
                }
                
                // For now, we'll create a placeholder text file
                // In a real implementation, you would use a library like TCPDF or PHPWord to generate a proper document
                $memo_content = "MEMO REFERENCE: $memo_reference\n";
                $memo_content .= "EMPLOYER: $employer\n";
                $memo_content .= "DATE: " . date('F d, Y') . "\n\n";
                $memo_content .= "This is to certify that the following Gov-to-Gov workers have been endorsed:\n\n";
                
                // Get the details of the endorsed workers
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                $sql = "SELECT g2g, last_name, first_name, middle_name, passport_number FROM gov_to_gov WHERE g2g IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($selected_ids);
                $endorsed_workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Add worker details to the memo
                foreach ($endorsed_workers as $index => $worker) {
                    $memo_content .= ($index + 1) . ". {$worker['last_name']}, {$worker['first_name']} {$worker['middle_name']}";
                    $memo_content .= " (Passport: {$worker['passport_number']})\n";
                }
                
                // Save the memo file
                file_put_contents($memo_path, $memo_content);
                
                // Commit the transaction
                $pdo->commit();
                
                // Set success response
                $response['success'] = true;
                $response['message'] = "Successfully endorsed $update_count record(s)";
                $response['memo_url'] = $memo_path;
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
