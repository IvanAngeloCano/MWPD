<?php
include 'session.php';
require_once 'connection.php';

echo "<h2>Gov-to-Gov Approval Debug</h2>";

try {
    // Check gov_to_gov table structure
    $stmt = $pdo->query("DESCRIBE gov_to_gov");
    echo "<h3>Gov-to-Gov Table Structure:</h3>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Check records in the gov_to_gov table
    $stmt = $pdo->query("SELECT g2g, last_name, first_name, remarks FROM gov_to_gov ORDER BY g2g DESC LIMIT 10");
    echo "<h3>Recent Gov-to-Gov Records:</h3>";
    echo "<table border='1'><tr><th>ID</th><th>Last Name</th><th>First Name</th><th>Remarks</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['g2g'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['last_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['remarks'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check pending approvals
    $stmt = $pdo->query("SELECT approval_id, g2g_id, status FROM pending_g2g_approvals ORDER BY approval_id DESC LIMIT 10");
    echo "<h3>Recent Pending Approvals:</h3>";
    echo "<table border='1'><tr><th>Approval ID</th><th>G2G ID</th><th>Status</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['approval_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['g2g_id'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['status'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
