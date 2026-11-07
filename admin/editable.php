<?php include("../header/admin_header.php") ?>

<?php
require "../db.php";

$selectedYear = isset($_GET['acyear']) ? trim($_GET['acyear']) : '';
$notice = isset($_GET['updated']) ? 'Faculty details updated for this subject.' : '';

if (isset($_GET['del_sid']))
{
  $sidToDelete = (int)$_GET['del_sid'];
  $delete = $db->prepare("DELETE FROM subjects WHERE sid = ?");
  $delete->bind_param('i', $sidToDelete);
  $delete->execute();
  $delete->close();
}

$ay = mysqli_query($db, "SELECT DISTINCT academic_year FROM subjects ORDER BY academic_year DESC");

if (isset($_GET['get']) && $selectedYear !== '')
{
  $query = $db->prepare("SELECT s.*, u.name AS account_name, u.userid, COALESCE(NULLIF(s.faculty_name, ''), u.name) AS display_faculty_name FROM subjects s INNER JOIN users u ON u.userid = s.fid WHERE s.academic_year = ? ORDER BY s.section");
  $query->bind_param('s', $selectedYear);
  $query->execute();
  $res = $query->get_result();
  $query->close();
}

?>

<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  #sidebar
  {
      padding-left: 10px;
      margin-left: 0px;
  }
  #sidebar.active {
    margin-left: -280px;
  }
  ul
  {
      list-style: none;
  }
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 60%;
  margin-left: 22%;
   border:1px solid #85144b;
}
body {
  margin: 0;
  font-family: "Lato", sans-serif;

}
th{
  background-color: #009879;
  color:#ffffff;
}
td, th {
  border: 1px solid #dddddd;
  text-align: center;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}

.sidebar {
  /*margin-top: -2%;*/
  margin-top: 0;
  padding: 0;
  width: 200px;
  background-color: #2c3e50;
  position: fixed;
  height: 100%;
  overflow: auto;
}

.sidebar a {
  display: block;
  color: white;
  padding: 16px;
  text-decoration: none;
}
 

.sidebar a.active {
  background-color: black;
  color: white;
}
.sidebar a.active2 {
  background-color: green;
  color: white;
}

.sidebar a:hover:not(.active) {
  background-color: #72587F;
  color: white;
}

div.content {
  margin-left: 200px;
  padding: 1px 16px;
  height: 1000px;
}

@media screen and (max-width: 700px) {
  .sidebar {
    width: 100%;
    height: auto;
    position: relative;
  }
  .sidebar a {float: left;}
  div.content {margin-left: 0;}
}

@media screen and (max-width: 400px) {
  .sidebar a {
    text-align: center;
    float: none;
  }
}
button
{
   /* #4CAF50 (or 0x4CAF50) named as Fruit Salad HEX triplet: 4C, AF and 50.rgb value is (76,175,80). */

/*position from left side*/
  width: 65px;
  padding-top: 6px;
  padding-bottom: 6px;
  font-size: 15px;
  padding-left: 7px;
  border-radius: 5px;
}
.btn{
  color: white;
  background-color: limegreen;
  border: none;
  outline: none;
}
.btn:hover{
background-color:  green;

}
.btn2{
  color: white;
  background-color:  #ff4000;
  border: none;
  outline: none;
}
.btn2:hover{
background-color: red;
}
select{
  margin-top: 30px; 
width: 300px;
padding: 12px 20px;
display: inline-block;
border: 1px solid #ccc;
border-radius: 4px;
box-sizing: border-box;

}
input[name="get"]
{
  height: 40px;
  width: 80px;
}

</style>
</head>
<body>


<form method="get">
  <div align="center">
  <select name="acyear" required>
    <option value="">Select academic year</option>
    <?php while($row = mysqli_fetch_assoc($ay)) { ?>
      <option value="<?php echo htmlspecialchars($row['academic_year'], ENT_QUOTES, 'UTF-8') ?>" <?php echo ($selectedYear === $row['academic_year']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($row['academic_year'], ENT_QUOTES, 'UTF-8') ?></option>
    <?php } ?>
  </select>
  <button type="submit" name="get" value="Show">Show</button>
</form>
</div>
<h1 style="text-align: center;font-family:sans-serif;"></h1>
<?php if ($notice !== '') { ?><div class="alert alert-success" style="margin: 18px auto; width: 60%;"><?php echo $notice; ?></div><?php } ?>
<table>

  <tr>
    <th>Faculty</th>
    <th>Edit Faculty Details</th>
    <th>Subject</th>
    <th>Department</th>
    <th>Year</th>
    <th>Semester</th>
    <th>Section / Branch</th>
    <th>View</th>
      <th>Modify</th>
       <th>Delete</th>
  </tr>

  <?php if (isset($res)) while($row = mysqli_fetch_assoc($res)) { ?>
  <tr>
    <td><?php echo htmlspecialchars($row['display_faculty_name'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><a href="faculty.php?sid=<?php echo (int)$row['sid'] ?>&amp;acyear=<?php echo rawurlencode($selectedYear) ?>">Edit details</a></td>
    <td><?php echo htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?php echo htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><?php echo (int)$row['year'] ?></td>
    <td><?php echo (int)$row['semester'] ?></td>
    <td><?php echo htmlspecialchars($row['section'], ENT_QUOTES, 'UTF-8') ?></td>
    <td><button onclick="window.location.href = '../dashboard/coursefile.php?sid=<?php echo (int)$row['sid'] ?>'" class="btn">view</button></td>
     <td><button onclick="window.location.href = 'table.php?sid=<?php echo (int)$row['sid'] ?>'" class="btn">Review</button></td>
      <td><button onclick="window.location.href = 'editable.php?del_sid=<?php echo (int)$row['sid'] ?>&amp;acyear=<?php echo rawurlencode($selectedYear) ?>&amp;get=Show'" class="btn2">Delete</button></td>
  </tr>
  <?php } ?>
</table>
<br><br>



</body>
</html>
