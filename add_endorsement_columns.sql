-- SQL script to add endorsement columns to the gov_to_gov table

-- Add endorsement_date column
ALTER TABLE gov_to_gov ADD COLUMN endorsement_date TIMESTAMP NULL DEFAULT NULL;

-- Add employer column
ALTER TABLE gov_to_gov ADD COLUMN employer VARCHAR(255) NULL DEFAULT NULL;

-- Add memo_reference column
ALTER TABLE gov_to_gov ADD COLUMN memo_reference VARCHAR(255) NULL DEFAULT NULL;
