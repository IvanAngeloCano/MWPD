<?php
/**
 * MWPD System Enhancement Setup
 * This script creates the necessary database tables for the enhanced features:
 * - Blacklist system
 * - Audit logging
 * - Cross-module applicant indexing
 */

include 'session.php';
require_once 'connection.php';

// Check for admin privileges
if ($_SESSION['role'] !== 'div head' && $_SESSION['role'] !== 'Division Head') {
    die("Access denied. This script requires Division Head privileges.");
}

// Create tables with a function to track progress
function createTable($pdo, $tableName, $sqlQuery) {
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() > 0) {
            echo "<p>Table '$tableName' already exists.</p>";
            return true;
        }
        
        // Create table
        $pdo->exec($sqlQuery);
        echo "<p>Table '$tableName' created successfully.</p>";
        return true;
    } catch (PDOException $e) {
        echo "<p>Error creating table '$tableName': " . $e->getMessage() . "</p>";
        return false;
    }
}

// Start setup
echo "<h1>MWPD System Enhancement Setup</h1>";
echo "<p>Setting up enhanced features...</p>";

// 1. Create blacklist table with optimized indexes
$blacklistTable = "
CREATE TABLE `blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `reason` text NOT NULL,
  `severity_level` tinyint(1) DEFAULT 1,
  `blacklist_date` date NOT NULL,
  `blacklist_expiry` date DEFAULT NULL,
  `source` varchar(50) NOT NULL COMMENT 'internal/external/api',
  `api_source` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `added_by` int(11) NOT NULL,
  `added_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`last_name`,`first_name`),
  KEY `idx_name_dob` (`last_name`,`first_name`,`date_of_birth`),
  KEY `idx_active` (`active`),
  KEY `idx_source` (`source`),
  FULLTEXT KEY `ft_names` (`first_name`,`last_name`,`middle_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
createTable($pdo, 'blacklist', $blacklistTable);

// 2. Create blacklist documents table
$blacklistDocsTable = "
CREATE TABLE `blacklist_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blacklist_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_number` varchar(100) NOT NULL,
  `issuing_country` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `added_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doc_number` (`document_type`,`document_number`),
  KEY `idx_blacklist_id` (`blacklist_id`),
  KEY `idx_document` (`document_type`,`document_number`),
  CONSTRAINT `fk_blacklist_docs` FOREIGN KEY (`blacklist_id`) REFERENCES `blacklist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
createTable($pdo, 'blacklist_documents', $blacklistDocsTable);

// 3. Create blacklist contact information
$blacklistContactsTable = "
CREATE TABLE `blacklist_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blacklist_id` int(11) NOT NULL,
  `contact_type` varchar(20) NOT NULL COMMENT 'email/phone/address',
  `contact_value` varchar(255) NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `added_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_blacklist_id` (`blacklist_id`),
  KEY `idx_contact` (`contact_type`,`contact_value`(191)),
  CONSTRAINT `fk_blacklist_contacts` FOREIGN KEY (`blacklist_id`) REFERENCES `blacklist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
createTable($pdo, 'blacklist_contacts', $blacklistContactsTable);

// 4. Create comprehensive audit log table
$auditLogTable = "
CREATE TABLE `audit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  KEY `idx_record` (`module`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
createTable($pdo, 'audit_log', $auditLogTable);

// 5. Create applicant index for cross-module search
$applicantIndexTable = "
CREATE TABLE `applicant_index` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `source_module` varchar(50) NOT NULL,
  `source_id` int(11) NOT NULL,
  `data_hash` varchar(64) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_source` (`source_module`,`source_id`),
  KEY `idx_name` (`last_name`,`first_name`),
  KEY `idx_name_dob` (`last_name`,`first_name`,`date_of_birth`),
  KEY `idx_hash` (`data_hash`),
  FULLTEXT KEY `ft_names` (`first_name`,`last_name`,`middle_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
createTable($pdo, 'applicant_index', $applicantIndexTable);

// 6. Create index for applicant documents
$applicantDocsIndexTable = "
CREATE TABLE `applicant_documents_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_index_id` bigint(20) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `document_number` varchar(100) NOT NULL,
  `source_module` varchar(50) NOT NULL,
  `source_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doc_source` (`document_type`,`document_number`,`source_module`,`source_id`),
  KEY `idx_applicant_id` (`applicant_index_id`),
  KEY `idx_document` (`document_type`,`document_number`),
  CONSTRAINT `fk_app_docs_index` FOREIGN KEY (`applicant_index_id`) REFERENCES `applicant_index` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
createTable($pdo, 'applicant_documents_index', $applicantDocsIndexTable);

// 7. Create index for applicant contacts
$applicantContactsIndexTable = "
CREATE TABLE `applicant_contacts_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_index_id` bigint(20) NOT NULL,
  `contact_type` varchar(20) NOT NULL COMMENT 'email/phone/address',
  `contact_value` varchar(255) NOT NULL,
  `source_module` varchar(50) NOT NULL,
  `source_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_applicant_id` (`applicant_index_id`),
  KEY `idx_contact` (`contact_type`,`contact_value`(191)),
  CONSTRAINT `fk_app_contacts_index` FOREIGN KEY (`applicant_index_id`) REFERENCES `applicant_index` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
createTable($pdo, 'applicant_contacts_index', $applicantContactsIndexTable);

echo "<h2>Setup Completed</h2>";
echo "<p>All required tables have been created. You can now use the enhanced features.</p>";
echo "<p><a href='dashboard.php'>Return to Dashboard</a></p>";
?>
