<?php
require_once 'connection.php';

try {
    // Modified SQL to include 'employer', 'employmentdurationstart', 'employmentdurationend'
    $stmt = $pdo->prepare("INSERT INTO bm (
        last_name, given_name, middle_name, sex, address, destination, 
        position, salary, nameofthenewprincipal, employer, 
        employmentdurationstart, employmentdurationend, 
        datearrival, datedeparture
    ) VALUES (
        :last_name, :given_name, :middle_name, :sex, :address, :destination, 
        :position, :salary, :nameofthenewprincipal, :employer, 
        :employmentdurationstart, :employmentdurationend, 
        :datearrival, :datedeparture
    )");

    // Executing the prepared statement with form data
    $stmt->execute([
        ':last_name' => $_POST['last_name'],
        ':given_name' => $_POST['given_name'],
        ':middle_name' => $_POST['middle_name'],
        ':sex' => $_POST['sex'],
        ':address' => $_POST['address'],
        ':destination' => $_POST['destination'],
        ':position' => $_POST['position'],
        ':salary' => $_POST['salary'],
        ':nameofthenewprincipal' => $_POST['nameofthenewprincipal'],
        ':employer' => $_POST['employer'],
        ':employmentdurationstart' => $_POST['employmentdurationstart'],
        ':employmentdurationend' => $_POST['employmentdurationend'],
        ':datearrival' => $_POST['dateofarrival'],
        ':datedeparture' => $_POST['dateofdeparture']
    ]);

    // Return success with the inserted data
    $response = [
        'success' => true,
        'bmid' => $pdo->lastInsertId(),
        'last_name' => $_POST['last_name'],
        'given_name' => $_POST['given_name'],
        'middle_name' => $_POST['middle_name'],
        'sex' => $_POST['sex'],
        'address' => $_POST['address'],
        'destination' => $_POST['destination'],
        'position' => $_POST['position'],
        'salary' => $_POST['salary'],
        'nameofthenewprincipal' => $_POST['nameofthenewprincipal'],
        'employer' => $_POST['employer'],
        'employmentdurationstart' => $_POST['employmentdurationstart'],
        'employmentdurationend' => $_POST['employmentdurationend'],
        'datearrival' => $_POST['dateofarrival'],
        'datedeparture' => $_POST['dateofdeparture'],
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
    echo json_encode($response);
}
?>
