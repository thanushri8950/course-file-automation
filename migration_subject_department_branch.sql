-- Add separate department and branch fields and make section optional.
-- Safe to rerun: columns are added only when missing.
USE courseplan;

SET @has_department = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'department'
);
SET @sql = IF(@has_department = 0,
  'ALTER TABLE subjects ADD COLUMN department VARCHAR(100) NOT NULL DEFAULT '''' AFTER faculty_phone',
  'SELECT 1');
PREPARE subject_structure_stmt FROM @sql;
EXECUTE subject_structure_stmt;
DEALLOCATE PREPARE subject_structure_stmt;

SET @has_branch = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subjects' AND COLUMN_NAME = 'branch'
);
SET @sql = IF(@has_branch = 0,
  'ALTER TABLE subjects ADD COLUMN branch VARCHAR(100) NOT NULL DEFAULT '''' AFTER department',
  'SELECT 1');
PREPARE subject_structure_stmt FROM @sql;
EXECUTE subject_structure_stmt;
DEALLOCATE PREPARE subject_structure_stmt;

ALTER TABLE subjects MODIFY section VARCHAR(20) NOT NULL DEFAULT '';

-- Existing values such as CSE-C stored department and section together.
UPDATE subjects
SET department = SUBSTRING_INDEX(section, '-', 1),
    branch = CASE
      WHEN SUBSTRING_INDEX(section, '-', 1) = 'CSBS' AND section LIKE '%-%-%'
      THEN SUBSTRING_INDEX(SUBSTRING_INDEX(section, '-', 2), '-', -1)
      ELSE ''
    END,
    section = SUBSTRING_INDEX(section, '-', -1)
WHERE department = '' AND section LIKE '%-%';

-- Older rows stored semester 1 or 2 within each year. Convert them to the
-- institution-wide semester numbers: years 2, 3, and 4 use semesters 3-8.
UPDATE subjects
SET semester = CASE year
  WHEN 2 THEN semester + 2
  WHEN 3 THEN semester + 4
  WHEN 4 THEN semester + 6
  ELSE semester
END
WHERE semester IN (1, 2) AND year IN (2, 3, 4);
