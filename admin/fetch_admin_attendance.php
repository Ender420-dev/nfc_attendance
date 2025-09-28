<?php
session_start();
include '../db.php';

$sql = "SELECT a.AttendanceID, a.ScanTime, a.ScanType, a.Remarks,
               e.FirstName, e.LastName
        FROM attendance a
        JOIN employees e ON a.EmployeeID = e.EmployeeID
        ORDER BY a.ScanTime DESC";

$result = $conn->query($sql);
$data = [];

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }
}

header('Content-Type: application/json');
echo json_encode($data);
?>