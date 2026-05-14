-- ============================================
-- QUIZ GENERATOR DATABASE SCHEMA
-- ============================================
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('teacher','student') NOT NULL DEFAULT 'student',
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `time_limit_minutes` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false','short_answer') NOT NULL,
  `points` int(11) NOT NULL DEFAULT 1,
  `order_index` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `question_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `order_index` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `total_points` int(11) DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) DEFAULT NULL,
  `short_answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_earned` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attempt_id` (`attempt_id`),
  KEY `question_id` (`question_id`),
  KEY `selected_option_id` (`selected_option_id`),
  CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answers_ibfk_3` FOREIGN KEY (`selected_option_id`) REFERENCES `question_options` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

-- STORED PROCEDURES
DELIMITER $$
CREATE PROCEDURE `sp_register_user`(IN p_username VARCHAR(50), IN p_email VARCHAR(100), IN p_password_hash VARCHAR(255), IN p_role ENUM('teacher','student'), IN p_first_name VARCHAR(50), IN p_last_name VARCHAR(50))
BEGIN
    INSERT INTO users (username, email, password_hash, role, first_name, last_name) VALUES (p_username, p_email, p_password_hash, p_role, p_first_name, p_last_name);
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_get_user_by_username`(IN p_username VARCHAR(50))
BEGIN
    SELECT * FROM users WHERE username = p_username LIMIT 1;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_create_quiz`(IN p_title VARCHAR(200), IN p_description TEXT, IN p_teacher_id INT, IN p_time_limit_minutes INT)
BEGIN
    INSERT INTO quizzes (title, description, teacher_id, time_limit_minutes) VALUES (p_title, p_description, p_teacher_id, p_time_limit_minutes);
    SELECT LAST_INSERT_ID() AS quiz_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_add_question`(IN p_quiz_id INT, IN p_question_text TEXT, IN p_question_type ENUM('multiple_choice','true_false','short_answer'), IN p_points INT, IN p_order_index INT)
BEGIN
    INSERT INTO questions (quiz_id, question_text, question_type, points, order_index) VALUES (p_quiz_id, p_question_text, p_question_type, p_points, p_order_index);
    SELECT LAST_INSERT_ID() AS question_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_add_option`(IN p_question_id INT, IN p_option_text VARCHAR(255), IN p_is_correct TINYINT, IN p_order_index INT)
BEGIN
    INSERT INTO question_options (question_id, option_text, is_correct, order_index) VALUES (p_question_id, p_option_text, p_is_correct, p_order_index);
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_start_attempt`(IN p_quiz_id INT, IN p_student_id INT)
BEGIN
    INSERT INTO quiz_attempts (quiz_id, student_id) VALUES (p_quiz_id, p_student_id);
    SELECT LAST_INSERT_ID() AS attempt_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_submit_answer`(IN p_attempt_id INT, IN p_question_id INT, IN p_selected_option_id INT, IN p_short_answer_text TEXT)
BEGIN
    DECLARE v_is_correct TINYINT DEFAULT NULL;
    DECLARE v_points_earned INT DEFAULT 0;
    DECLARE v_question_type ENUM('multiple_choice','true_false','short_answer');
    DECLARE v_points INT DEFAULT 1;

    SELECT question_type, points INTO v_question_type, v_points FROM questions WHERE id = p_question_id;

    IF v_question_type IN ('multiple_choice', 'true_false') AND p_selected_option_id IS NOT NULL THEN
        SELECT is_correct INTO v_is_correct FROM question_options WHERE id = p_selected_option_id;
        SET v_points_earned = IF(v_is_correct = 1, v_points, 0);
    END IF;

    IF v_question_type = 'short_answer' THEN
        SET v_is_correct = NULL;
        SET v_points_earned = NULL;
    END IF;

    INSERT INTO answers (attempt_id, question_id, selected_option_id, short_answer_text, is_correct, points_earned)
    VALUES (p_attempt_id, p_question_id, p_selected_option_id, p_short_answer_text, v_is_correct, v_points_earned);
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_complete_attempt`(IN p_attempt_id INT)
BEGIN
    DECLARE v_total_points INT;
    DECLARE v_score INT;

    SELECT SUM(points) INTO v_total_points FROM questions WHERE quiz_id = (SELECT quiz_id FROM quiz_attempts WHERE id = p_attempt_id);
    SELECT COALESCE(SUM(points_earned), 0) INTO v_score FROM answers WHERE attempt_id = p_attempt_id;

    UPDATE quiz_attempts SET completed_at = NOW(), score = v_score, total_points = v_total_points, is_completed = 1 WHERE id = p_attempt_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_get_teacher_quizzes`(IN p_teacher_id INT)
BEGIN
    SELECT q.*, (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS question_count
    FROM quizzes q WHERE q.teacher_id = p_teacher_id ORDER BY q.created_at DESC;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_get_quiz_by_id`(IN p_quiz_id INT)
BEGIN
    SELECT q.*, u.username AS teacher_name FROM quizzes q JOIN users u ON q.teacher_id = u.id WHERE q.id = p_quiz_id LIMIT 1;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_update_quiz`(IN p_quiz_id INT, IN p_title VARCHAR(200), IN p_description TEXT, IN p_time_limit_minutes INT, IN p_is_published TINYINT, IN p_teacher_id INT)
BEGIN
    UPDATE quizzes SET title = p_title, description = p_description, time_limit_minutes = p_time_limit_minutes, is_published = p_is_published, updated_at = NOW() WHERE id = p_quiz_id AND teacher_id = p_teacher_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_delete_quiz`(IN p_quiz_id INT, IN p_teacher_id INT)
BEGIN
    DELETE FROM quizzes WHERE id = p_quiz_id AND teacher_id = p_teacher_id;
    SELECT ROW_COUNT() AS affected_rows;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `sp_get_questions_with_options`(IN p_quiz_id INT)
BEGIN
    SELECT q.*, o.id AS option_id, o.option_text, o.is_correct, o.order_index
    FROM questions q LEFT JOIN question_options o ON q.id = o.question_id
    WHERE q.quiz_id = p_quiz_id ORDER BY q.order_index, o.order_index;
END$$
DELIMITER ;

-- TRIGGERS
DELIMITER $$
CREATE TRIGGER `trg_after_attempt_complete` AFTER UPDATE ON `quiz_attempts` FOR EACH ROW
BEGIN
    IF NEW.is_completed = 1 AND OLD.is_completed = 0 THEN
        INSERT INTO audit_logs (table_name, record_id, action, old_values, new_values)
        VALUES ('quiz_attempts', NEW.id, 'COMPLETED', CONCAT('score=', OLD.score, ', completed_at=', OLD.completed_at), CONCAT('score=', NEW.score, ', completed_at=', NEW.completed_at));
    END IF;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_after_answer_insert` AFTER INSERT ON `answers` FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (table_name, record_id, action, old_values, new_values)
    VALUES ('answers', NEW.id, 'ANSWER_SUBMITTED', NULL, CONCAT('attempt_id=', NEW.attempt_id, ', question_id=', NEW.question_id, ', points_earned=', NEW.points_earned));
END$$
DELIMITER ;