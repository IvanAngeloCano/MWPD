<?php
require_once 'connection.php';

try {
    // Check if signatories table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'signatories'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the signatories table
        $sql = "CREATE TABLE signatories (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            position VARCHAR(255) NOT NULL,
            position_order INT(11) NOT NULL DEFAULT 0,
            signature_file VARCHAR(255) DEFAULT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        
        echo "Created signatories table.\n";
    }
    
    // Check if there are any signatories
    $count = $pdo->query("SELECT COUNT(*) FROM signatories")->fetchColumn();
    
    if ($count == 0) {
        // Insert default signatories
        $insertSql = "INSERT INTO signatories (name, position, position_order) VALUES 
            ('IVAN ANGELO M. CANO', 'MWPD', 1),
            ('JOHN DOE', 'Department Head', 2),
            ('JANE SMITH', 'Director', 3)";
        
        $pdo->exec($insertSql);
        
        echo "Added default signatories.\n";
    }
    
    // Update the first signatory to use the signature image
    $updateSql = "UPDATE signatories SET signature_file = 'Signature.png' WHERE position_order = 1";
    $pdo->exec($updateSql);
    
    echo "Updated signature file for the first signatory.\n";
    
    // Display current signatories
    $signatories = $pdo->query("SELECT * FROM signatories ORDER BY position_order")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nCurrent signatories:\n";
    foreach ($signatories as $signatory) {
        echo "ID: {$signatory['id']}, Name: {$signatory['name']}, Position: {$signatory['position']}, Signature: {$signatory['signature_file']}\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
