-- Normalize existing course labels for the unified Section / Branch field.
-- Run this once after backing up the courseplan database.
UPDATE subjects
SET section = CONCAT('CSE-', UPPER(section))
WHERE UPPER(department) = 'CSE'
  AND UPPER(section) IN ('A', 'B', 'C', 'D');

UPDATE subjects
SET department = 'ICBS',
    section = CASE WHEN branch <> '' THEN branch ELSE section END,
    branch = ''
WHERE UPPER(department) IN ('CSBS', 'ICBS');

UPDATE subjects
SET section = UPPER(section)
WHERE UPPER(department) = 'MECH'
  AND UPPER(section) IN ('M1', 'M2');
