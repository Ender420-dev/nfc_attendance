<?php
include '../db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $id = $_POST['AttendanceID'];
  $scanType = $_POST['ScanType'];
  $scanTime = $_POST['ScanTime'];
  $remarks = $_POST['Remarks'];

  $stmt = $conn->prepare("UPDATE attendance SET ScanType=?, ScanTime=?, Remarks=? WHERE AttendanceID=?");
  $stmt->bind_param("sssi", $scanType, $scanTime, $remarks, $id);

  if($stmt->execute()){
    echo "Attendance updated successfully!";
  } else {
    echo "Error updating attendance.";
  }
}
?>
