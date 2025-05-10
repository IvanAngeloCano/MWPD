<?php
require_once 'connection.php';
include 'session.php';

// Initialize response array
$response = ['success' => false];

// Check if form data was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bmid'])) {
    $bmid = $_POST['bmid'];
    
    // Let's check what columns actually exist in the BM table
    try {
        $columnsQuery = $pdo->query("SHOW COLUMNS FROM BM");
        $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN);
        
        // Log the columns for debugging
        error_log("BM table columns: " . implode(", ", $columns));
    } catch (PDOException $e) {
        error_log("Error checking columns: " . $e->getMessage());
    }
    
    try {
        // Prepare the SQL statement
        // Check if status column exists
        $check_status = $pdo->prepare("SHOW COLUMNS FROM bm LIKE 'status'");
        $check_status->execute();
        $status_exists = $check_status->rowCount() > 0;
        
        // Build SQL without remarks and with status if it exists
        $sql = "UPDATE bm SET 
                last_name = :last_name,
                given_name = :given_name,
                middle_name = :middle_name,
                sex = :sex,
                address = :address,
                destination = :destination";
                
        // Always set status to 'Pending' when edited, regardless of previous status
        if ($status_exists) {
            $sql .= ", status = 'Pending'";
        }
        
        $sql .= " WHERE bmid = :bmid";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters
        $stmt->bindParam(':bmid', $bmid);
        $stmt->bindParam(':last_name', $_POST['last_name']);
        $stmt->bindParam(':given_name', $_POST['given_name']);
        $stmt->bindParam(':middle_name', $_POST['middle_name']);
        $stmt->bindParam(':sex', $_POST['sex']);
        $stmt->bindParam(':address', $_POST['address']);
        $stmt->bindParam(':destination', $_POST['destination']);
        
        // Execute the statement
        if ($stmt->execute()) {
            $response['success'] = true;
            
            // Add notification for record update
            try {
                $notificationText = "Balik Manggagawa record updated: " . $_POST['last_name'] . ", " . $_POST['given_name'];
                $notificationSql = "INSERT INTO notifications (user_id, notification_text, created_at) 
                                   VALUES (:user_id, :notification_text, NOW())";
                $notificationStmt = $pdo->prepare($notificationSql);
                $notificationStmt->bindParam(':user_id', $_SESSION['user_id']);
                $notificationStmt->bindParam(':notification_text', $notificationText);
                $notificationStmt->execute();
            } catch (PDOException $e) {
                // Notification failed, but update was successful, so continue
            }
        }
    } catch (PDOException $e) {
        $response['error'] = $e->getMessage();
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
