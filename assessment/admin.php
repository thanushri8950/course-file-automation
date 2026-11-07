<?php
session_start();
require("../db.php");

if (!isset($_SESSION['userid']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'admin') {
    header("Location:../login/index_login.php");
    exit;
}

$query = "SELECT s.sid, s.academic_year, s.subject_name, s.year, s.semester, s.department, s.section, COALESCE(NULLIF(s.faculty_name, ''), u.name) AS faculty_name " .
         "FROM subjects s LEFT JOIN users u ON u.userid = s.fid " .
         "ORDER BY s.academic_year DESC, s.year, s.semester, s.section, s.subject_name";
$courses = mysqli_query($db, $query);

function assessment_admin_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

include("../header/admin_header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Course Assessment Sheets</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        .assessment-admin { margin: 30px 30px 40px 230px; }
        .assessment-admin h2 { color: #283b68; margin-bottom: 10px; }
        .assessment-admin .table-wrap { overflow-x: auto; }
        .assessment-admin table { background: #fff; }
        .assessment-admin th { background: #eef2f7; }
        @media (max-width: 767px) { .assessment-admin { margin: 24px 14px; } }
    </style>
</head>
<body>
<main class="assessment-admin">
    <h2>Course Assessment Sheets</h2>
    <p>Select a course to view or upload its assessment files.</p>
    <div class="table-wrap">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Academic Year</th>
                    <th>Course</th>
                    <th>Year / Semester</th>
                    <th>Department</th>
                    <th>Section / Branch</th>
                    <th>Faculty</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($courses && mysqli_num_rows($courses) > 0) { ?>
                    <?php while ($course = mysqli_fetch_assoc($courses)) { ?>
                        <tr>
                            <td><?php echo assessment_admin_h($course['academic_year']); ?></td>
                            <td><?php echo assessment_admin_h($course['subject_name']); ?></td>
                            <td><?php echo assessment_admin_h($course['year']); ?> / <?php echo assessment_admin_h($course['semester']); ?></td>
                            <td><?php echo assessment_admin_h($course['department']); ?></td>
                            <td><?php echo assessment_admin_h($course['section']); ?></td>
                            <td><?php echo assessment_admin_h($course['faculty_name']); ?></td>
                            <td><a class="btn btn-primary btn-sm" href="index.php?sid=<?php echo (int)$course['sid']; ?>">Open assessment sheet</a></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="7" class="text-center">No courses have been assigned yet.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
