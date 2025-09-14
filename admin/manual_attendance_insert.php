<?php
session_start();
include '../db.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employeeId = intval($_POST['employeeId']);
    $scanType   = $_POST['scanType'];
    $scanTime   = $_POST['scanTime']; // datetime-local from modal
    $remarks    = $_POST['remarks'] ?? 'Manual Entry';

    // Fetch employee info
    $empRes = $conn->query("SELECT FirstName, LastName, ShiftStart FROM employees WHERE EmployeeID = $employeeId");
    if (!$empRes || $empRes->num_rows == 0) {
        echo json_encode(['success' => false, 'error' => 'Employee not found']);
        exit;
    }

    $emp = $empRes->fetch_assoc();

    // Late check only for Time IN
    $isLate = 0;
    if ($scanType == "IN") {
        $shiftStart = new DateTime($emp['ShiftStart']);
        $scanDT     = new DateTime($scanTime);
        if ($scanDT > $shiftStart) $isLate = 1;
    }

    // Insert attendance
    $stmt = $conn->prepare("INSERT INTO attendance (EmployeeID, ScanType, IsLate, ScanTime, Remarks) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisds", $employeeId, $scanType, $isLate, $scanTime, $remarks);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'FirstName' => $emp['FirstName'],
            'LastName'  => $emp['LastName'],
            'ScanType'  => $scanType,
            'ScanTime'  => $scanTime,
            'Remarks'   => $remarks
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
?>
