<?php
require_once 'connection.php';

if (isset($_GET['bmid'])) {
    $bmid = $_GET['bmid'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM bm WHERE bmid = :bmid");
        $stmt->bindParam(':bmid', $bmid);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // No date formatting needed as those fields don't exist in the database
            
            echo json_encode($result);
        } else {
            echo json_encode(['error' => 'Record not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}
?>
