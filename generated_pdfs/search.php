<?php
include 'session.php';
require_once 'connection.php';

$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT first_name, middle_name, last_name FROM gov_to_gov 
        WHERE first_name LIKE :searchQuery OR middle_name LIKE :searchQuery OR last_name LIKE :searchQuery";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['searchQuery' => $searchQuery . '%']);
    
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['middle_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='3'>No records found for your search query.</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='3'>Error fetching data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>
