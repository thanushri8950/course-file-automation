<?php
session_start();
require("../db.php");

if (!isset($_SESSION['userid'])) {
    header("Location:../login/index_login.php");
    exit;
}
$userId = (int)$_SESSION['userid'];
$userType = isset($_SESSION['usertype']) ? $_SESSION['usertype'] : '';

if ($userType === 'admin' && isset($_GET['sid'])) {
    $_SESSION['sid'] = (int)$_GET['sid'];
}
if (empty($_SESSION['sid'])) {
    header($userType === 'admin' ? "Location:admin.php" : "Location:../dashboard/index.php");
    exit;
}

$sid = (int)$_SESSION['sid'];

if ($userType === 'admin') {
    $accessCheck = $db->prepare("SELECT sid FROM subjects WHERE sid = ?");
    $accessCheck->bind_param('i', $sid);
} else {
    $accessCheck = $db->prepare("SELECT sid FROM subjects WHERE sid = ? AND fid = ?");
    $accessCheck->bind_param('ii', $sid, $userId);
}
$accessCheck->execute();
$courseAccess = $accessCheck->get_result()->fetch_assoc();
$accessCheck->close();
if (!$courseAccess) {
    header("Location:../dashboard/index.php");
    exit;
}

$categories = array(
    'co_po_pso' => 'CO-PO / PSO Mapping Matrix',
    'ia1_marks' => 'IA-1 Marks (per-question mapped marks)',
    'ia2_marks' => 'IA-2 Marks (per-question mapped marks)',
    'assignments' => 'Assignments',
    'other_assessment' => 'Other Assessment Sheets',
    'semester_end_exam' => 'Semester End Exam',
    'course_end_survey' => 'Course End Survey'
);
$allowedExtensions = array('pdf', 'xls', 'xlsx', 'xlsm');
$maxFileSize = 20 * 1024 * 1024;
$message = '';
$messageType = 'success';

