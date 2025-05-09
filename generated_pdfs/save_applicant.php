<?php
require_once 'connection.php';

try {
    $stmt = $pdo->prepare("INSERT INTO gov_to_gov (
      last_name, first_name, middle_name, sex, birth_date, age,
      height, weight, educational_attainment, present_address, email_address,
      contact_number, passport_number, passport_validity, id_presented,
      id_number, with_job_experience, company_name_year_started_ended,
      with_job_experience_aside_from, name_company_year_started_ended,
      remarks, date_received_by_region
    ) VALUES (
      :last_name, :first_name, :middle_name, :sex, :birth_date, :age,
      :height, :weight, :educational_attainment, :present_address, :email_address,
      :contact_number, :passport_number, :passport_validity, :id_presented,
      :id_number, :with_job_experience, :company_name_year_started_ended,
      :with_job_experience_aside_from, :name_company_year_started_ended,
      :remarks, :date_received_by_region
    )");
  
    $stmt->execute([
      ':last_name' => $_POST['last_name'],
      ':first_name' => $_POST['first_name'],
      ':middle_name' => $_POST['middle_name'],
      ':sex' => $_POST['sex'],
      ':birth_date' => $_POST['birth_date'],
      ':age' => $_POST['age'],
      ':height' => $_POST['height'],
      ':weight' => $_POST['weight'],
      ':educational_attainment' => $_POST['educational_attainment'],
      ':present_address' => $_POST['present_address'],
      ':email_address' => $_POST['email_address'],
      ':contact_number' => $_POST['contact_number'],
      ':passport_number' => $_POST['passport_number'],
      ':passport_validity' => $_POST['passport_validity'],
      ':id_presented' => $_POST['id_presented'],
      ':id_number' => $_POST['id_number'],
      ':with_job_experience' => $_POST['with_job_experience'],
      ':company_name_year_started_ended' => $_POST['company_name_year_started_ended'],
      ':with_job_experience_aside_from' => $_POST['with_job_experience_aside_from'],
      ':name_company_year_started_ended' => $_POST['name_company_year_started_ended'],
      ':remarks' => $_POST['remarks'],
      ':date_received_by_region' => $_POST['date_received_by_region']
    ]);
  header("Location: /MWPD/gov_to_gov.php");

  exit;
} catch (PDOException $e) {
  echo "Error saving data: " . $e->getMessage();
}
