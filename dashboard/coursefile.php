<?php
session_start();

require "../db.php";

if(!isset($_SESSION['userid'])) { header("Location:../login/index_login.php"); exit; }

if (isset($_GET['sid']))
{
  $sid = (int)$_GET['sid'];
  $query = mysqli_prepare($db, "SELECT s.sid, s.subject_name FROM subjects s WHERE s.sid = ?");
  mysqli_stmt_bind_param($query, "i", $sid);
  mysqli_stmt_execute($query);
  $result = mysqli_stmt_get_result($query);
  $course = $result ? mysqli_fetch_assoc($result) : null;
  mysqli_stmt_close($query);

  if (!$course) {
    header("Location:../dashboard/index.php");
    exit;
  }
  $_SESSION['sid'] = $sid;
  $_SESSION['subject_name'] = $course['subject_name'];
  header("Location:coursefile.php");
  exit;
}
if (empty($_SESSION['sid'])) { header("Location:../dashboard/index.php"); exit; }
$sid = (int)$_SESSION['sid'];
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$data = ensure_course_status($db, $sid);
include("../header/header.php");

?>

<!DOCTYPE html>
<html>
<head>
  <title>Course File</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="../css/bootstrap.min.css">
<style>
#sidebar
{
    padding-left: 10px;
}
#sidebar.active {
  margin-left: -270px;
}
ul
{
    list-style: none;
}
body {
  font-family: "Lato", sans-serif;
}

.sidenav {
  height: 100%;
  width: 230px;
  position: fixed;
  z-index: 1;
  top: 0;
  left: 0;
  background-color: #111;
  overflow-x: hidden;
  padding-top: 20px;
}

.sidenav a {
  padding: 6px 8px 6px 16px;
  height: 20px;
  text-decoration: none;
  font-size: 15px;
  color: #fff;
  display: block;
}

.sidenav a:hover {
  color: #f1f1f1;
  background-color: green; 
}

.main {
  margin-left: 230px; /* Same as the width of the sidenav */
  font-size: 15px; /* Increased text to enable scrolling */
  padding: 0px 10px;
}

@media screen and (max-height: 450px) {
  .sidenav {padding-top: 15px;}
  .sidenav a {font-size: 1px;}
}
th,td
{
   border: 1px solid black;
   text-align: center;
}
.account_options
{
  border: 1px solid black;
  text-align: center;
}
.account_options ul
{
  padding: 0;
  margin-top: 20px;
}
.account_options li
{
  margin-top: 10px;
}
</style>
</head>
<body>

<div class="container">
  <div class="row">
    <div class="col-sm-12">
      <!-- <img src="download.png" style="margin-left: 350px; margin-top: 250px;"> -->
      <div class="container" style="margin-top: 20px;">
        <div class="row">
          <div class="col-sm-6">
            <table class="table table-striped" id="myTable" style="width: 500px;" align="center">
              <col width="100px"> 
              <col width="30px">
              <!-- <col width="80px"> -->
              <!-- <col width="20px"> -->
              <tr>
                <th><font size="4px">Course file</font></th>
                <th><font size="4px">Status</font></th>
              </tr>
                <tr>
                    
                    <td><font size="4px">Individual time table</font></td>
                    <td>
                      <?php if ($data['individual_time_table']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['individual_time_table'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                <tr>
                  <!-- <img src="../icons/checked.svg"> -->
                    
                    <td><font size="4px">Class time table</font></td>
                    <td>
                      <?php if ($data['class_time_table']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['class_time_table'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                  <tr>
                    
                    <td><font size="4px">Course Plan</font></td>
                    <td>
                      <?php if ($data['course_plan']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['course_plan'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                  <tr>
                    
                    <td><font size="4px">Lesson Plan</font></td>
                    <td>
                      <?php if ($data['lesson_plan']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['lesson_plan'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                  <tr>
                    
                    <td><font size="4px">Assignment</font></td>
                    <td>
                      <?php if ($data['assignment']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['assignment'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                  <tr>
                    
                    <td><font size="4px">Mid Question paper</font></td>
                    <td>
                      <?php if ($data['mid']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['mid'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                  <tr>
                    
                    <td><font size="4px">Remedial classes</font></td>
                    <td>
                      <?php if ($data['remedial']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['remedial'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                  <tr>
                    
                    <td><font size="4px">Tutorial</font></td>
                    <td>
                      <?php if ($data['tutorial']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['tutorial'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>
                  <tr>
                    
                    <td><font size="4px">Quiz</font></td>
                    <td>
                      <?php if ($data['quiz']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['quiz'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr>

                  <tr>
                    
                    <td><font size="4px">Notes</font></td>
                    <td>
                      <?php if ($data['notes']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['notes'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr> 
                  <tr>
                    
                    <td><font size="4px">CO-PO Attainment</font></td>
                    <td>
                      <?php if ($data['co_po']=="Completed") echo "<img src='../icons/checked.svg'>";
                          else if(substr($data['co_po'], 0, 5) == "_req_") echo "<img src='../icons/alert.svg'>"; ?>
                    </td>
                  </tr> 
                <!-- </tr> -->
              </table>
          </div>
          <div class="col-sm-6">
            <div class="account_options">
              <h3>Hi <?php echo $_SESSION['name'] ?></h3>
              <ul>
                <li><a href="../login/otp.php?email=<?php echo $email ?>">Change Password</a></li>
                <li><a href="../login/logout.php">Logout</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
   
</body>
</html> 
