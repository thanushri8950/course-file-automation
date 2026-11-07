<?php
session_start();
require("../db.php");

if (!isset($_SESSION['userid']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'admin') {
    header("Location:../login/index_login.php");
    exit;
}

if (empty($_SESSION['subject_faculty_csrf'])) {
    $_SESSION['subject_faculty_csrf'] = bin2hex(random_bytes(32));
}

$sid = isset($_POST['sid']) ? (int)$_POST['sid'] : (isset($_GET['sid']) ? (int)$_GET['sid'] : 0);
$academicYear = isset($_POST['acyear']) ? trim($_POST['acyear']) : (isset($_GET['acyear']) ? trim($_GET['acyear']) : '');
$error = '';

if ($sid <= 0) {
    header('Location:editable.php');
    exit;
}

$lookup = $db->prepare(
    "SELECT s.sid, s.academic_year, s.subject_name, s.year, s.semester, s.section, s.fid, " .
    "s.faculty_name, s.faculty_username, s.faculty_email, s.faculty_phone, " .
    "u.username AS account_username " .
    "FROM subjects s LEFT JOIN users u ON u.userid = s.fid WHERE s.sid = ?"
);
$lookup->bind_param('i', $sid);
$lookup->execute();
$course = $lookup->get_result()->fetch_assoc();
$lookup->close();

if (!$course) {
    header('Location:editable.php' . ($academicYear !== '' ? '?' . http_build_query(array('acyear' => $academicYear, 'get' => 'Show')) : ''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facultyName = trim(isset($_POST['faculty_name']) ? $_POST['faculty_name'] : '');
    $facultyUsername = trim(isset($_POST['faculty_username']) ? $_POST['faculty_username'] : '');
    $facultyEmail = trim(isset($_POST['faculty_email']) ? $_POST['faculty_email'] : '');
    $facultyPhone = trim(isset($_POST['faculty_phone']) ? $_POST['faculty_phone'] : '');
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!hash_equals($_SESSION['subject_faculty_csrf'], $token)) {
        $error = 'This form expired. Refresh and try again.';
    } elseif ($facultyName === '' || $facultyUsername === '' || strlen($facultyName) > 255 || strlen($facultyUsername) > 255 || strlen($facultyEmail) > 255 || strlen($facultyPhone) > 50) {
        $error = 'Enter a name and username. Name, username, and email must be up to 255 characters; phone must be up to 50 characters.';
    } elseif ($facultyEmail !== '' && !filter_var($facultyEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address or leave the email blank.';
    } else {
        $facultyId = (int)$course['fid'];
        if ($facultyUsername !== '') {
            $accountCheck = $db->prepare("SELECT userid FROM users WHERE username = ? AND usertype = 'faculty'");
            $accountCheck->bind_param('s', $facultyUsername);
            $accountCheck->execute();
            $account = $accountCheck->get_result()->fetch_assoc();
            $accountCheck->close();
            if ($account) {
                $facultyId = (int)$account['userid'];
            }
        }

        $update = $db->prepare("UPDATE subjects SET fid = ?, faculty_name = ?, faculty_username = ?, faculty_email = ?, faculty_phone = ? WHERE sid = ?");
        $update->bind_param('issssi', $facultyId, $facultyName, $facultyUsername, $facultyEmail, $facultyPhone, $sid);
        $saved = $update->execute();
        $update->close();

        if ($saved) {
            $_SESSION['subject_faculty_csrf'] = bin2hex(random_bytes(32));
            $returnParams = array('acyear' => $academicYear !== '' ? $academicYear : $course['academic_year'], 'get' => 'Show', 'updated' => 1);
            header('Location:editable.php?' . http_build_query($returnParams));
            exit;
        }
        $error = 'Faculty details could not be saved.';
    }
}

$formName = isset($_POST['faculty_name']) ? $_POST['faculty_name'] : ($course['faculty_name'] !== '' ? $course['faculty_name'] : '');
$formUsername = isset($_POST['faculty_username']) ? $_POST['faculty_username'] : ($course['faculty_username'] !== '' ? $course['faculty_username'] : $course['account_username']);
$formEmail = isset($_POST['faculty_email']) ? $_POST['faculty_email'] : $course['faculty_email'];
$formPhone = isset($_POST['faculty_phone']) ? $_POST['faculty_phone'] : $course['faculty_phone'];

include("../header/admin_header.php");

function edit_faculty_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Faculty Details</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        .faculty-edit { max-width: 680px; margin: 36px auto; padding: 24px; background: #fff; border: 1px solid #d9e0e8; border-radius: 8px; box-shadow: 0 4px 12px rgba(20, 40, 70, .08); }
        .faculty-edit h2 { color: #283b68; margin-top: 0; }
        .faculty-edit label { display: block; margin-top: 16px; }
        .faculty-edit input { width: 100%; padding: 10px; }
        .faculty-edit .help { color: #536273; }
        @media (max-width: 767px) { .faculty-edit { margin: 24px 14px; } }
    </style>
</head>
<body>
<main class="faculty-edit">
    <h2>Edit Faculty Details</h2>
    <p><strong>Subject:</strong> <?php echo edit_faculty_h($course['subject_name']); ?> (<?php echo edit_faculty_h($course['academic_year']); ?>)</p>
    <p class="help">These details belong to this subject only. To link this course to another faculty login, create the account in Admin → Add User, then enter that account’s username below.</p>
    <?php if ($error !== '') { ?><div class="alert alert-danger" role="alert"><?php echo edit_faculty_h($error); ?></div><?php } ?>
    <form method="post">
        <input type="hidden" name="sid" value="<?php echo (int)$course['sid']; ?>">
        <input type="hidden" name="acyear" value="<?php echo edit_faculty_h($academicYear !== '' ? $academicYear : $course['academic_year']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo edit_faculty_h($_SESSION['subject_faculty_csrf']); ?>">

        <label for="faculty-name">Lecturer name</label>
        <input id="faculty-name" type="text" name="faculty_name" maxlength="255" value="<?php echo edit_faculty_h($formName); ?>" required>

        <label for="faculty-username">Lecturer username</label>
        <input id="faculty-username" type="text" name="faculty_username" maxlength="255" value="<?php echo edit_faculty_h($formUsername); ?>" required>

        <label for="faculty-email">Email</label>
        <input id="faculty-email" type="email" name="faculty_email" maxlength="255" value="<?php echo edit_faculty_h($formEmail); ?>">

        <label for="faculty-phone">Phone number</label>
        <input id="faculty-phone" type="tel" name="faculty_phone" maxlength="50" value="<?php echo edit_faculty_h($formPhone); ?>">

        <p style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save details</button>
            <a class="btn btn-default" href="editable.php<?php echo $academicYear !== '' ? '?' . http_build_query(array('acyear' => $academicYear, 'get' => 'Show')) : ''; ?>">Cancel</a>
        </p>
    </form>
</main>
</body>
</html>
