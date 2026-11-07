<?php
session_start();
require("../db.php");

if (!isset($_SESSION['userid']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'admin') {
    header("Location:../login/index_login.php");
    exit;
}

$message = '';
$messageType = 'success';
$yearSemesters = array(1 => array(1, 2), 2 => array(3, 4), 3 => array(5, 6), 4 => array(7, 8));
$departments = array('CSE', 'ICBS', 'MECH');
$sectionsByDepartment = array(
    'CSE' => array('CSE-A', 'CSE-B', 'CSE-C', 'CSE-D'),
    'ICBS' => array('AIML', 'CSBS', 'CSDS'),
    'MECH' => array('M1', 'M2')
);

if (isset($_POST['Submit'])) {
    $academicYear = isset($_POST['acyear_radio']) && $_POST['acyear_radio'] === 'new'
        ? trim(isset($_POST['acyear_new']) ? $_POST['acyear_new'] : '')
        : trim(isset($_POST['acyear']) ? $_POST['acyear'] : '');
    $subject = isset($_POST['subject_radio']) && $_POST['subject_radio'] === 'new'
        ? trim(isset($_POST['subject_new']) ? $_POST['subject_new'] : '')
        : trim(isset($_POST['subject']) ? $_POST['subject'] : '');
    $lecturerName = trim(isset($_POST['lecturer_name']) ? $_POST['lecturer_name'] : '');
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    $semester = isset($_POST['semester']) ? (int)$_POST['semester'] : 0;
    $sectionBranch = isset($_POST['section_branch']) ? $_POST['section_branch'] : '';

    if ($academicYear === '' || strlen($academicYear) > 12 || $subject === '' || strlen($subject) > 50) {
        $message = 'Enter an academic year and course name.';
        $messageType = 'danger';
    } elseif (!in_array($department, $departments, true)) {
        $message = 'Choose a valid department.';
        $messageType = 'danger';
    } elseif ($department !== '' && !isset($sectionsByDepartment[$department])) {
        $message = 'Choose a valid department.';
        $messageType = 'danger';
    } elseif ($department !== '' && $sectionBranch !== '' && !in_array($sectionBranch, $sectionsByDepartment[$department], true)) {
        $message = 'Choose a valid section or branch for the selected department.';
        $messageType = 'danger';
    }

    if ($message === '' && (!isset($yearSemesters[$year]) || !in_array($semester, $yearSemesters[$year], true))) {
        $message = 'Choose a semester that belongs to the selected year.';
        $messageType = 'danger';
    } elseif ($message === '' && ($lecturerName === '' || strlen($lecturerName) > 255)) {
        $message = 'Enter the lecturer name (up to 255 characters).';
        $messageType = 'danger';
    }

    if ($message === '') {
        $lookup = $db->prepare("SELECT userid, username, name, email FROM users WHERE usertype = 'faculty' AND name = ?");
        $lookup->bind_param('s', $lecturerName);
        $lookup->execute();
        $facultyResult = $lookup->get_result();
        $facultyCount = $facultyResult->num_rows;
        $faculty = $facultyCount === 1 ? $facultyResult->fetch_assoc() : null;
        $lookup->close();

        if ($facultyCount === 0) {
            $message = 'No faculty account has that exact name. Add the faculty member in Admin → Add User first, then enter the same name here.';
            $messageType = 'danger';
        } elseif ($facultyCount > 1) {
            $message = 'More than one faculty account has that name. Give the accounts unique names in Admin → Add User first.';
            $messageType = 'danger';
        } else {
            $facultyId = (int)$faculty['userid'];
            $facultyUsername = $faculty['username'];
            $facultyEmail = $faculty['email'];
            $facultyPhone = '';
            $emptyBranch = '';
            $progress = 0;
            $insert = $db->prepare(
                "INSERT INTO subjects (fid, faculty_name, faculty_username, faculty_email, faculty_phone, department, branch, academic_year, subject_name, year, semester, section, progress) " .
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $insert->bind_param(
                'issssssssiisi',
                $facultyId,
                $lecturerName,
                $facultyUsername,
                $facultyEmail,
                $facultyPhone,
                $department,
                $emptyBranch,
                $academicYear,
                $subject,
                $year,
                $semester,
                $sectionBranch,
                $progress
            );
            $insert->execute();
            ensure_course_status($db, mysqli_insert_id($db));
            $insert->close();
            $message = 'Course assigned successfully.';
        }
    }
}

$ay = mysqli_query($db, "SELECT DISTINCT academic_year FROM subjects ORDER BY academic_year DESC");
$sb = mysqli_query($db, "SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name");

include("../header/admin_header.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assign Lecturer</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        .assignment-form { max-width: 620px; margin: 30px auto 45px; padding: 24px; background: #fff; border-radius: 8px; box-shadow: 0 4px 14px rgba(0,0,0,.14); }
        .assignment-form h1 { text-align: center; color: #283b68; margin: 0 0 22px; }
        .assignment-form label { display: block; margin: 15px 0 6px; font-weight: 600; }
        .assignment-form input[type=text], .assignment-form select { width: 100%; padding: 10px; border: 1px solid #ccd3dc; border-radius: 4px; }
        .assignment-form .choice { display: flex; gap: 18px; align-items: center; margin: 4px 0 8px; }
        .assignment-form .choice label { display: inline; margin: 0; font-weight: normal; }
        .assignment-form .submit { width: 100%; margin-top: 22px; padding: 12px; border: 0; border-radius: 4px; background: #ff4000; color: #fff; font-weight: 600; }
        .assignment-form .help { color: #596778; font-size: 13px; margin-top: 5px; }
    </style>
</head>
<body>
<main class="assignment-form">
    <h1>Assign Lecturer</h1>
    <?php if ($message !== '') { ?><div class="alert alert-<?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
    <form method="post">
        <label>Academic year</label>
        <div class="choice">
            <label><input type="radio" value="existing" name="acyear_radio" checked> Existing</label>
            <label><input type="radio" value="new" name="acyear_radio"> New</label>
        </div>
        <select name="acyear" id="acyear_select">
            <?php while ($row = mysqli_fetch_assoc($ay)) { ?>
                <option value="<?php echo htmlspecialchars($row['academic_year'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['academic_year'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php } ?>
        </select>
        <input id="acyear_text" type="text" name="acyear_new" placeholder="New academic year" style="display:none;">

        <label for="department">Department</label>
        <select name="department" id="department" required>
            <option value="">Choose department</option>
            <option value="CSE">CSE</option>
            <option value="ICBS">ICBS</option>
            <option value="MECH">Mechanical</option>
        </select>


        <label for="section_branch">Section / Branch (optional)</label>
        <select name="section_branch" id="section_branch" disabled>
            <option value="">Choose department first</option>
        </select>

        <label for="year">Year</label>
        <select name="year" id="year" required>
            <option value="1">1st year</option>
            <option value="2">2nd year</option>
            <option value="3">3rd year</option>
            <option value="4">4th year</option>
        </select>

        <label for="semester">Semester</label>
        <select name="semester" id="semester" required></select>

        <label for="lecturer_name">Lecturer</label>
        <input type="text" id="lecturer_name" name="lecturer_name" maxlength="255" placeholder="Enter the faculty account name" required>
        <p class="help">First add the faculty account in Admin → Add User. Enter the same name here so the lecturer can log in and see this course.</p>

        <label>Course</label>
        <div class="choice">
            <label><input type="radio" value="existing" name="subject_radio" checked> Existing</label>
            <label><input type="radio" value="new" name="subject_radio"> New</label>
        </div>
        <select id="subject_select" name="subject">
            <?php while ($row = mysqli_fetch_assoc($sb)) { ?>
                <option value="<?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></option>
            <?php } ?>
        </select>
        <input id="subject_text" type="text" name="subject_new" placeholder="New course name" style="display:none;">

        <input class="submit" type="submit" name="Submit" value="Assign course">
    </form>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var department = document.getElementById('department');
    var sectionBranch = document.getElementById('section_branch');
    var year = document.getElementById('year');
    var semester = document.getElementById('semester');
    var semestersByYear = { '1': [1, 2], '2': [3, 4], '3': [5, 6], '4': [7, 8] };
    var ordinal = ['', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th'];

    var sectionBranchOptions = {
        'CSE': ['CSE-A', 'CSE-B', 'CSE-C', 'CSE-D'],
        'ICBS': ['AIML', 'CSBS', 'CSDS'],
        'MECH': ['M1', 'M2']
    };

    function updateSectionBranchOptions() {
        var options = sectionBranchOptions[department.value] || [];
        sectionBranch.innerHTML = '';
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = department.value ? 'No section / branch' : 'Choose department first';
        sectionBranch.appendChild(placeholder);
        options.forEach(function (value) {
            var option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            sectionBranch.appendChild(option);
        });
        sectionBranch.disabled = !department.value;
        sectionBranch.required = false;
    }

    function updateSemesterOptions() {
        var validSemesters = semestersByYear[year.value] || [];
        var oldValue = semester.value;
        semester.innerHTML = '';
        validSemesters.forEach(function (number) {
            var option = document.createElement('option');
            option.value = number;
            option.textContent = ordinal[number] + ' semester';
            semester.appendChild(option);
        });
        if (validSemesters.indexOf(Number(oldValue)) !== -1) semester.value = oldValue;
    }

    function updateRadioFields(radioName, selectId, textId) {
        var selected = document.querySelector('input[name="' + radioName + '"]:checked').value;
        var useNew = selected === 'new';
        document.getElementById(selectId).style.display = useNew ? 'none' : 'block';
        document.getElementById(textId).style.display = useNew ? 'block' : 'none';
        document.getElementById(selectId).required = !useNew;
        document.getElementById(textId).required = useNew;
    }

    department.addEventListener('change', updateSectionBranchOptions);
    year.addEventListener('change', updateSemesterOptions);
    document.querySelectorAll('input[name="acyear_radio"]').forEach(function (input) {
        input.addEventListener('change', function () { updateRadioFields('acyear_radio', 'acyear_select', 'acyear_text'); });
    });
    document.querySelectorAll('input[name="subject_radio"]').forEach(function (input) {
        input.addEventListener('change', function () { updateRadioFields('subject_radio', 'subject_select', 'subject_text'); });
    });

    updateSectionBranchOptions();
    updateSemesterOptions();
    updateRadioFields('acyear_radio', 'acyear_select', 'acyear_text');
    updateRadioFields('subject_radio', 'subject_select', 'subject_text');
});
</script>
</body>
</html>
