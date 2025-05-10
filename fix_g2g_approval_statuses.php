<?php
include 'session.php';
require_once 'connection.php';

try {
    $pdo->beginTransaction();
    
    echo "<h1>Fixing Gov-to-Gov Approval Statuses</h1>";
    
    // 1. Fix records that have Approved status in pending_g2g_approvals but don't have Approved remarks in gov_to_gov
    $query1 = "
        UPDATE gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        SET g.remarks = 'Approved'
        WHERE p.status = 'Approved' AND (g.remarks IS NULL OR g.remarks != 'Approved')
    ";
    
    $count1 = $pdo->exec($query1);
    echo "<p>Fixed $count1 records to have 'Approved' status</p>";
    
    // 2. Fix records that have Rejected status in pending_g2g_approvals but don't have Rejected remarks in gov_to_gov
    $query2 = "
        UPDATE gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        SET g.remarks = 'Rejected'
        WHERE p.status = 'Rejected' AND (g.remarks IS NULL OR g.remarks != 'Rejected')
    ";
    
    $count2 = $pdo->exec($query2);
    echo "<p>Fixed $count2 records to have 'Rejected' status</p>";
    
    // 3. Fix records that have Pending status in pending_g2g_approvals but don't have Pending remarks in gov_to_gov
    $query3 = "
        UPDATE gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        SET g.remarks = 'Pending'
        WHERE p.status = 'Pending' AND (g.remarks IS NULL OR g.remarks != 'Pending')
    ";
    
    $count3 = $pdo->exec($query3);
    echo "<p>Fixed $count3 records to have 'Pending' status</p>";
    
    $pdo->commit();
    
    echo "<div style='margin: 20px 0; padding: 10px; background-color: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
    echo "<h3 style='color: #3c763d;'>All records fixed!</h3>";
    echo "<p>Total records fixed: " . ($count1 + $count2 + $count3) . "</p>";
    echo "</div>";
    
    echo "<p><a href='debug_approvals.php' style='display: inline-block; padding: 10px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Return to Debug Page</a> &nbsp;";
    echo "<a href='gov_to_gov.php?tab=approved' style='display: inline-block; padding: 10px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px;'>Go to Approved Tab</a></p>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<div style='margin: 20px 0; padding: 10px; background-color: #f2dede; border: 1px solid #ebccd1; border-radius: 4px;'>";
    echo "<h3 style='color: #a94442;'>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
