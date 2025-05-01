-- SQL script to add 'Endorsed' as an option in the database for the remarks field

-- Update any existing records with NULL remarks to 'Pending'
UPDATE gov_to_gov SET remarks = 'Pending' WHERE remarks IS NULL OR remarks = '';

-- Add a sample record with 'Endorsed' remarks to ensure it exists in the database
-- This is just to ensure the option exists in the database
INSERT INTO gov_to_gov (last_name, first_name, middle_name, remarks)
SELECT 'SYSTEM', 'OPTION', 'ENDORSED', 'Endorsed'
FROM dual
WHERE NOT EXISTS (
    SELECT 1 FROM gov_to_gov WHERE remarks = 'Endorsed' LIMIT 1
);

-- You can delete this record after verifying the option exists
-- DELETE FROM gov_to_gov WHERE last_name = 'SYSTEM' AND first_name = 'OPTION' AND middle_name = 'ENDORSED';
