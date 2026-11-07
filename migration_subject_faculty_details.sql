-- Add faculty contact/details to each course assignment.
-- Safe to rerun: each column is added only if it is missing.
USE courseplan;

SET @has_faculty_name = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'faculty_name'
);
SET @sql = IF(@has_faculty_name = 0,
  'ALTER TABLE subjects ADD COLUMN faculty_name VARCHAR(255) NOT NULL DEFAULT '''' AFTER fid',
  'SELECT 1');
PREPARE faculty_stmt FROM @sql;
EXECUTE faculty_stmt;
DEALLOCATE PREPARE faculty_stmt;

SET @has_faculty_username = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'faculty_username'
);
SET @sql = IF(@has_faculty_username = 0,
  'ALTER TABLE subjects ADD COLUMN faculty_username VARCHAR(255) NOT NULL DEFAULT '''' AFTER faculty_name',
  'SELECT 1');
PREPARE faculty_stmt FROM @sql;
EXECUTE faculty_stmt;
DEALLOCATE PREPARE faculty_stmt;

SET @has_faculty_email = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'faculty_email'
);
SET @sql = IF(@has_faculty_email = 0,
  'ALTER TABLE subjects ADD COLUMN faculty_email VARCHAR(255) NOT NULL DEFAULT '''' AFTER faculty_username',
  'SELECT 1');
PREPARE faculty_stmt FROM @sql;
EXECUTE faculty_stmt;
DEALLOCATE PREPARE faculty_stmt;

SET @has_faculty_phone = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'faculty_phone'
);
SET @sql = IF(@has_faculty_phone = 0,
  'ALTER TABLE subjects ADD COLUMN faculty_phone VARCHAR(50) NOT NULL DEFAULT '''' AFTER faculty_email',
  'SELECT 1');
PREPARE faculty_stmt FROM @sql;
EXECUTE faculty_stmt;
DEALLOCATE PREPARE faculty_stmt;

UPDATE subjects s
JOIN users u ON u.userid = s.fid
SET s.faculty_name = IF(s.faculty_name = '', u.name, s.faculty_name),
    s.faculty_username = IF(s.faculty_username = '', u.username, s.faculty_username),
    s.faculty_email = IF(s.faculty_email = '', u.email, s.faculty_email)
WHERE s.faculty_name = '' OR s.faculty_username = '' OR s.faculty_email = '';
