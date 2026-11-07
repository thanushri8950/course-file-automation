-- Destructive cleanup requested for the local courseplan database.
-- Removes course-linked records not belonging to a 2026-2027 course.
-- Faculty accounts and faculty-level papers (keyed by user_id, not course sid) are retained.

START TRANSACTION;

CREATE TEMPORARY TABLE cleanup_keep_course_ids (
  sid INT NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT INTO cleanup_keep_course_ids (sid)
SELECT sid
FROM subjects
WHERE academic_year = '2026-2027';

DELETE FROM review
WHERE from_sid NOT IN (SELECT sid FROM cleanup_keep_course_ids)
   OR to_sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);

DELETE FROM quiz WHERE sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM remedial WHERE sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM tutorial WHERE sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM content_beyond_syllabus WHERE sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM roleplay WHERE sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM rubrics WHERE CAST(sid AS UNSIGNED) NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM status WHERE sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM assessment_files WHERE sid NOT IN (SELECT sid FROM cleanup_keep_course_ids);
DELETE FROM subjects WHERE academic_year <> '2026-2027';

COMMIT;
DROP TEMPORARY TABLE cleanup_keep_course_ids;
