<?php
include '../db.php';
$data=[];
$sql="SELECT a.AttendanceID, a.Date, a.TimeIn, a.TimeOut, a.IsLate, a.Remarks,
             CONCAT(e.FirstName,' ',e.LastName) AS FullName
      FROM attendance a
      JOIN employees e ON a.EmployeeID=e.EmployeeID
      ORDER BY a.Date DESC";
$res=$conn->query($sql);
while($row=$res->fetch_assoc()){
  $data[]= [
    "AttendanceID"=>$row['AttendanceID'],
    "FullName"=>$row['FullName'],
    "TimeIn"=>$row['TimeIn'],
    "TimeOut"=>$row['TimeOut'],
    "Date"=>$row['Date'],
    "Status"=>$row['IsLate']? "Late":"Present",
    "Remarks"=>$row['Remarks']
  ];
}
echo json_encode($data);
?>
