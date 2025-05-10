<?php
// Force update of all approved records
include 'session.php';
require_once 'connection.php';

echo "<h1>Gov-to-Gov Approval Fix</h1>";

try {
    $pdo->beginTransaction();
    
    // Step 1: Check if we have any approved records in the pending_g2g_approvals table
    $check_stmt = $pdo->query("SELECT COUNT(*) FROM pending_g2g_approvals WHERE status = 'Approved'");
    $approved_count = $check_stmt->fetchColumn();
    
    echo "<p>Found $approved_count records marked as 'Approved' in pending_g2g_approvals table.</p>";
    
    // Step 2: Force update the remarks field in the gov_to_gov table for all approved records
    $update_stmt = $pdo->prepare("
        UPDATE gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        SET g.remarks = 'Approved'
        WHERE p.status = 'Approved'
    ");
    
    $update_stmt->execute();
    $updated_count = $update_stmt->rowCount();
    
    echo "<p>Updated $updated_count records in gov_to_gov table with remarks = 'Approved'</p>";
    
    // Step 3: List all the approved records
    $list_stmt = $pdo->query("
        SELECT g.g2g, g.last_name, g.first_name, g.remarks, p.status
        FROM gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        WHERE p.status = 'Approved'
        ORDER BY g.g2g DESC
    ");
    
    echo "<h2>Approved Records:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Remarks</th><th>Approval Status</th></tr>";
    
    while ($row = $list_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['g2g']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_name'] . ', ' . $row['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    $pdo->commit();
    
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<p style='color: #155724; font-weight: bold;'>Fix completed successfully!</p>";
    echo "<p>You should now see all approved records in the Approved tab.</p>";
    echo "</div>";
    
    echo "<p><a href='gov_to_gov.php?tab=approved' style='display: inline-block; padding: 10px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px;'>Go to Approved Tab</a></p>";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<p style='color: #721c24; font-weight: bold;'>Error:</p>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
