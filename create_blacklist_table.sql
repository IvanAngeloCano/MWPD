USE mwpd;

CREATE TABLE IF NOT EXISTS blacklist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  passport_number VARCHAR(50),
  email VARCHAR(100),
  phone VARCHAR(50),
  reason TEXT NOT NULL,
  submitted_by INT NOT NULL,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  notes TEXT,
  approved_by INT,
  approved_date DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
