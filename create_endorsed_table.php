<?php
require_once 'connection.php';

try {
    // Create the endorsed_gov_to_gov table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS endorsed_gov_to_gov (
        endorsed_id INT(11) AUTO_INCREMENT PRIMARY KEY,
        g2g_id INT(11) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        first_name VARCHAR(255) NOT NULL,
        middle_name VARCHAR(255),
        sex VARCHAR(10),
        birth_date DATE,
        age INT(3),
        height VARCHAR(20),
        weight VARCHAR(20),
        educational_attainment VARCHAR(255),
        present_address TEXT,
        email_address VARCHAR(255),
        contact_number VARCHAR(20),
        passport_number VARCHAR(50),
        passport_validity DATE,
        id_presented VARCHAR(255),
        id_number VARCHAR(255),
        with_job_experience VARCHAR(5),
        job_title VARCHAR(255),
        job_description TEXT,
        remarks TEXT,
        endorsement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        memo_reference VARCHAR(255),
        employer VARCHAR(255),
        FOREIGN KEY (g2g_id) REFERENCES gov_to_gov(g2g) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "Endorsed Gov to Gov table created successfully!";
    
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
