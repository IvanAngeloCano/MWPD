-- SQL script to create a table for pending approvals

CREATE TABLE IF NOT EXISTS pending_g2g_approvals (
    approval_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    g2g_id INT(11) NOT NULL,
    submitted_by INT(11) NOT NULL,
    submitted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    employer VARCHAR(255),
    memo_reference VARCHAR(255),
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approval_date TIMESTAMP NULL,
    approved_by INT(11) NULL,
    remarks TEXT,
    FOREIGN KEY (g2g_id) REFERENCES gov_to_gov(g2g) ON DELETE CASCADE
);
