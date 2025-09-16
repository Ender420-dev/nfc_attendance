<?php
include '../db.php'; // adjust path if needed

// Step 1: Find all employees with "In" but no "Out" for yesterday
$sql = "
    SELECT a.EmployeeID, a.AttendanceID, a.ScanTime
    FROM attendance a
    WHERE a.ScanType = 'In'
      AND DATE(a.ScanTime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
      AND NOT EXISTS (
          SELECT 1 FROM attendance b
          WHERE b.EmployeeID = a.EmployeeID
            AND DATE(b.ScanTime) = DATE(a.ScanTime)
            AND b.ScanType = 'Out'
      )
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $insertStmt = $conn->prepare("
        INSERT INTO attendance (EmployeeID, ScanType, IsLate, ScanTime, Remarks)
        VALUES (?, 'Out', 0, ?, 'Auto clockout (missed)')
    ");

    while ($row = $result->fetch_assoc()) {
        $employeeID = $row['EmployeeID'];
        $scanDate = date("Y-m-d", strtotime($row['ScanTime']));
        $autoTime = $scanDate . " 23:59:59";

        $insertStmt->bind_param("is", $employeeID, $autoTime);
        $insertStmt->execute();
    }

    echo "✅ Auto-clockout completed for " . $result->num_rows . " employees.";
} else {
    echo "ℹ️ No missing clockouts found for yesterday.";
}
?>
