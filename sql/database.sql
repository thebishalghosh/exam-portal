-- Create Database
CREATE DATABASE IF NOT EXISTS online_assessment;
USE online_assessment;

-- --------------------------------------------
-- 1. Users (Students Table)
-- --------------------------------------------
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       email VARCHAR(150) UNIQUE NOT NULL,
                       otp VARCHAR(10),
                       otp_expiry DATETIME,
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                       updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- --------------------------------------------
-- 2. Admin Table
-- --------------------------------------------
CREATE TABLE admin (
                       admin_id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(100) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------
-- 3. Exams Table
-- --------------------------------------------
CREATE TABLE exams (
                       exam_id INT AUTO_INCREMENT PRIMARY KEY,
                       title VARCHAR(255) NOT NULL,
                       description TEXT,
                       duration INT NOT NULL,                       -- duration in minutes
                       start_time DATETIME NOT NULL,
                       end_time DATETIME NOT NULL,
                       status ENUM('active','inactive') DEFAULT 'active',
                       created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------
-- 4. Questions Table
-- --------------------------------------------
CREATE TABLE questions (
                           question_id INT AUTO_INCREMENT PRIMARY KEY,
                           exam_id INT NOT NULL,
                           type ENUM('mcq','descriptive') NOT NULL,
                           question_text TEXT NOT NULL,
                           options JSON DEFAULT NULL,                   -- for MCQ options
                           correct_answer VARCHAR(255) DEFAULT NULL,    -- MCQ correct answer
                           marks INT NOT NULL DEFAULT 1,

                           FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

-- --------------------------------------------
-- 5. Submissions Table
-- --------------------------------------------
CREATE TABLE submissions (
                             submission_id INT AUTO_INCREMENT PRIMARY KEY,
                             exam_id INT NOT NULL,
                             user_id INT NOT NULL,
                             submitted_answers JSON,                      -- stores all answers
                             status ENUM('Completed','Failed','Disqualified') DEFAULT 'Completed',
                             start_time DATETIME,
                             end_time DATETIME,
                             created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                             FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
                             FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- --------------------------------------------
-- 6. Activity Logs (Optional but recommended)
-- --------------------------------------------
CREATE TABLE activity_logs (
                               log_id INT AUTO_INCREMENT PRIMARY KEY,
                               user_id INT NOT NULL,
                               exam_id INT NOT NULL,
                               event_type VARCHAR(255),                     -- blur, alt+tab, resize, etc.
                               timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,

                               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                               FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

-- --------------------------------------------
CREATE TABLE exam_assignments (
                                  assignment_id INT AUTO_INCREMENT PRIMARY KEY,
                                  exam_id INT NOT NULL,
                                  candidate_id INT NOT NULL,  -- Added this column
                                  candidate_email VARCHAR(150) NOT NULL,
                                  candidate_source ENUM('internal', 'interview') NOT NULL,
                                  status ENUM('assigned', 'started', 'completed', 'disqualified') DEFAULT 'assigned',
                                  score DECIMAL(5, 2) NULL,
                                  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,

                                  FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    -- We add a unique constraint to prevent assigning the same exam to the same person twice
                                  UNIQUE KEY unique_assignment (exam_id, candidate_email)
);

ALTER TABLE submissions ADD COLUMN marks_breakdown JSON DEFAULT NULL AFTER submitted_answers;


-- Add exam_type column to exams table
ALTER TABLE exams
    ADD COLUMN exam_type ENUM('open', 'interview', 'internal') NOT NULL DEFAULT 'internal' AFTER status;

-- Add 'open_registration' to the ENUM for candidate_source in exam_assignments
-- Note: This might require a temporary table or direct ALTER depending on MySQL version and existing data.
-- A safer way if you have existing data:
-- 1. ALTER TABLE exam_assignments CHANGE COLUMN candidate_source candidate_source ENUM('internal', 'interview', 'open_registration') NOT NULL DEFAULT 'internal';
-- 2. If you have a lot of data, you might need to do it in two steps:
--    ALTER TABLE exam_assignments ADD COLUMN candidate_source_new ENUM('internal', 'interview', 'open_registration') NOT NULL DEFAULT 'internal';
--    UPDATE exam_assignments SET candidate_source_new = candidate_source;
--    ALTER TABLE exam_assignments DROP COLUMN candidate_source;
--    ALTER TABLE exam_assignments CHANGE COLUMN candidate_source_new candidate_source ENUM('internal', 'interview', 'open_registration') NOT NULL DEFAULT 'internal';
-- For now, assuming you can directly alter or have little data:
ALTER TABLE exam_assignments
    MODIFY COLUMN candidate_source ENUM('internal', 'interview', 'open_registration') NOT NULL DEFAULT 'internal';


ALTER TABLE users ADD COLUMN roll_number VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN college_name VARCHAR(255) DEFAULT NULL;

-- Add columns for Magic Link functionality to users table
ALTER TABLE users
    ADD COLUMN magic_link_token VARCHAR(255) NULL DEFAULT NULL UNIQUE,
    ADD COLUMN magic_link_expiry DATETIME NULL DEFAULT NULL;