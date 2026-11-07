<?php
session_start();
require "../db.php";
if (!isset($_SESSION['userid']) || !isset($_SESSION['usertype']) || $_SESSION['usertype'] !== "admin") {
	header("Location:../login/index_login.php");
	exit;
}
ensure_all_course_statuses($db);
include ("../header/admin_header.php");

// $ye=$_SESSION['2018']
if (isset($_GET['acyear']))
{
	$acyear = $_GET['acyear'];
	$qry = "SELECT u.name AS account_name, s.*, COALESCE(NULLIF(s.faculty_name, ''), u.name) AS faculty_display_name, ss.* from users u inner join subjects s on s.fid=u.userid inner join status ss on s.sid=ss.sid where s.academic_year='$acyear' order by s.section ";
	$res = mysqli_query($db, $qry);
}

$qry = "SELECT distinct academic_year from subjects order by academic_year desc";
$ay = mysqli_query($db, $qry);

?>


<!DOCTYPE HTML>
<html>
<head>
	<style type="text/css">
	#sidebar
    {
        padding-left: 40px;
    }
    #sidebar.active {
      margin-left: -310px;
    }
    ul
    {
        list-style: upper-norwegian;
    }
	table {
		border:1px solid black;
		border-collapse: collapse;
		width:100%;
		box-shadow: 0 15px 25px rgba(0,0,0,0.5);
	}
	th, td{
		border-right: 1px solid black;
		border-bottom: 1px solid black;
	}
	th{
		padding:2px;
		text-align: center;
	}
	td{
		padding:15px;
		text-align: center;
	}
	</style>
</head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	
<body>
	<h1 align="center">Admin Review</h1>
	<div align="center"><select id="acyear" style="margin-top: 10px; width:100px; height:30px;" onchange="update(this)">
    <?php while($row = mysqli_fetch_assoc($ay)) { ?>
    	<option value="<?php echo $row['academic_year'] ?>"><?php echo $row['academic_year'] ?></option>
    <?php } ?>
	</select>
	<script type="text/javascript">
		var get = () => {
			var acyear = $('#acyear').val()
			window.location.href='verify.php?acyear='+acyear;
		};
	</script>
	<button style="width:100px; height:30px;" onclick="get()">Show</button>
	</div>
<br>
<h2 align="center"><?php if (isset($_GET['acyear'])) echo $_GET['acyear'] ?></h2>
<div class="container">
     <table class="table table-striped" id="myTable">
				
				
				<tr>
					<th><font size="4px" align="center">Year</font></th>
					<th><font size="4px"align="center">Subject</font></th>
					<th><font size="4px"align="center">Department</font></th>
					<th><font size="4px"align="center">Section / Branch</font></th>
					<th><font size="4px"align="center">Faculty name</font></th>
					<th><font size="4px"align="center">Individualtimetable</font></th>
					<th><font size="4px"align="center">Classtimetable</font></th>
					<th><font size="4px"align="center">Course Plan</font></th>
					<th><font size="4px"align="center">Lesson Plan</font></th>
					<th><font size="4px"align="center">Assignment</font></th>
					<th><font size="4px"align="center">Mid</font></th>
					<th><font size="4px"align="center">Tutorial</font></th>
					<th><font size="4px"align="center">Quiz</font></th>
					<th><font size="4px"align="center">Remedial</font></th>
					<th><font size="4px"align="center">CO-PO </font></th>
					<th><font size="4px"align="center">Notes</font></th>
				</tr>
				<?php if(isset($res)) while ($row = mysqli_fetch_assoc($res)) { ?>
				<tr>
					<td><?php echo $row['year'] ?></td>
					<td><?php echo $row['subject_name'] ?></td>
					<td><?php echo htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?></td>
					<td><?php echo $row['section'] ?></td>
					<td><?php echo htmlspecialchars($row['faculty_display_name'], ENT_QUOTES, 'UTF-8') ?></td>
					<td><?php if ($row['individual_time_table']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['class_time_table']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['course_plan']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['lesson_plan']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['assignment']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['mid']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['tutorial']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['quiz']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['remedial']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['co_po']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
					<td><?php if ($row['notes']=="Completed") echo "<i class='fa fa-check fa-lg' aria-hidden='true'></i>" ?></td>
				</tr>
				<?php } ?>
</table>
</div>
</body>
