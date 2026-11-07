<?php
$db = mysqli_connect("localhost","root","","courseplan") or die("Could not connect");

/**
 * Ensure a course has a status row before a page reads or updates its progress.
 * Older database dumps may contain subjects without matching status rows.
 */
if (!function_exists('ensure_course_status')) {
function ensure_course_status($db, $sid)
{
    $sid = (int)$sid;
    $columns = array(
        'individual_time_table', 'class_time_table', 'course_plan', 'lesson_plan',
        'assignment', 'mid', 'remedial', 'tutorial', 'quiz', 'co_po', 'notes'
    );

    if ($sid <= 0) {
        return array_fill_keys($columns, '');
    }

    $insert = $db->prepare(
        "INSERT IGNORE INTO status (sid, individual_time_table, class_time_table, course_plan, lesson_plan, assignment, mid, remedial, tutorial, quiz, co_po, notes) " .
        "VALUES (?, '', '', '', '', '', '', '', '', '', '', '')"
    );
    if ($insert) {
        $insert->bind_param('i', $sid);
        $insert->execute();
        $insert->close();
    }

    $query = $db->prepare("SELECT * FROM status WHERE sid = ?");
    if ($query) {
        $query->bind_param('i', $sid);
        $query->execute();
        $result = $query->get_result();
        $status = $result ? $result->fetch_assoc() : null;
        $query->close();
        if ($status) {
            return $status;
        }
    }

    return array_fill_keys($columns, '');
}
}

/** Backfill status rows for the admin report, which displays every course. */
if (!function_exists('ensure_all_course_statuses')) {
function ensure_all_course_statuses($db)
{
    $db->query(
        "INSERT IGNORE INTO status (sid, individual_time_table, class_time_table, course_plan, lesson_plan, assignment, mid, remedial, tutorial, quiz, co_po, notes) " .
        "SELECT sid, '', '', '', '', '', '', '', '', '', '', '' FROM subjects"
    );
}
}
?>
