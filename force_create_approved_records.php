<?php
// This script will create approved records for testing
include 'session.php';
require_once 'connection.php';

echo "<h1>Force Create Approved Records</h1>";

try {
    $pdo->beginTransaction();
    
    // Step 1: Get a list of Gov-to-Gov records that we can update to Approved status
    $check_stmt = $pdo->query("SELECT g2g, last_name, first_name FROM gov_to_gov LIMIT 5");
    $records = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($records) . " records that can be updated to Approved status.</p>";
    
    if (count($records) > 0) {
        // Step 2: Update these records to have 'Approved' in the remarks field
        $update_stmt = $pdo->prepare("UPDATE gov_to_gov SET remarks = 'Approved' WHERE g2g = ?");
        
        echo "<h2>Records Updated:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Status</th></tr>";
        
        foreach ($records as $record) {
            $update_stmt->execute([$record['g2g']]);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($record['g2g']) . "</td>";
            echo "<td>" . htmlspecialchars($record['last_name'] . ', ' . $record['first_name']) . "</td>";
            echo "<td>Approved</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No records found to update.</p>";
    }
    
    $pdo->commit();
    
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<p style='color: #155724; font-weight: bold;'>Update completed successfully!</p>";
    echo "<p>Several records have been manually set to 'Approved' status.</p>";
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
