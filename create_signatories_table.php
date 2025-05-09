<?php
require_once 'connection.php';

try {
    // Check if the table already exists
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
        
        // Insert default signatories
        $insertSql = "INSERT INTO signatories (name, position, position_order) VALUES 
            ('IVAN ANGELO M. CANO', 'MWPD', 1),
            ('JOHN DOE', 'Department Head', 2),
            ('JANE SMITH', 'Director', 3)";
        
        $pdo->exec($insertSql);
        
        echo "<p>Signatories table created successfully with default entries.</p>";
    } else {
        echo "<p>Signatories table already exists.</p>";
    }
    
    echo "<p>You can now <a href='index.php'>return to the dashboard</a>.</p>";
    
} catch (PDOException $e) {
    die("Error creating signatories table: " . $e->getMessage());
}
?>
