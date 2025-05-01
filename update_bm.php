<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Get form data
  $bmid = $_POST['bmid'];
  $lastName = $_POST['last_name'];
  $givenName = $_POST['given_name'];
  $middleName = $_POST['middle_name'];
  $sex = $_POST['sex'];
  $address = $_POST['address'];
  $destination = $_POST['destination'];
  $remarks = $_POST['remarks'];
  $nameoftheagency = $_POST['nameoftheagency'];
  $nameoftheprincipal = $_POST['nameoftheprincipal'];
  $nameofthenewagency = $_POST['nameofthenewagency'];
  $nameofthenewprincipal = $_POST['nameofthenewprincipal'];
  $employmentdurationstart = $_POST['employmentdurationstart'];
  $employmentdurationend = $_POST['employmentdurationend'];
  $dateofarrival = $_POST['dateofarrival'];
  $dateofdeparture = $_POST['dateofdeparture'];

  try {
    // Prepare the update SQL query
    $sql = "UPDATE bm SET 
            last_name = :last_name, 
            given_name = :given_name, 
            middle_name = :middle_name, 
            sex = :sex, 
            address = :address, 
            destination = :destination, 
            remarks = :remarks,
            nameoftheagency = :nameoftheagency,
            nameoftheprincipal = :nameoftheprincipal,
            nameofthenewagency = :nameofthenewagency,
            nameofthenewprincipal = :nameofthenewprincipal,
            employmentdurationstart = :employmentdurationstart,
            employmentdurationend = :employmentdurationend,
            dateofarrival = :dateofarrival,
            dateofdeparture = :dateofdeparture
            WHERE bmid = :bmid";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bindParam(':bmid', $bmid);
    $stmt->bindParam(':last_name', $lastName);
    $stmt->bindParam(':given_name', $givenName);
    $stmt->bindParam(':middle_name', $middleName);
    $stmt->bindParam(':sex', $sex);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':destination', $destination);
    $stmt->bindParam(':remarks', $remarks);
    $stmt->bindParam(':nameoftheagency', $nameoftheagency);
    $stmt->bindParam(':nameoftheprincipal', $nameoftheprincipal);
    $stmt->bindParam(':nameofthenewagency', $nameofthenewagency);
    $stmt->bindParam(':nameofthenewprincipal', $nameofthenewprincipal);
    $stmt->bindParam(':employmentdurationstart', $employmentdurationstart);
    $stmt->bindParam(':employmentdurationend', $employmentdurationend);
    $stmt->bindParam(':dateofarrival', $dateofarrival);
    $stmt->bindParam(':dateofdeparture', $dateofdeparture);
    
    $stmt->execute();

    echo json_encode(['success' => true]);
  } catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
}
?>
