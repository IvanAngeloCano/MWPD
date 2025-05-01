-- SQL script to create the endorsed_gov_to_gov table

CREATE TABLE IF NOT EXISTS endorsed_gov_to_gov (
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
    employer VARCHAR(255)
);

-- Add a note that this table has been created
INSERT INTO endorsed_gov_to_gov (g2g_id, last_name, first_name, middle_name, remarks, memo_reference, employer)
VALUES (0, 'System', 'Table', 'Created', 'This is a system record to indicate the table was created successfully', 'System', 'System');

-- You can delete this record after verifying the table exists
-- DELETE FROM endorsed_gov_to_gov WHERE g2g_id = 0;
