<?php
require_once 'connection.php';

if (isset($_GET['bmid'])) {
    $bmid = $_GET['bmid'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM bm WHERE bmid = :bmid");
        $stmt->bindParam(':bmid', $bmid);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Format dates for proper display in form
            if (!empty($result['employmentdurationstart'])) {
                $result['employmentdurationstart'] = date('Y-m-d', strtotime($result['employmentdurationstart']));
            }
            if (!empty($result['employmentdurationend'])) {
                $result['employmentdurationend'] = date('Y-m-d', strtotime($result['employmentdurationend']));
            }
            if (!empty($result['dateofarrival'])) {
                $result['dateofarrival'] = date('Y-m-d', strtotime($result['dateofarrival']));
            }
            if (!empty($result['dateofdeparture'])) {
                $result['dateofdeparture'] = date('Y-m-d', strtotime($result['dateofdeparture']));
            }
            
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