try {
    $db->query(
        "CREATE TABLE IF NOT EXISTS assessment_files (" .
        "id INT NOT NULL AUTO_INCREMENT, " .
        "sid INT NOT NULL, " .
        "category VARCHAR(50) NOT NULL, " .
        "original_name VARCHAR(255) NOT NULL, " .
        "stored_name VARCHAR(80) NOT NULL, " .
        "uploaded_by INT NOT NULL, " .
        "uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, " .
        "PRIMARY KEY (id), KEY course_category (sid, category)" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (mysqli_sql_exception $exception) {
    http_response_code(500);
    exit('Could not prepare the course assessment storage. Check that the database user can create tables.');
}

if (empty($_SESSION['assessment_csrf'])) {
    $_SESSION['assessment_csrf'] = bin2hex(random_bytes(32));
}

if (isset($_GET['download'])) {
    $fileId = (int)$_GET['download'];
    $statement = $db->prepare("SELECT original_name, stored_name FROM assessment_files WHERE id = ? AND sid = ?");
    $statement->bind_param('ii', $fileId, $sid);
    $statement->execute();
    $fileRecord = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$fileRecord || !preg_match('/^[a-f0-9]{32}\\.(pdf|xls|xlsx|xlsm)$/i', $fileRecord['stored_name'])) {
        http_response_code(404);
        exit('Assessment file not found.');
    }

    $storageRoot = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads');
    $storedPath = $storageRoot ? realpath($storageRoot . DIRECTORY_SEPARATOR . $sid . DIRECTORY_SEPARATOR . $fileRecord['stored_name']) : false;
    if (!$storedPath || strpos($storedPath, $storageRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($storedPath)) {
        http_response_code(404);
        exit('Assessment file not found.');
    }

    $extension = strtolower(pathinfo($storedPath, PATHINFO_EXTENSION));
    $contentTypes = array(
        'pdf' => 'application/pdf',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12'
    );
    $originalName = basename(str_replace('\\', '/', $fileRecord['original_name']));
    $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
    header('Content-Type: ' . $contentTypes[$extension]);
    header('Content-Length: ' . filesize($storedPath));
    header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($originalName));
    header('X-Content-Type-Options: nosniff');
    readfile($storedPath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $upload = isset($_FILES['assessment_file']) ? $_FILES['assessment_file'] : null;

    if (!hash_equals($_SESSION['assessment_csrf'], $postedToken)) {
        $message = 'Your form expired. Refresh the page and try again.';
        $messageType = 'danger';
    } elseif (!isset($categories[$category])) {
        $message = 'Choose a valid assessment category.';
        $messageType = 'danger';
    } elseif (!$upload || !isset($upload['error']) || $upload['error'] !== UPLOAD_ERR_OK) {
        $message = 'Choose a PDF or Excel file to upload.';
        $messageType = 'danger';
    } elseif ($upload['size'] <= 0 || $upload['size'] > $maxFileSize) {
        $message = 'The file must be smaller than 20 MB.';
        $messageType = 'danger';
    } else {
        $originalName = basename(str_replace('\\', '/', $upload['name']));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $signature = is_uploaded_file($upload['tmp_name']) ? file_get_contents($upload['tmp_name'], false, null, 0, 8) : false;
        $validSignature = false;

        if ($extension === 'pdf') {
            $validSignature = is_string($signature) && strpos($signature, '%PDF-') === 0;
        } elseif ($extension === 'xls') {
            $validSignature = is_string($signature) && substr($signature, 0, 8) === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
        } elseif ($extension === 'xlsx' || $extension === 'xlsm') {
            $validSignature = is_string($signature) && substr($signature, 0, 2) === 'PK';
        }

        if (!in_array($extension, $allowedExtensions, true) || !$validSignature) {
            $message = 'Only valid PDF, XLS, XLSX, and XLSM files are accepted.';
            $messageType = 'danger';
        } else {
            $storageRoot = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
            $courseDirectory = $storageRoot . DIRECTORY_SEPARATOR . $sid;
            if (!is_dir($courseDirectory) && !mkdir($courseDirectory, 0750, true) && !is_dir($courseDirectory)) {
                $message = 'The upload folder could not be created. Check folder permissions.';
                $messageType = 'danger';
            } else {
                $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
                $storedPath = $courseDirectory . DIRECTORY_SEPARATOR . $storedName;
                if (!move_uploaded_file($upload['tmp_name'], $storedPath)) {
                    $message = 'The file could not be saved. Check the upload folder permissions.';
                    $messageType = 'danger';
                } else {
                    try {
                        $statement = $db->prepare("INSERT INTO assessment_files (sid, category, original_name, stored_name, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                        $statement->bind_param('isssi', $sid, $category, $originalName, $storedName, $userId);
                        $statement->execute();
                        $statement->close();
                        $_SESSION['assessment_csrf'] = bin2hex(random_bytes(32));
                        header("Location:index.php?uploaded=1");
                        exit;
                    } catch (mysqli_sql_exception $exception) {
                        if (is_file($storedPath)) unlink($storedPath);
                        $message = 'The upload could not be recorded. Please try again.';
                        $messageType = 'danger';
                    }
                }
            }
        }
    }
}

if (isset($_GET['uploaded'])) {
    $message = 'Your assessment sheet was uploaded.';
}

$filesByCategory = array_fill_keys(array_keys($categories), array());
$statement = $db->prepare("SELECT id, category, original_name, uploaded_at FROM assessment_files WHERE sid = ? ORDER BY uploaded_at DESC, id DESC");
$statement->bind_param('i', $sid);
$statement->execute();
$results = $statement->get_result();
while ($fileRecord = $results->fetch_assoc()) {
    if (isset($filesByCategory[$fileRecord['category']])) {
        $filesByCategory[$fileRecord['category']][] = $fileRecord;
    }
}
$statement->close();

function assessment_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($userType === 'admin') {
    include("../header/admin_header.php");
} else {
    include("../header/header.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Course Assessment Sheet</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        .assessment-page { max-width: 1100px; margin: 28px auto; padding: 0 18px 40px; }
        .assessment-intro { margin-bottom: 22px; }
        .assessment-card { background: #fff; border: 1px solid #d9e0e8; border-radius: 8px; box-shadow: 0 4px 12px rgba(20, 40, 70, .08); margin-bottom: 18px; padding: 20px; }
        .assessment-card h3 { color: #283b68; font-size: 20px; margin: 0 0 14px; }
        .assessment-upload { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .assessment-upload input[type=file] { min-width: 220px; flex: 1; }
        .assessment-files { margin: 14px 0 0; padding-left: 20px; }
        .assessment-files li { margin: 6px 0; overflow-wrap: anywhere; }
        .assessment-hint { color: #536273; }
    </style>
</head>
<body>
<main class="assessment-page">
    <div class="assessment-intro">
        <h2>Course Assessment Sheet</h2>
        <p class="assessment-hint">Upload PDF or Excel sheets for this course. Files are grouped by assessment type.</p>
        <p class="assessment-hint">Accepted formats: PDF, XLS, XLSX, XLSM. Maximum size: 20 MB per file.</p>
    </div>

    <?php if ($message !== '') { ?>
        <div class="alert alert-<?php echo assessment_h($messageType); ?>" role="alert"><?php echo assessment_h($message); ?></div>
    <?php } ?>

    <?php foreach ($categories as $categoryKey => $categoryLabel) { ?>
        <section class="assessment-card">
            <h3><?php echo assessment_h($categoryLabel); ?></h3>
            <form class="assessment-upload" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo assessment_h($_SESSION['assessment_csrf']); ?>">
                <input type="hidden" name="category" value="<?php echo assessment_h($categoryKey); ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $maxFileSize; ?>">
                <input type="file" name="assessment_file" accept=".pdf,.xls,.xlsx,.xlsm" required>
                <button class="btn btn-primary" type="submit">Upload sheet</button>
            </form>

            <?php if (count($filesByCategory[$categoryKey]) > 0) { ?>
                <ul class="assessment-files">
                    <?php foreach ($filesByCategory[$categoryKey] as $fileRecord) { ?>
                        <li>
                            <a href="index.php?download=<?php echo (int)$fileRecord['id']; ?>"><?php echo assessment_h($fileRecord['original_name']); ?></a>
                            <small class="assessment-hint">Uploaded <?php echo assessment_h($fileRecord['uploaded_at']); ?></small>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p class="assessment-hint" style="margin: 14px 0 0;">No files uploaded for this assessment yet.</p>
            <?php } ?>
        </section>
    <?php } ?>
</main>
</body>
</html>
