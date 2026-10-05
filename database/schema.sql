-- ==============================================================================
-- DATABASE CREATION AND INITIALIZATION SCRIPT
-- Project: Campus Academic Resource & Notes Sharing Portal
-- DBMS Architecture: Relational Model (3NF Normalization, Triggers, Views, Procedures)
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `campus_notes_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `campus_notes_db`;

-- Drop existing objects for clean deployment if needed
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `download_logs`;
DROP TABLE IF EXISTS `bookmarks`;
DROP TABLE IF EXISTS `comments`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `resources`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `semesters`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP VIEW IF EXISTS `view_top_rated_resources`;
DROP VIEW IF EXISTS `view_department_statistics`;
DROP VIEW IF EXISTS `view_user_contributions`;
DROP PROCEDURE IF EXISTS `sp_approve_resource`;
DROP PROCEDURE IF EXISTS `sp_get_course_resources`;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------------------
-- 1. ROLES TABLE (RBAC Support)
-- ------------------------------------------------------------------------------
CREATE TABLE `roles` (
    `role_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 2. DEPARTMENTS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `departments` (
    `dept_id` INT AUTO_INCREMENT PRIMARY KEY,
    `dept_code` VARCHAR(20) NOT NULL UNIQUE,
    `dept_name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 3. USERS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `dept_id` INT NULL,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `reset_token` VARCHAR(64) NULL UNIQUE,
    `reset_token_expires_at` TIMESTAMP NULL,
    `academic_id` VARCHAR(50) NULL, -- Student ID or Faculty Employee Code
    `phone` VARCHAR(30) NULL,
    `bio` TEXT NULL,
    `avatar` VARCHAR(255) DEFAULT 'default_avatar.png',
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_users_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 4. SEMESTERS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `semesters` (
    `semester_id` INT AUTO_INCREMENT PRIMARY KEY,
    `semester_name` VARCHAR(50) NOT NULL,
    `semester_code` VARCHAR(20) NOT NULL UNIQUE,
    `academic_year` VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 5. COURSES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `courses` (
    `course_id` INT AUTO_INCREMENT PRIMARY KEY,
    `dept_id` INT NOT NULL,
    `semester_id` INT NOT NULL,
    `course_code` VARCHAR(30) NOT NULL UNIQUE,
    `course_title` VARCHAR(200) NOT NULL,
    `credit_hours` DECIMAL(3,1) DEFAULT 3.0,
    `syllabus_summary` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_courses_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_courses_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 6. RESOURCE CATEGORIES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(80) NOT NULL UNIQUE,
    `slug` VARCHAR(80) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT 'fa-file-text',
    `description` VARCHAR(255) NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 7. RESOURCES TABLE (Core Entity)
-- ------------------------------------------------------------------------------
CREATE TABLE `resources` (
    `resource_id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `course_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `uploaded_by` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_size` BIGINT UNSIGNED DEFAULT 0, -- in bytes
    `file_type` VARCHAR(50) NOT NULL,      -- pdf, docx, pptx, zip, etc.
    `download_count` INT UNSIGNED DEFAULT 0,
    `view_count` INT UNSIGNED DEFAULT 0,
    `avg_rating` DECIMAL(3,2) DEFAULT 0.00,
    `rating_count` INT UNSIGNED DEFAULT 0,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    `moderated_by` INT NULL,
    `moderation_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_resources_course` (`course_id`),
    INDEX `idx_resources_category` (`category_id`),
    INDEX `idx_resources_status` (`status`),
    CONSTRAINT `fk_resources_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_resources_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_resources_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_resources_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 8. REVIEWS & RATINGS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `reviews` (
    `review_id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `review_text` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_resource_review` (`resource_id`, `user_id`),
    CONSTRAINT `fk_reviews_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 9. COMMENTS TABLE (Discussion & Q&A)
-- ------------------------------------------------------------------------------
CREATE TABLE `comments` (
    `comment_id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `parent_id` INT NULL,
    `comment_text` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_comments_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`comment_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 10. BOOKMARKS / FAVORITES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `bookmarks` (
    `bookmark_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `resource_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_bookmark` (`user_id`, `resource_id`),
    CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_bookmarks_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 11. DOWNLOAD LOGS TABLE (For Analytics & Security Tracking)
-- ------------------------------------------------------------------------------
CREATE TABLE `download_logs` (
    `log_id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_id` INT NOT NULL,
    `user_id` INT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(255) NULL,
    `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_downloads_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_downloads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 12. ACTIVITY & AUDIT LOGS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE `activity_logs` (
    `activity_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------------------------
-- 13. CONTENT REPORTS TABLE (Moderation Queue)
-- ------------------------------------------------------------------------------
CREATE TABLE `reports` (
    `report_id` INT AUTO_INCREMENT PRIMARY KEY,
    `resource_id` INT NOT NULL,
    `reported_by` INT NOT NULL,
    `reason` VARCHAR(255) NOT NULL,
    `details` TEXT NULL,
    `status` ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    `resolved_by` INT NULL,
    `resolution_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` TIMESTAMP NULL,
    CONSTRAINT `fk_reports_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`resource_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_reports_user` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_reports_moderator` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==============================================================================
-- ADVANCED DBMS FEATURES: TRIGGERS
-- ==============================================================================

DELIMITER $$

-- Trigger 1: Automatically recalculate average rating and rating count when a review is added
CREATE TRIGGER `trg_after_review_insert`
AFTER INSERT ON `reviews`
FOR EACH ROW
BEGIN
    UPDATE `resources`
    SET 
        `avg_rating` = (SELECT IFNULL(AVG(`rating`), 0) FROM `reviews` WHERE `resource_id` = NEW.`resource_id`),
        `rating_count` = (SELECT COUNT(*) FROM `reviews` WHERE `resource_id` = NEW.`resource_id`)
    WHERE `resource_id` = NEW.`resource_id`;
END$$

-- Trigger 2: Recalculate average rating if a review is updated
CREATE TRIGGER `trg_after_review_update`
AFTER UPDATE ON `reviews`
FOR EACH ROW
BEGIN
    UPDATE `resources`
    SET 
        `avg_rating` = (SELECT IFNULL(AVG(`rating`), 0) FROM `reviews` WHERE `resource_id` = NEW.`resource_id`),
        `rating_count` = (SELECT COUNT(*) FROM `reviews` WHERE `resource_id` = NEW.`resource_id`)
    WHERE `resource_id` = NEW.`resource_id`;
END$$

-- Trigger 3: Recalculate average rating if a review is deleted
CREATE TRIGGER `trg_after_review_delete`
AFTER DELETE ON `reviews`
FOR EACH ROW
BEGIN
    UPDATE `resources`
    SET 
        `avg_rating` = (SELECT IFNULL(AVG(`rating`), 0) FROM `reviews` WHERE `resource_id` = OLD.`resource_id`),
        `rating_count` = (SELECT COUNT(*) FROM `reviews` WHERE `resource_id` = OLD.`resource_id`)
    WHERE `resource_id` = OLD.`resource_id`;
END$$

-- Trigger 4: Automatically increment resource download counter upon download log insertion
CREATE TRIGGER `trg_after_download_log_insert`
AFTER INSERT ON `download_logs`
FOR EACH ROW
BEGIN
    UPDATE `resources`
    SET `download_count` = `download_count` + 1
    WHERE `resource_id` = NEW.`resource_id`;
END$$

DELIMITER ;

-- ==============================================================================
-- ADVANCED DBMS FEATURES: DATABASE VIEWS
-- ==============================================================================

-- View 1: Top Rated Approved Resources with Author & Course Details
CREATE OR REPLACE VIEW `view_top_rated_resources` AS
SELECT 
    r.`resource_id`,
    r.`title`,
    r.`file_type`,
    r.`file_size`,
    r.`avg_rating`,
    r.`rating_count`,
    r.`download_count`,
    r.`view_count`,
    r.`created_at`,
    c.`course_code`,
    c.`course_title`,
    d.`dept_code`,
    d.`dept_name`,
    cat.`name` AS `category_name`,
    cat.`icon` AS `category_icon`,
    u.`full_name` AS `author_name`,
    u.`email` AS `author_email`
FROM `resources` r
JOIN `courses` c ON r.`course_id` = c.`course_id`
JOIN `departments` d ON c.`dept_id` = d.`dept_id`
JOIN `categories` cat ON r.`category_id` = cat.`category_id`
JOIN `users` u ON r.`uploaded_by` = u.`user_id`
WHERE r.`status` = 'approved';

-- View 2: Department-Level Statistics Summary
CREATE OR REPLACE VIEW `view_department_statistics` AS
SELECT 
    d.`dept_id`,
    d.`dept_code`,
    d.`dept_name`,
    COUNT(DISTINCT c.`course_id`) AS `total_courses`,
    COUNT(DISTINCT r.`resource_id`) AS `total_resources`,
    IFNULL(SUM(r.`download_count`), 0) AS `total_downloads`,
    COUNT(DISTINCT u.`user_id`) AS `total_enrolled_users`
FROM `departments` d
LEFT JOIN `courses` c ON d.`dept_id` = c.`dept_id`
LEFT JOIN `resources` r ON c.`course_id` = r.`course_id` AND r.`status` = 'approved'
LEFT JOIN `users` u ON d.`dept_id` = u.`dept_id`
GROUP BY d.`dept_id`, d.`dept_code`, d.`dept_name`;

-- View 3: User Contribution and Activity Ranking
CREATE OR REPLACE VIEW `view_user_contributions` AS
SELECT 
    u.`user_id`,
    u.`full_name`,
    u.`email`,
    ro.`role_name`,
    d.`dept_name`,
    COUNT(r.`resource_id`) AS `total_uploads`,
    SUM(CASE WHEN r.`status` = 'approved' THEN 1 ELSE 0 END) AS `approved_uploads`,
    IFNULL(SUM(r.`download_count`), 0) AS `total_downloads_received`,
    IFNULL(AVG(r.`avg_rating`), 0) AS `average_author_rating`
FROM `users` u
JOIN `roles` ro ON u.`role_id` = ro.`role_id`
LEFT JOIN `departments` d ON u.`dept_id` = d.`dept_id`
LEFT JOIN `resources` r ON u.`user_id` = r.`uploaded_by`
GROUP BY u.`user_id`, u.`full_name`, u.`email`, ro.`role_name`, d.`dept_name`;

-- ==============================================================================
-- ADVANCED DBMS FEATURES: STORED PROCEDURES
-- ==============================================================================

DELIMITER $$

-- Procedure 1: Safe Resource Approval Workflow with Transaction and Audit Log
CREATE PROCEDURE `sp_approve_resource`(
    IN p_resource_id INT,
    IN p_moderator_id INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
        -- Update resource status
        UPDATE `resources`
        SET 
            `status` = 'approved',
            `moderated_by` = p_moderator_id,
            `moderation_notes` = p_notes,
            `updated_at` = NOW()
        WHERE `resource_id` = p_resource_id;

        -- Record activity log
        INSERT INTO `activity_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `details`)
        VALUES (p_moderator_id, 'RESOURCE_APPROVED', 'resources', p_resource_id, CONCAT('Approved note ID: ', p_resource_id));
    COMMIT;
END$$

-- Procedure 2: Fetch Course-Wise Resources with Author Details
CREATE PROCEDURE `sp_get_course_resources`(
    IN p_course_id INT
)
BEGIN
    SELECT 
        r.`resource_id`,
        r.`title`,
        r.`description`,
        r.`file_name`,
        r.`file_type`,
        r.`file_size`,
        r.`avg_rating`,
        r.`download_count`,
        r.`created_at`,
        cat.`name` AS `category_name`,
        u.`full_name` AS `author_name`
    FROM `resources` r
    JOIN `categories` cat ON r.`category_id` = cat.`category_id`
    JOIN `users` u ON r.`uploaded_by` = u.`user_id`
    WHERE r.`course_id` = p_course_id AND r.`status` = 'approved'
    ORDER BY r.`created_at` DESC;
END$$

DELIMITER ;

-- ==============================================================================
-- SEED DATA INSERTION (ROLES, DEPARTMENTS, SEMESTERS, CATEGORIES, DEMO USERS)
-- ==============================================================================

-- 1. Roles
INSERT INTO `roles` (`role_id`, `role_name`, `display_name`, `description`) VALUES
(1, 'admin', 'Super Administrator', 'Full administrative privileges, user management, and system configuration'),
(2, 'moderator', 'Database Updater & Moderator', 'Data integrity oversight, note validation, and course catalog maintenance'),
(3, 'faculty', 'Faculty / Teacher', 'Verified course instructor, lecture note creator and academic contributor'),
(4, 'student', 'Student / Learner', 'Access resources, download notes, submit reviews, and participate in discussions');

-- 2. Departments (Nexus University Campuses)
INSERT INTO `departments` (`dept_id`, `dept_code`, `dept_name`, `description`) VALUES
(1, 'CSE', 'Computer Science & Engineering', 'Software engineering, algorithms, DBMS, AI, and systems at GEC Campus.'),
(2, 'EEE', 'Electrical & Electronic Engineering', 'Circuits, power systems, signal processing, and communication engineering.'),
(3, 'BBA', 'Business Administration', 'Marketing, finance, accounting, and human resource management at Prabartak Campus.'),
(4, 'LAW', 'Faculty of Law', 'Jurisprudence, constitutional law, and criminal law at Hazari Lane Campus.'),
(5, 'ENG', 'English Language & Literature', 'Linguistics, English literature, and academic writing at GEC Campus.'),
(6, 'MATH', 'Mathematics & Physical Sciences', 'Engineering mathematics, differential calculus, statistics, and physics.');

-- 3. Semesters
INSERT INTO `semesters` (`semester_id`, `semester_name`, `semester_code`, `academic_year`) VALUES
(1, 'Spring 2026', 'SPR26', '2026'),
(2, 'Summer 2026', 'SUM26', '2026'),
(3, 'Fall 2026', 'FAL26', '2026'),
(4, 'General / All Semesters', 'ALL', 'Ongoing');

-- 4. Categories
INSERT INTO `categories` (`category_id`, `name`, `slug`, `icon`, `description`) VALUES
(1, 'Lecture Notes & Handouts', 'lecture-notes', 'fa-book-open', 'Classroom notes, curated handwritten sheets, and summarized chapters'),
(2, 'Question Banks & Past Papers', 'question-banks', 'fa-file-invoice', 'Nexus semester mid-term & final exam question archives with solved keys'),
(3, 'Lab Manuals & Code Reports', 'lab-manuals', 'fa-code', 'Practical assignment codes, laboratory worksheets, and execution guides'),
(4, 'Slides & Presentations', 'slides-presentations', 'fa-file-powerpoint', 'Official professor lecture presentations and symposium decks'),
(5, 'Reference Books & Textbooks', 'reference-books', 'fa-bookmark', 'Academic reference book chapters, cheatsheets, and formula sheets'),
(6, 'Solution Manuals & Guides', 'solution-manuals', 'fa-check-double', 'Step-by-step solutions to textbook problem sets');

-- 5. Courses (Nexus University - Full Department Course Catalog)
INSERT INTO `courses` (`course_id`, `dept_id`, `semester_id`, `course_code`, `course_title`, `credit_hours`, `syllabus_summary`) VALUES
-- CSE Department
(1,  1, 1, 'CSE-101', 'Introduction to Computer Science',        3.0, 'Fundamentals of computing, hardware, software, number systems, and basic programming concepts.'),
(2,  1, 1, 'CSE-115', 'Structured Programming Language (C)',     3.0, 'C programming, arrays, pointers, structures, file handling and memory management.'),
(3,  1, 1, 'CSE-213', 'Object Oriented Programming (Java)',      3.0, 'OOP principles, inheritance, polymorphism, encapsulation, exception handling and JavaFX GUI.'),
(4,  1, 1, 'CSE-221', 'Data Structures',                         3.0, 'Arrays, linked lists, stacks, queues, trees, graphs, hashing and sorting algorithms.'),
(5,  1, 1, 'CSE-225', 'Algorithms & Complexity',                 3.0, 'Divide and conquer, greedy methods, dynamic programming, graph algorithms (Dijkstra, BFS/DFS).'),
(6,  1, 1, 'CSE-311', 'Database Management Systems',             3.0, 'Relational model, SQL DDL/DML, 3NF normalization, ER modeling, indexing, transaction processing.'),
(7,  1, 2, 'CSE-315', 'Software Engineering',                    3.0, 'SDLC models, requirements analysis, UML diagrams, testing strategies and project management.'),
(8,  1, 2, 'CSE-317', 'Computer Networks',                       3.0, 'OSI model, TCP/IP, routing protocols, socket programming and network security fundamentals.'),
(9,  1, 2, 'CSE-320', 'Operating Systems',                       3.0, 'Process management, memory management, file systems, scheduling algorithms, deadlocks.'),
(10, 1, 2, 'CSE-413', 'Artificial Intelligence',                 3.0, 'Search algorithms, knowledge representation, machine learning basics, expert systems and NLP.'),
(11, 1, 2, 'CSE-415', 'Web Engineering',                         3.0, 'HTML5, CSS3, JavaScript, PHP, MySQL web apps, REST APIs and modern frameworks.'),
(12, 1, 3, 'CSE-420', 'Compiler Design',                         3.0, 'Lexical analysis, syntax analysis, parsing, semantic analysis, intermediate code generation.'),
(13, 1, 3, 'CSE-421', 'Computer Architecture',                   3.0, 'CPU design, instruction sets, memory hierarchy, pipelining, parallel processing.'),
(14, 1, 3, 'CSE-430', 'Machine Learning',                        3.0, 'Supervised and unsupervised learning, regression, classification, neural networks, deep learning.'),
(15, 1, 3, 'CSE-499', 'Thesis & Final Year Project',             3.0, 'Final year research project, literature review, implementation, evaluation and defence presentation.'),
-- EEE Department
(16, 2, 1, 'EEE-101', 'Basic Electrical Engineering',            3.0, 'Kirchhoffs laws, circuit analysis, AC/DC circuits, network theorems and power calculations.'),
(17, 2, 1, 'EEE-103', 'Electronic Devices & Circuits',           3.0, 'Diodes, BJT, MOSFET, amplifiers, oscillators and logic gate implementations.'),
(18, 2, 1, 'EEE-201', 'Signals and Systems',                     3.0, 'Continuous/discrete time signals, Fourier transform, Laplace transform, z-transform.'),
(19, 2, 1, 'EEE-203', 'Electromagnetic Fields & Waves',          3.0, 'Electrostatics, magnetostatics, Maxwells equations, wave propagation and transmission lines.'),
(20, 2, 2, 'EEE-301', 'Digital Electronics',                     3.0, 'Combinational and sequential logic, flip-flops, counters, registers, and ALU design.'),
(21, 2, 2, 'EEE-303', 'Microprocessors & Interfacing',           3.0, '8086/8088 architecture, assembly language, I/O interfacing, interrupts and peripheral devices.'),
(22, 2, 2, 'EEE-305', 'Control Systems',                         3.0, 'Transfer functions, root locus, Bode plots, Nyquist criteria, PID controllers and stability analysis.'),
(23, 2, 3, 'EEE-401', 'Power Systems Analysis',                  3.0, 'Load flow, fault analysis, power system stability, protection relays and transformer design.'),
(24, 2, 3, 'EEE-403', 'Communication Engineering',               3.0, 'Analog/digital modulation, AM, FM, PCM, multiplexing, antenna and mobile communications.'),
(25, 2, 3, 'EEE-411', 'VLSI Design',                             3.0, 'CMOS logic, chip design flow, layout, simulation, timing analysis and HDL programming.'),
-- BBA Department
(26, 3, 1, 'BBA-101', 'Principles of Management',               3.0, 'Organizational behavior, planning, organizing, leadership, motivation and decision making.'),
(27, 3, 1, 'BBA-103', 'Business Mathematics',                    3.0, 'Linear equations, matrices, calculus applications in business, interest and financial calculations.'),
(28, 3, 1, 'BBA-201', 'Financial Accounting',                    3.0, 'Double entry bookkeeping, trial balance, income statement, balance sheet and cash flow.'),
(29, 3, 1, 'BBA-203', 'Microeconomics',                          3.0, 'Supply and demand, elasticity, consumer theory, production theory and market structures.'),
(30, 3, 2, 'BBA-205', 'Marketing Management',                    3.0, 'Market segmentation, 4Ps, consumer behavior, branding, digital marketing and CRM.'),
(31, 3, 2, 'BBA-207', 'Macroeconomics',                          3.0, 'National income, GDP, unemployment, inflation, monetary and fiscal policy in Bangladesh.'),
(32, 3, 2, 'BBA-301', 'Human Resource Management',               3.0, 'Recruitment, training, performance appraisal, compensation and labor laws.'),
(33, 3, 3, 'BBA-303', 'Business Finance',                        3.0, 'Time value of money, capital budgeting, CAPM, portfolio theory, risk and return analysis.'),
(34, 3, 3, 'BBA-401', 'Strategic Management',                    3.0, 'SWOT analysis, competitive strategy, business-level and corporate-level strategy formulation.'),
(35, 3, 3, 'BBA-403', 'Entrepreneurship Development',            3.0, 'Business plan, startup ecosystem, venture capital, innovation and SME development in Bangladesh.'),
-- LAW Department
(36, 4, 1, 'LAW-101', 'Constitutional Law of Bangladesh',        3.0, 'Fundamental rights, judicial review, parliament, executive powers and constitutional amendments.'),
(37, 4, 1, 'LAW-103', 'Law of Contract',                         3.0, 'Offer, acceptance, consideration, void/voidable contracts, breach and remedies.'),
(38, 4, 2, 'LAW-201', 'Criminal Law',                            3.0, 'Penal Code 1860, offences, punishments, criminal procedure and evidence law.'),
(39, 4, 2, 'LAW-203', 'Administrative Law',                      3.0, 'Delegated legislation, judicial review, rule of law and principles of natural justice.'),
(40, 4, 3, 'LAW-301', 'International Law',                       3.0, 'Sources of international law, treaties, state responsibility, ICJ and human rights law.'),
-- English Department
(41, 5, 1, 'ENG-101', 'Introduction to English Literature',      3.0, 'Overview of major literary periods, genres, authors and critical analysis techniques.'),
(42, 5, 1, 'ENG-103', 'Academic Writing & Research',             3.0, 'Paragraph writing, essay structure, citation styles APA/MLA, research methodology.'),
(43, 5, 2, 'ENG-201', 'British Literature',                      3.0, 'Shakespeare, Romantic poets, Victorian novelists and 20th century British authors.'),
(44, 5, 3, 'ENG-301', 'Linguistics',                             3.0, 'Phonetics, morphology, syntax, semantics, pragmatics and sociolinguistics.'),
-- Mathematics Department
(45, 6, 1, 'MAT-101', 'Differential Calculus',                   3.0, 'Limits, continuity, differentiation, chain rule, implicit differentiation, curve sketching.'),
(46, 6, 1, 'MAT-103', 'Coordinate Geometry & Vector Analysis',   3.0, 'Lines, conics, planes in 3D, vector operations, dot and cross products.'),
(47, 6, 2, 'MAT-201', 'Integral Calculus & Differential Equations', 3.0, 'Definite integrals, ODEs, vector calculus, series solutions and Laplace transform.'),
(48, 6, 2, 'MAT-203', 'Linear Algebra',                          3.0, 'Matrices, determinants, eigenvalues, vector spaces, linear transformations.'),
(49, 6, 3, 'MAT-301', 'Statistics & Probability',                3.0, 'Descriptive statistics, probability distributions, hypothesis testing, regression analysis.');

-- 6. Demo Users (Nexus University Scholars)
-- Password for all accounts: password123 (Hash: $2y$10$nttx21GusayWv1lKg/GTy.sZhAvlGfb1uY7uSyi5lji9yMktMYTFy)
INSERT INTO `users` (`user_id`, `role_id`, `dept_id`, `full_name`, `email`, `password_hash`, `academic_id`, `phone`, `bio`, `status`) VALUES
(1, 1, 1, 'Dr. Sarah Connor (Admin)', 'admin@campus.edu', '$2y$10$nttx21GusayWv1lKg/GTy.sZhAvlGfb1uY7uSyi5lji9yMktMYTFy', 'ADM-Nexus-01', '+880 1711-000001', 'Nexus System Administrator & Central Server Lead', 'active'),
(2, 2, 1, 'Alex Mercer (DB Updater)', 'updater@campus.edu', '$2y$10$nttx21GusayWv1lKg/GTy.sZhAvlGfb1uY7uSyi5lji9yMktMYTFy', 'MOD-Nexus-04', '+880 1711-000002', 'Nexus Database Updater & Course Catalog Moderator', 'active'),
(3, 3, 1, 'Prof. Robert Langdon', 'faculty@campus.edu', '$2y$10$nttx21GusayWv1lKg/GTy.sZhAvlGfb1uY7uSyi5lji9yMktMYTFy', 'FAC-CSE-89', '+880 1711-000003', 'Professor, Dept of CSE, Nexus University. Specialized in DBMS.', 'active'),
(4, 4, 1, 'Ethan Hunt (Student)', 'student@campus.edu', '$2y$10$nttx21GusayWv1lKg/GTy.sZhAvlGfb1uY7uSyi5lji9yMktMYTFy', '2201114042', '+880 1811-000004', 'Undergraduate Student, 45th Batch, Dept. of CSE, Premier University.', 'active');

-- 7. Seed Sample Academic Resources (course_id updated to match full course catalog)
INSERT INTO `resources` (`resource_id`, `title`, `description`, `course_id`, `category_id`, `uploaded_by`, `file_path`, `file_name`, `file_size`, `file_type`, `download_count`, `view_count`, `avg_rating`, `rating_count`, `status`, `moderated_by`) VALUES
(1, 'Complete SQL & Relational Normalization Master Guide',    'Comprehensive chapter-wise notes covering 1NF, 2NF, 3NF, BCNF, Functional Dependencies, and SQL Join optimizations with solved university questions.',   6, 1, 3, 'uploads/notes/sample_dbms_notes.pdf',        'sample_dbms_notes.pdf',       2457600, 'pdf', 142, 580, 5.00, 2, 'approved', 1),
(2, 'DBMS Spring Midterm Question Bank (2020-2025 Solved)',    'Last 5 years solved midterm papers with detailed ER diagrams, schema mapping, and relational algebra queries.',                                              6, 2, 3, 'uploads/notes/sample_question_bank.pdf',      'sample_question_bank.pdf',    1843200, 'pdf',  98, 320, 5.00, 1, 'approved', 2),
(3, 'Data Structures & Graph Algorithms Visual Cheatsheet',   'Compact revision sheets for Dijkstra, Bellman-Ford, Floyd-Warshall, 0/1 Knapsack, and LCS with pseudocodes.',                                              5, 1, 3, 'uploads/notes/sample_algo_cheatsheet.pdf',    'sample_algo_cheatsheet.pdf',  3145728, 'pdf',  64, 210, 4.00, 1, 'approved', 1),
(4, 'MySQL Stored Procedures & Triggers Laboratory Exercise', 'Hands-on lab manual containing sample code snippets for university database lab experiments and triggers.',                                                   6, 3, 4, 'uploads/notes/sample_lab_manual.pdf',         'sample_lab_manual.pdf',       1024000, 'pdf',  45, 130, 4.00, 1, 'approved', 2),
(5, 'OOP Java Complete Lecture Notes - All Chapters',         'Full semester lecture notes covering classes, objects, inheritance, polymorphism, exception handling and JavaFX GUI development.',                           3, 1, 3, 'uploads/notes/sample_oop_notes.pdf',          'sample_oop_notes.pdf',        2048000, 'pdf',  78, 245, 4.50, 2, 'approved', 1),
(6, 'Principles of Management - BBA Complete Guide',          'Comprehensive notes covering all management theories, organizational behavior, leadership styles and decision-making frameworks.',                           26, 1, 3, 'uploads/notes/sample_bba_notes.pdf',          'sample_bba_notes.pdf',        1536000, 'pdf',  55, 195, 4.00, 1, 'approved', 2),
(7, 'Signals & Systems - EEE Lecture Slides (Full Semester)', 'Complete professor-prepared presentation slides covering Fourier transform, Laplace transform, and z-transform with solved examples.',                      18, 4, 3, 'uploads/notes/sample_eee_slides.pdf',          'sample_eee_slides.pdf',       3072000, 'pdf',  33, 160, 4.50, 2, 'approved', 1),
(8, 'Computer Networks Protocol Reference Cheatsheet',        'Quick reference cards for OSI layers, TCP/IP stack, routing protocols, subnetting and socket programming examples.',                                         8, 5, 3, 'uploads/notes/sample_networks.pdf',           'sample_networks.pdf',         1280000, 'pdf',  29, 120, 3.50, 2, 'approved', 2);

-- 8. Seed Sample Reviews (covering all seed resources)
INSERT INTO `reviews` (`review_id`, `resource_id`, `user_id`, `rating`, `review_text`, `created_at`) VALUES
(1, 1, 4, 5, 'Exceptional quality notes! Helped me secure an A in the database normalization quiz.', '2026-02-10 14:30:00'),
(2, 1, 2, 5, 'Verified by moderator. Accurate definitions and cleanly formatted relational schemas.', '2026-02-11 09:15:00'),
(3, 2, 4, 5, 'All past exam questions are accurate and properly mapped to course outcomes.', '2026-02-15 11:20:00'),
(4, 3, 4, 4, 'Very intuitive diagrams for graph traversal. Highly recommended for CSE-221.', '2026-02-18 16:45:00'),
(5, 4, 3, 4, 'Good implementation examples for lab demonstrations. Covers triggers well.', '2026-02-20 18:00:00'),
(6, 5, 4, 5, 'Best OOP Java notes I have found. All chapters covered with clear examples.', '2026-03-01 10:30:00'),
(7, 5, 2, 4, 'Comprehensive and well-structured. Good coverage of exception handling.', '2026-03-02 12:00:00'),
(8, 6, 4, 4, 'Very helpful for BBA management exam preparation. Clear theories.', '2026-03-05 09:00:00'),
(9, 7, 4, 5, 'Professor Langdon slides are excellent. The z-transform derivations are spot on.', '2026-03-08 14:00:00'),
(10, 7, 2, 4, 'Slides are very clear with solved numerical examples. EEE students will love it.', '2026-03-09 11:00:00');

-- 9. Seed Sample Comments
INSERT INTO `comments` (`comment_id`, `resource_id`, `user_id`, `parent_id`, `comment_text`, `created_at`) VALUES
(1, 1, 4, NULL, 'Does this cover multi-valued dependencies (4NF) as well?', '2026-02-12 10:00:00'),
(2, 1, 3, 1, 'Yes Ethan, check Chapter 7 of the document on page 42 for 4NF and 5NF.', '2026-02-12 11:30:00');

-- 10. Seed Sample Bookmarks
INSERT INTO `bookmarks` (`user_id`, `resource_id`) VALUES
(4, 1), (4, 2), (4, 3), (4, 5), (4, 7);

-- 11. Seed Sample Activity Logs
INSERT INTO `activity_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`) VALUES
(1, 'SYSTEM_INITIALIZED', 'system', 1, 'Campus Notes Sharing Portal initialized with relational schema.', '127.0.0.1'),
(3, 'RESOURCE_UPLOADED', 'resources', 1, 'Uploaded SQL & Normalization Guide', '127.0.0.1'),
(2, 'RESOURCE_APPROVED', 'resources', 1, 'Approved note ID: 1 by updater', '127.0.0.1');
