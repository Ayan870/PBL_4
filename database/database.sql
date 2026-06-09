-- PROJECXIA - Complete Database Schema
-- Consolidation of all modules and patches

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. DEPARTMENTS
DROP TABLE IF EXISTS departments;
CREATE TABLE departments (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  code VARCHAR(32)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dept_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PROGRAMS
DROP TABLE IF EXISTS programs;
CREATE TABLE programs (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_id INT UNSIGNED NOT NULL,
  name          VARCHAR(191) NOT NULL,
  code          VARCHAR(32)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_program (department_id, code),
  CONSTRAINT fk_programs_dept
    FOREIGN KEY (department_id) REFERENCES departments(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. SEMESTERS
DROP TABLE IF EXISTS semesters;
CREATE TABLE semesters (
  id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  number     TINYINT UNSIGNED NOT NULL,
  session    VARCHAR(32)      NOT NULL,
  year       SMALLINT UNSIGNED NOT NULL,
  start_date DATE NULL,
  end_date   DATE NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_semester (number, session, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. USERS
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                 VARCHAR(191) NOT NULL,
  email                VARCHAR(191) NULL,
  password_hash        VARCHAR(255) NOT NULL,
  role                 ENUM('student','supervisor','pbl_manager','chairman','evaluator') NOT NULL,
  roll_number          VARCHAR(32)  NULL,
  program_id           INT UNSIGNED NULL,
  department_id        INT UNSIGNED NULL,
  semester_id          INT UNSIGNED NULL,
  is_temporary         TINYINT(1)   NOT NULL DEFAULT 0,
  must_change_password TINYINT(1)   NOT NULL DEFAULT 0,
  pbl_manager_dept_id  INT AS (CASE WHEN role = 'pbl_manager' THEN department_id ELSE NULL END) STORED,
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email       (email),
  UNIQUE KEY uq_roll_number (roll_number),
  UNIQUE KEY uq_one_pbl_manager_per_dept (pbl_manager_dept_id),
  CONSTRAINT fk_users_program
    FOREIGN KEY (program_id)  REFERENCES programs(id)  ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_users_dept
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_users_semester
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CLASSES
DROP TABLE IF EXISTS classes;
CREATE TABLE classes (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  program_id  INT UNSIGNED NOT NULL,
  semester_id INT UNSIGNED NOT NULL,
  section     VARCHAR(8)   NOT NULL DEFAULT 'A',
  PRIMARY KEY (id),
  UNIQUE KEY uq_class (program_id, semester_id, section),
  CONSTRAINT fk_classes_program
    FOREIGN KEY (program_id)  REFERENCES programs(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_classes_semester
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6.5 SUBJECTS MASTER (Predefined list)
DROP TABLE IF EXISTS subjects_master;
CREATE TABLE subjects_master (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(191) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subject_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. PBL SUBJECTS
DROP TABLE IF EXISTS pbl_subjects;
CREATE TABLE pbl_subjects (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  semester_id INT UNSIGNED NOT NULL,
  program_id  INT UNSIGNED NOT NULL,
  title       VARCHAR(191) NOT NULL,
  assigned_by INT UNSIGNED NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pbl_subject (semester_id, program_id, title),
  CONSTRAINT fk_pbl_semester
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pbl_program
    FOREIGN KEY (program_id)  REFERENCES programs(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_pbl_assigned_by
    FOREIGN KEY (assigned_by) REFERENCES users(id)     ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CLASS SUPERVISORS
DROP TABLE IF EXISTS class_supervisors;
CREATE TABLE class_supervisors (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  class_id       INT UNSIGNED NOT NULL,
  supervisor_id  INT UNSIGNED NOT NULL,
  pbl_subject_id INT UNSIGNED NOT NULL,
  assigned_by    INT UNSIGNED NULL,
  assigned_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_class_sup (class_id, supervisor_id, pbl_subject_id),
  CONSTRAINT fk_cs_class
    FOREIGN KEY (class_id)       REFERENCES classes(id)      ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cs_supervisor
    FOREIGN KEY (supervisor_id)  REFERENCES users(id)        ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cs_subject
    FOREIGN KEY (pbl_subject_id) REFERENCES pbl_subjects(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. GROUPS
DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  class_id       INT UNSIGNED NOT NULL,
  pbl_subject_id INT UNSIGNED NOT NULL,
  name           VARCHAR(191) NOT NULL,
  created_by     INT UNSIGNED NOT NULL,
  status         ENUM('forming','active','completed') NOT NULL DEFAULT 'forming',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_groups_class
    FOREIGN KEY (class_id)       REFERENCES classes(id)      ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_groups_subject
    FOREIGN KEY (pbl_subject_id) REFERENCES pbl_subjects(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_groups_created_by
    FOREIGN KEY (created_by)     REFERENCES users(id)        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. GROUP MEMBERS
DROP TABLE IF EXISTS group_members;
CREATE TABLE group_members (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id      INT UNSIGNED NOT NULL,
  student_id    INT UNSIGNED NOT NULL,
  role          ENUM('leader','member')               NOT NULL DEFAULT 'member',
  invite_status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  joined_at     TIMESTAMP NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_group_member (group_id, student_id),
  CONSTRAINT fk_gm_group
    FOREIGN KEY (group_id)   REFERENCES `groups`(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gm_student
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. PROPOSALS
DROP TABLE IF EXISTS proposals;
CREATE TABLE proposals (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  group_id         INT UNSIGNED     NOT NULL,
  supervisor_id    INT UNSIGNED     NULL,
  version_number   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  title            VARCHAR(255)     NOT NULL,
  category         VARCHAR(100)     NULL,
  description      TEXT NULL,
  objectives       TEXT NULL,
  methodology      TEXT NULL,
  tools            VARCHAR(255) NULL,
  status           ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT NULL,
  reviewed_by      INT UNSIGNED NULL,
  submitted_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at      TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proposal_version (group_id, version_number),
  CONSTRAINT fk_proposals_group
    FOREIGN KEY (group_id)    REFERENCES `groups`(id) ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_proposals_supervisor
    FOREIGN KEY (supervisor_id) REFERENCES users(id)  ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_proposals_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users(id)    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PROPOSAL ATTACHMENTS
DROP TABLE IF EXISTS proposal_attachments;
CREATE TABLE proposal_attachments (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  proposal_id  INT UNSIGNED NOT NULL,
  file_name    VARCHAR(255) NOT NULL,
  file_path    VARCHAR(500) NOT NULL,
  file_type    VARCHAR(100) NULL,
  file_size_kb INT UNSIGNED NULL,
  uploaded_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_pa_proposal
    FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. EVALUATIONS (Individual Student Marks)
DROP TABLE IF EXISTS evaluations;
CREATE TABLE evaluations (
  id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  student_id      INT UNSIGNED     NOT NULL,
  supervisor_id   INT UNSIGNED     NOT NULL,
  eval_type       VARCHAR(50)      NOT NULL,
  tech_score      DECIMAL(5,2)     NULL,
  overall_rating  TINYINT UNSIGNED NULL DEFAULT 5,
  feedback        TEXT             NULL,
  recommendations TEXT             NULL,
  created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_eval_student
    FOREIGN KEY (student_id)    REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_eval_supervisor
    FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. MID EVALUATIONS (Group Summary)
DROP TABLE IF EXISTS mid_evaluations;
CREATE TABLE mid_evaluations (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  group_id         INT UNSIGNED     NOT NULL,
  pbl_subject_id   INT UNSIGNED     NOT NULL,
  evaluated_by     INT UNSIGNED     NOT NULL,
  marks            TINYINT UNSIGNED NULL,
  feedback         TEXT NULL,
  progress_percent TINYINT UNSIGNED NULL,
  evaluation_date  DATE NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mid_eval (group_id, pbl_subject_id),
  CONSTRAINT fk_mid_group
    FOREIGN KEY (group_id)       REFERENCES `groups`(id)     ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_mid_subject
    FOREIGN KEY (pbl_subject_id) REFERENCES pbl_subjects(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_mid_evaluator
    FOREIGN KEY (evaluated_by)   REFERENCES users(id)        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. FINAL EVALUATION SESSIONS
DROP TABLE IF EXISTS final_eval_sessions;
CREATE TABLE final_eval_sessions (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_id INT UNSIGNED NOT NULL,
  semester_id   INT UNSIGNED NOT NULL,
  evaluator_id  INT UNSIGNED NOT NULL,
  status        ENUM('running','closed') DEFAULT 'running',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_fes_dept      FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
  CONSTRAINT fk_fes_semester  FOREIGN KEY (semester_id)   REFERENCES semesters(id)   ON DELETE CASCADE,
  CONSTRAINT fk_fes_evaluator FOREIGN KEY (evaluator_id)  REFERENCES users(id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. FINAL EVALUATIONS
DROP TABLE IF EXISTS final_evaluations;
CREATE TABLE final_evaluations (
  id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  group_id        INT UNSIGNED     NOT NULL,
  pbl_subject_id  INT UNSIGNED     NOT NULL,
  evaluator_id    INT UNSIGNED     NOT NULL,
  marks_out_of_20 TINYINT UNSIGNED NULL,
  feedback        TEXT NULL,
  evaluation_date DATE NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_final_eval (group_id, pbl_subject_id),
  CONSTRAINT fk_final_group
    FOREIGN KEY (group_id)       REFERENCES `groups`(id)     ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_final_subject
    FOREIGN KEY (pbl_subject_id) REFERENCES pbl_subjects(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_final_evaluator
    FOREIGN KEY (evaluator_id)   REFERENCES users(id)        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- End of table definitions
-- =============================================

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- INSERT INITIAL DATA
-- =============================================

-- Departments
INSERT IGNORE INTO departments (name, code) VALUES
('CS & IT',  'CSIT'),
('Medical',  'MED'),
('Business', 'BUS');

-- Programs - CS & IT
INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BSCS M', 'BSCSM' FROM departments WHERE code = 'CSIT';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BS IT M', 'BSITM' FROM departments WHERE code = 'CSIT';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BS AI M', 'BSAIM' FROM departments WHERE code = 'CSIT';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BS CBS M', 'BSCBSM' FROM departments WHERE code = 'CSIT';

-- Programs - Medical
INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'MBBS', 'MBBS' FROM departments WHERE code = 'MED';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'Pharmacy', 'PHARM' FROM departments WHERE code = 'MED';

-- Programs - Business
INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BBA M', 'BBAM' FROM departments WHERE code = 'BUS';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BBS M', 'BSBBSM' FROM departments WHERE code = 'BUS';

-- Current semester
INSERT IGNORE INTO semesters (number, session, year, start_date, end_date)
VALUES (4, 'Fall', 2024, '2024-09-01', '2024-12-31');

-- Predefined Subjects
INSERT IGNORE INTO subjects_master (title) VALUES
('OOP'),
('PF'),
('DSA'),
('Database'),
('Web dev'),
('Mobile App dev'),
('Machine Learning'),
('Deep Learning'),
('Robotic AI'),
('Intro to AI');

-- =============================================
-- Done! Consolidated schema created.
-- =============================================
