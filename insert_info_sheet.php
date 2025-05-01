<?php
require_once 'connection.php'; // Use the same folder

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $sql = "INSERT INTO info_sheet (
            family_name, first_name, middle_name, gender, jobsite, name_of_agency,
            purpose, specify_if_others_purpose, worker_category, specify_if_regional_office_or_polo,
            requested_record, documents_presented, if_others_documents_presented,
            year_of_action_taken, ofw_record_released, number_of_records_retrieved_printed,
            time_received, time_released, total_pct, remarks_if_pct_not_met, specify_if_others_final
        ) VALUES (
            :family_name, :first_name, :middle_name, :gender, :jobsite, :name_of_agency,
            :purpose, :specify_if_others_purpose, :worker_category, :specify_if_regional_office_or_polo,
            :requested_record, :documents_presented, :if_others_documents_presented,
            :year_of_action_taken, :ofw_record_released, :number_of_records_retrieved_printed,
            :time_received, :time_released, :total_pct, :remarks_if_pct_not_met, :specify_if_others_final
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($_POST);

        echo "Record inserted successfully.";
    } catch (PDOException $e) {
        echo "Error inserting record: " . $e->getMessage();
    }
}
?>
