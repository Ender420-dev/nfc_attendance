<?php
session_start();
include '../db.php';

$employeeID = $_SESSION['EmployeeID'];

$sql = "SELECT AttendanceID, WorkDate, ScanType, ScanTime, IsLate, Remarks
        FROM attendance
        WHERE EmployeeID = ?
        ORDER BY ScanTime DESC
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeID);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
?>