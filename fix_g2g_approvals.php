<?php
include 'session.php';
require_once 'connection.php';

try {
    $pdo->beginTransaction();
    
    echo "<h2>Gov-to-Gov Approval Fix</h2>";
    
    // 1. Update all records with approved status in pending_g2g_approvals to have 'Approved' in gov_to_gov
    $update_query = "
        UPDATE gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        SET g.remarks = 'Approved'
        WHERE p.status = 'Approved'
    ";
    
    $updated = $pdo->exec($update_query);
    echo "<p>Updated $updated records to have remarks = 'Approved'</p>";
    
    // 2. Update all pending approvals' records to have 'Pending' in gov_to_gov
    $pending_query = "
        UPDATE gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        SET g.remarks = 'Pending'
        WHERE p.status = 'Pending'
    ";
    
    $pending_updated = $pdo->exec($pending_query);
    echo "<p>Updated $pending_updated records to have remarks = 'Pending'</p>";
    
    // 3. Update all rejected approvals' records to have 'Rejected' in gov_to_gov
    $rejected_query = "
        UPDATE gov_to_gov g
        JOIN pending_g2g_approvals p ON g.g2g = p.g2g_id
        SET g.remarks = 'Rejected'
        WHERE p.status = 'Rejected'
    ";
    
    $rejected_updated = $pdo->exec($rejected_query);
    echo "<p>Updated $rejected_updated records to have remarks = 'Rejected'</p>";
    
    $pdo->commit();
    
    echo "<h3>Fix completed successfully!</h3>";
    echo "<p><a href='gov_to_gov.php?tab=approved'>Go to Gov-to-Gov Approved Tab</a></p>";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
