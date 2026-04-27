SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. DEPARTMENTS
CREATE TABLE IF NOT EXISTS departments (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  code VARCHAR(32)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dept_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PROGRAMS
CREATE TABLE IF NOT EXISTS programs (
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
CREATE TABLE IF NOT EXISTS semesters (
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
CREATE TABLE IF NOT EXISTS users (
  id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name                 VARCHAR(191) NOT NULL,
  email                VARCHAR(191) NULL,
  password_hash        VARCHAR(255) NOT NULL,
  role                 ENUM('student','supervisor','pbl_manager','chairman','evaluator') NOT NULL,
  roll_number          VARCHAR(32)  NULL,
  program_id           INT UNSIGNED NULL,
  semester_id          INT UNSIGNED NULL,
  is_temporary         TINYINT(1)   NOT NULL DEFAULT 0,
  must_change_password TINYINT(1)   NOT NULL DEFAULT 0,
  created_at           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_email       (email),
  UNIQUE KEY uq_roll_number (roll_number),
  CONSTRAINT fk_users_program
    FOREIGN KEY (program_id)  REFERENCES programs(id)  ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_users_semester
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CLASSES
CREATE TABLE IF NOT EXISTS classes (
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

-- 6. PBL SUBJECTS
CREATE TABLE IF NOT EXISTS pbl_subjects (
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
CREATE TABLE IF NOT EXISTS class_supervisors (
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
CREATE TABLE IF NOT EXISTS `groups` (
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
CREATE TABLE IF NOT EXISTS group_members (
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
CREATE TABLE IF NOT EXISTS proposals (
  id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  group_id         INT UNSIGNED     NOT NULL,
  version_number   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  title            VARCHAR(255)     NOT NULL,
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
  CONSTRAINT fk_proposals_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users(id)    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. PROPOSAL ATTACHMENTS
CREATE TABLE IF NOT EXISTS proposal_attachments (
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

-- 12. MID EVALUATIONS
CREATE TABLE IF NOT EXISTS mid_evaluations (
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

-- 13. FINAL EVALUATIONS
CREATE TABLE IF NOT EXISTS final_evaluations (
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

-- 14. CHAT ROOMS
CREATE TABLE IF NOT EXISTS chat_rooms (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  class_id       INT UNSIGNED NOT NULL,
  pbl_subject_id INT UNSIGNED NULL,
  type           ENUM('direct','group_class') NOT NULL DEFAULT 'direct',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_rooms_class
    FOREIGN KEY (class_id)       REFERENCES classes(id)      ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_rooms_subject
    FOREIGN KEY (pbl_subject_id) REFERENCES pbl_subjects(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. CHAT PARTICIPANTS
CREATE TABLE IF NOT EXISTS chat_participants (
  id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  room_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_participant (room_id, user_id),
  CONSTRAINT fk_cp_room
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cp_user
    FOREIGN KEY (user_id) REFERENCES users(id)      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. MESSAGES
CREATE TABLE IF NOT EXISTS messages (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  room_id   INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  content   TEXT         NOT NULL,
  is_read   TINYINT(1)   NOT NULL DEFAULT 0,
  sent_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_msg_room
    FOREIGN KEY (room_id)   REFERENCES chat_rooms(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_msg_sender
    FOREIGN KEY (sender_id) REFERENCES users(id)      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. MESSAGE ATTACHMENTS
CREATE TABLE IF NOT EXISTS message_attachments (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id  INT UNSIGNED NOT NULL,
  file_name   VARCHAR(255) NOT NULL,
  file_path   VARCHAR(500) NOT NULL,
  uploaded_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT fk_ma_message
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
SELECT id, 'BSCS Morning', 'BSCSM' FROM departments WHERE code = 'CSIT';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BSCS Evening', 'BSCSE' FROM departments WHERE code = 'CSIT';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BS IT', 'BSIT' FROM departments WHERE code = 'CSIT';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BS AI', 'BSAI' FROM departments WHERE code = 'CSIT';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BS CBS', 'BSCBS' FROM departments WHERE code = 'CSIT';

-- Programs - Medical
INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'MBBS', 'MBBS' FROM departments WHERE code = 'MED';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'Pharmacy', 'PHARM' FROM departments WHERE code = 'MED';

-- Programs - Business
INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BBA', 'BBA' FROM departments WHERE code = 'BUS';

INSERT IGNORE INTO programs (department_id, name, code)
SELECT id, 'BBS', 'BBS' FROM departments WHERE code = 'BUS';

-- Current semester
INSERT IGNORE INTO semesters (number, session, year, start_date, end_date)
VALUES (4, 'Fall', 2024, '2024-09-01', '2024-12-31');

-- =============================================
-- Done! All 17 tables + initial data inserted
-- =============================================
