<?php
require_once 'connection.php';

$response = ['success' => false];

try {
    $stmt = $pdo->prepare("INSERT INTO ofw_transaction 
        (last_name, given_name, middle_name, sex, address, destination, 
         eval_counter_no, eval_type, eval_time_in, eval_time_out, eval_total_pct, 
         pay_counter_no, pay_time_in, pay_time_out, pay_total_pct, remarks)
        VALUES 
        (:last_name, :given_name, :middle_name, :sex, :address, :destination, 
         :eval_counter_no, :eval_type, :eval_time_in, :eval_time_out, :eval_total_pct, 
         :pay_counter_no, :pay_time_in, :pay_time_out, :pay_total_pct, :remarks)");

    $stmt->execute([
        ':last_name' => $_POST['last_name'],
        ':given_name' => $_POST['given_name'],
        ':middle_name' => $_POST['middle_name'],
        ':sex' => $_POST['sex'],
        ':address' => $_POST['address'],
        ':destination' => $_POST['destination'],
        ':eval_counter_no' => $_POST['eval_counter_no'],
        ':eval_type' => $_POST['eval_type'],
        ':eval_time_in' => $_POST['eval_time_in'],
        ':eval_time_out' => $_POST['eval_time_out'],
        ':eval_total_pct' => $_POST['eval_total_pct'],
        ':pay_counter_no' => $_POST['pay_counter_no'],
        ':pay_time_in' => $_POST['pay_time_in'],
        ':pay_time_out' => $_POST['pay_time_out'],
        ':pay_total_pct' => $_POST['pay_total_pct'],
        ':remarks' => $_POST['remarks'],
    ]);

    $lastId = $pdo->lastInsertId();

    $response['success'] = true;
    $response['record'] = [
        'or_no' => $lastId, // auto-generated
        'last_name' => $_POST['last_name'],
        'given_name' => $_POST['given_name'],
        'middle_name' => $_POST['middle_name'],
        'sex' => $_POST['sex'],
        'address' => $_POST['address'],
        'destination' => $_POST['destination'],
        'eval_counter_no' => $_POST['eval_counter_no'],
        'eval_type' => $_POST['eval_type'],
        'eval_time_in' => $_POST['eval_time_in'],
        'eval_time_out' => $_POST['eval_time_out'],
        'eval_total_pct' => $_POST['eval_total_pct'],
        'pay_counter_no' => $_POST['pay_counter_no'],
        'pay_time_in' => $_POST['pay_time_in'],
        'pay_time_out' => $_POST['pay_time_out'],
        'pay_total_pct' => $_POST['pay_total_pct'],
        'remarks' => $_POST['remarks'],
    ];

} catch (PDOException $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
