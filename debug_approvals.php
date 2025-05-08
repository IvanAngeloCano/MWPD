<?php
include 'session.php';
require_once 'connection.php';

// Show pending_g2g_approvals table structure
try {
    echo "<h1>Debugging Gov-to-Gov Approvals</h1>";
    $stmt = $pdo->query("DESCRIBE pending_g2g_approvals");
    echo "<h2>pending_g2g_approvals Table Structure:</h2>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // Show recent approvals
    $stmt = $pdo->query("SELECT * FROM pending_g2g_approvals ORDER BY approval_id DESC LIMIT 10");
    echo "<h2>Recent Pending Approvals:</h2>";
    echo "<table border='1'><tr>";
    $first_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($first_row) {
        foreach ($first_row as $key => $value) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Display the first row
        echo "<tr>";
        foreach ($first_row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
        
        // Display the rest of the rows
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>No records found</td></tr>";
    }
    echo "</table>";

    // Check gov_to_gov records that correspond to pending approvals
    $stmt = $pdo->query("
        SELECT g.g2g, g.last_name, g.first_name, g.remarks, p.status, p.approval_id
        FROM gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        ORDER BY p.approval_id DESC
        LIMIT 10
    ");
    echo "<h2>Gov-to-Gov Records with Pending Approvals:</h2>";
    echo "<table border='1'><tr><th>G2G ID</th><th>Name</th><th>G2G Remarks</th><th>Approval Status</th><th>Approval ID</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['g2g']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['remarks'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['approval_id']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Add a fix button
    echo "<h2>Fix Inconsistent Approval Statuses:</h2>";
    echo "<form method='post' action='fix_g2g_approval_statuses.php'>";
    echo "<button type='submit' style='padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px;'>Fix Approval Statuses</button>";
    echo "</form>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
