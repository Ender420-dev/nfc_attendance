<?php
include '../db.php';
header('Content-Type: application/json');

$date = $_GET['date'] ?? date('Y-m-d');

// ✅ Adjusted query to fit standard column names
$sql = "
  SELECT 
    a.AttendanceID,
    e.FirstName,
    e.LastName,
    a.ScanType,
    TIME_FORMAT(a.ScanTime, '%H:%i:%s') AS ScanTime,
    a.IsLate,
    a.Remarks
  FROM attendance a
  LEFT JOIN employee e ON a.EmployeeID = e.EmployeeID
  WHERE DATE(a.WorkDate) = ?
  ORDER BY a.AttendanceID DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "SQL Prepare Failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data ?: []);
?>
