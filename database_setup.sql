-- Database Setup Script for Snack Shop
-- Database: if0_39943693_wp37
-- Host: sql208.infinityfree.com

-- ============================================
-- Table: Support
-- Purpose: Store customer support/feedback messages
-- ============================================
CREATE TABLE IF NOT EXISTS Support (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Customer name',
    email VARCHAR(255) NOT NULL COMMENT 'Customer email address',
    subject VARCHAR(500) DEFAULT NULL COMMENT 'Message subject',
    message TEXT NOT NULL COMMENT 'Message content',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Submission timestamp',
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customer support and feedback messages';

-- ============================================
-- Table: Recruit
-- Purpose: Store job application submissions
-- ============================================
CREATE TABLE IF NOT EXISTS Recruit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Applicant full name',
    email VARCHAR(255) NOT NULL COMMENT 'Applicant email address',
    phone VARCHAR(50) DEFAULT NULL COMMENT 'Applicant phone number',
    position_id INT NOT NULL COMMENT 'Job position ID',
    motivation TEXT NOT NULL COMMENT 'Applicant motivation/cover letter',
    file_path VARCHAR(500) DEFAULT NULL COMMENT 'Path to uploaded resume/file',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Application submission timestamp',
    INDEX idx_email (email),
    INDEX idx_position (position_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Job application submissions';

-- ============================================
-- Optional: View to see recent support messages
-- ============================================
CREATE OR REPLACE VIEW vw_recent_support AS
SELECT 
    id,
    name,
    email,
    subject,
    LEFT(message, 100) AS message_preview,
    created_at
FROM Support
ORDER BY created_at DESC
LIMIT 50;

-- ============================================
-- Optional: View to see recent applications
-- ============================================
CREATE OR REPLACE VIEW vw_recent_applications AS
SELECT 
    r.id,
    r.name,
    r.email,
    r.phone,
    r.position_id,
    LEFT(r.motivation, 100) AS motivation_preview,
    r.file_path,
    r.created_at
FROM Recruit r
ORDER BY r.created_at DESC
LIMIT 50;

