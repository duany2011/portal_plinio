CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(80) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 full_name VARCHAR(150) NOT NULL,
 role ENUM('admin','director','coordinator','teacher') NOT NULL DEFAULT 'teacher',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS academic_years (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(30) NOT NULL UNIQUE,
 active TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS sections (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 academic_year_id INT UNSIGNED NOT NULL,
 grade_name VARCHAR(30) NOT NULL,
 section_name VARCHAR(30) NOT NULL,
 technical_track VARCHAR(150) NULL,
 professional_family VARCHAR(150) NULL,
 UNIQUE KEY uq_section(academic_year_id,grade_name,section_name),
 CONSTRAINT fk_section_year FOREIGN KEY(academic_year_id) REFERENCES academic_years(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS subjects (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NULL,
 name VARCHAR(180) NOT NULL,
 type ENUM('curricular','technical') NOT NULL DEFAULT 'curricular',
 active TINYINT(1) NOT NULL DEFAULT 1,
 UNIQUE KEY uq_subject_name(name,type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS students (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 section_id INT UNSIGNED NOT NULL,
 order_no INT NULL,
 sigerd_id VARCHAR(80) NULL UNIQUE,
 names VARCHAR(150) NOT NULL,
 last_names VARCHAR(150) NOT NULL,
 phone VARCHAR(40) NULL,
 observations TEXT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_student_section FOREIGN KEY(section_id) REFERENCES sections(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS teacher_assignments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 section_id INT UNSIGNED NOT NULL,
 subject_id INT UNSIGNED NOT NULL,
 UNIQUE KEY uq_assignment(user_id,section_id,subject_id),
 CONSTRAINT fk_ta_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_ta_section FOREIGN KEY(section_id) REFERENCES sections(id) ON DELETE CASCADE,
 CONSTRAINT fk_ta_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS grades (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 subject_id INT UNSIGNED NOT NULL,
 period ENUM('P1','P2','P3','P4') NOT NULL,
 score DECIMAL(5,2) NULL,
 updated_by INT UNSIGNED NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_grade(student_id,subject_id,period),
 CONSTRAINT fk_grade_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 CONSTRAINT fk_grade_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
 CONSTRAINT fk_grade_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS attendance (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 period ENUM('P1','P2','P3','P4') NOT NULL,
 present INT NULL,
 absent INT NULL,
 updated_by INT UNSIGNED NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_attendance(student_id,period),
 CONSTRAINT fk_att_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 CONSTRAINT fk_att_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS student_status (
 student_id INT UNSIGNED PRIMARY KEY,
 status ENUM('Promovido/a','Repitente') NULL,
 final_condition VARCHAR(255) NULL,
 updated_by INT UNSIGNED NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_status_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 CONSTRAINT fk_status_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS learning_results (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 subject_id INT UNSIGNED NOT NULL,
 ra_code VARCHAR(20) NOT NULL,
 title VARCHAR(255) NOT NULL,
 sort_order INT NOT NULL DEFAULT 0,
 UNIQUE KEY uq_ra(subject_id,ra_code),
 CONSTRAINT fk_ra_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS ra_scores (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id INT UNSIGNED NOT NULL,
 learning_result_id INT UNSIGNED NOT NULL,
 score DECIMAL(5,2) NULL,
 updated_by INT UNSIGNED NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_ra_score(student_id,learning_result_id),
 CONSTRAINT fk_ras_student FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
 CONSTRAINT fk_ras_result FOREIGN KEY(learning_result_id) REFERENCES learning_results(id) ON DELETE CASCADE,
 CONSTRAINT fk_ras_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO academic_years(name,active) VALUES('2026-2027',1);
INSERT IGNORE INTO subjects(name,type) VALUES
('Lengua Española','curricular'),
('Lenguas Extranjeras (Inglés)','curricular'),
('Lenguas Extranjeras (Francés)','curricular'),
('Matemática','curricular'),
('Ciencias Sociales','curricular'),
('Ciencias de la Naturaleza','curricular'),
('Educación Artística','curricular'),
('Educación Física','curricular'),
('Formación Integral Humana y Religiosa','curricular');
