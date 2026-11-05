<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require "../db.php";

if (!isset($_SESSION['userid'])) {
	header("Location:../login/index_login.php");
	exit;
}
include ("../login/header.php");

if (isset($_GET['sid'])) {
	$_SESSION['sid'] = $_GET['sid'];
	$_SESSION['subject_name'] = $_GET['subject_name'];
	header("Location:coursefile.php");
	exit;
}

$fid = $_SESSION['userid'];
$qry = "SELECT DISTINCT academic_year FROM subjects WHERE fid = '$fid' AND academic_year = '2026-2027' ORDER BY academic_year DESC";
$res = mysqli_query($db, $qry);
$academicYears = [];
while ($yearRow = mysqli_fetch_assoc($res)) $academicYears[] = $yearRow['academic_year'];

$selectedYear = isset($_GET['acyear']) && in_array($_GET['acyear'], $academicYears, true)
	? $_GET['acyear']
	: (count($academicYears) > 0 ? $academicYears[0] : '');
$sem5 = false;
if ($selectedYear !== '') {
	$_SESSION['academic_year'] = $selectedYear;
	$acyear = $selectedYear;
	$sem5 = mysqli_query($db, "SELECT * from subjects where academic_year='$acyear' and fid='$fid' and semester=5 ORDER BY subject_name");
}

function renderSubjectCards($subjects, $db) {
	while ($row = mysqli_fetch_assoc($subjects)) {
		$sid = (int)$row['sid'];
		$statusResult = mysqli_query($db, "SELECT * FROM status where sid='$sid'");
		$status = mysqli_fetch_array($statusResult);
		$progress = 0;
		if ($status) for ($i = 1; $i < 12; $i++) if ($status[$i] === "Completed") $progress++;
		$progress = floor(($progress / 11) * 100);
		$subjectName = htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8');
		$section = htmlspecialchars($row['section'], ENT_QUOTES, 'UTF-8');
		$target = "../dashboard/index.php?sid=" . $sid . "&subject_name=" . rawurlencode($row['subject_name']);
		?>
		<a class="sjc-course-card" href="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>">
			<div class="sjc-course-card__top"><span class="sjc-course-card__eyebrow">COURSE FILE</span><span class="sjc-course-card__arrow" aria-hidden="true">↗</span></div>
			<h4><?php echo $subjectName; ?></h4>
			<p>Year <?php echo (int)$row['year']; ?>/4 <span aria-hidden="true">·</span> <?php echo $section; ?></p>
			<div class="sjc-progress"><div class="sjc-progress__track"><span style="width:<?php echo $progress; ?>%"></span></div><strong><?php echo $progress; ?>%</strong></div>
			<span class="sjc-course-card__foot">Open course file</span>
		</a>
		<?php
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Faculty Dashboard | SJEC Course File</title>
	<link rel="stylesheet" href="../css/bootstrap.min.css">
	<link rel="stylesheet" href="progress-circle.css">
	<link rel="stylesheet" href="../header/sjc-theme.css">
</head>
<body class="sjc-dashboard-page">
	<main class="sjc-dashboard">
		<section class="sjc-welcome">
			<div>
				<p class="sjc-kicker">ST JOSEPH ENGINEERING COLLEGE · MANGALORE</p>
				<h1>Faculty Dashboard</h1>
				<p class="sjc-welcome__text">Choose an academic year to view your assigned course files.</p>
			</div>
			<div class="sjc-welcome__seal"><img src="../sjec_logo.gif" alt="St Joseph Engineering College crest"></div>
		</section>

		<section class="sjc-year-panel" aria-labelledby="academic-years-title">
			<div class="sjc-section-heading">
				<div><p class="sjc-kicker">YOUR TEACHING RECORD</p><h2 id="academic-years-title">Academic year</h2></div>
				<span class="sjc-year-count"><?php echo count($academicYears); ?> available</span>
			</div>
			<?php if (count($academicYears) > 0) { ?>
				<div class="sjc-year-list">
				<?php foreach ($academicYears as $year) { $active = ($year === $selectedYear); ?>
					<a class="sjc-year-chip<?php echo $active ? ' is-active' : ''; ?>" href="../dashboard/index.php?acyear=<?php echo rawurlencode($year); ?>"><?php echo htmlspecialchars($year, ENT_QUOTES, 'UTF-8'); ?><span aria-hidden="true">→</span></a>
				<?php } ?>
				</div>
			<?php } else { ?>
				<p class="sjc-empty">No academic years have been assigned to your faculty account yet.</p>
			<?php } ?>
		</section>

		<?php if ($selectedYear !== '') { ?>
			<div class="sjc-selected-year"><span>Showing course files for</span><strong><?php echo htmlspecialchars($selectedYear, ENT_QUOTES, 'UTF-8'); ?></strong></div>
			<?php foreach ([5 => $sem5] as $semester => $subjects) { if ($subjects && mysqli_num_rows($subjects) > 0) { ?>
				<section class="sjc-semester" aria-labelledby="semester-<?php echo $semester; ?>">
					<div class="sjc-section-heading"><div><p class="sjc-kicker">ACADEMIC YEAR <?php echo htmlspecialchars($selectedYear, ENT_QUOTES, 'UTF-8'); ?></p><h2 id="semester-<?php echo $semester; ?>">Semester <?php echo $semester; ?></h2></div><span class="sjc-year-count"><?php echo mysqli_num_rows($subjects); ?> course<?php echo mysqli_num_rows($subjects) === 1 ? '' : 's'; ?></span></div>
					<div class="sjc-course-grid"><?php renderSubjectCards($subjects, $db); ?></div>
				</section>
			<?php } } ?>
		<?php } else if (count($academicYears) > 0) { ?>
			<div class="sjc-hint"><span class="sjc-hint__icon" aria-hidden="true">i</span><p>Select an academic year above to see its semesters and assigned courses.</p></div>
		<?php } ?>
		<footer class="sjc-footer">St Joseph Engineering College <span>·</span> Course File Automation</footer>
	</main>
</body>
</html>
