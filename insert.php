<?php
$servername = "localhost:3307";
$username   = "root";
$password   = "";
$dbname     = "salon";

header('Content-Type: text/plain'); // simple response for ESP8266

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("ERROR: DB_CONNECT_FAIL");
}

$cardUID = $_GET['uid'] ?? '';

if (!$cardUID) {
    echo "ERROR: NO_UID";
    exit;
}

// 1️⃣ Find NFC card and check if active
$stmt = $conn->prepare("SELECT NfcCardID FROM nfc_card WHERE CardUID = ? AND IsActive = 1");
$stmt->bind_param("s", $cardUID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "NOT_FOUND"; // Card not registered or deactivated
    exit;
}

$row       = $result->fetch_assoc();
$nfcCardId = $row['NfcCardID'];

// 2️⃣ Find linked employee and check status
$stmt2 = $conn->prepare("SELECT EmployeeID, Status, ShiftStart FROM employees WHERE NfcCardID = ?");
$stmt2->bind_param("i", $nfcCardId);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($result2->num_rows === 0) {
    echo "NOT_FOUND"; // No linked employee
    exit;
}

$row2       = $result2->fetch_assoc();
$employeeId = $row2['EmployeeID'];
$status     = strtolower(trim($row2['Status']));
$shiftStart = $row2['ShiftStart'];

// 3️⃣ Status validation
if ($status === 'inactive') {
    echo "INACTIVE";
    exit;
} elseif ($status === 'terminated') {
    echo "TERMINATED";
    exit;
}

// 4️⃣ Prepare attendance logic
$today = date('Y-m-d');
$now   = new DateTime();

// Get last scan
$stmtLast = $conn->prepare("
    SELECT ScanType, ScanTime 
    FROM attendance 
    WHERE EmployeeID = ? 
    ORDER BY ScanTime DESC LIMIT 1
");
$stmtLast->bind_param("i", $employeeId);
$stmtLast->execute();
$resultLast = $stmtLast->get_result();

$scanType = 'IN';

if ($resultLast->num_rows > 0) {
    $lastRow      = $resultLast->fetch_assoc();
    $lastScanType = $lastRow['ScanType'];
    $lastScanTime = new DateTime($lastRow['ScanTime']);

    // Auto clock-out if last IN was from previous day
    if ($lastScanType == 'IN' && $lastScanTime->format('Y-m-d') < $today) {
        $stmtAutoOut = $conn->prepare("
            INSERT INTO attendance (EmployeeID, WorkDate, ScanType, IsLate, ScanTime, Remarks) 
            VALUES (?, ?, 'OUT', 0, ?, 'Auto Timeout')
        ");
        $autoTime = $lastScanTime->format('Y-m-d 23:59:59');
        $autoDate = $lastScanTime->format('Y-m-d');
        $stmtAutoOut->bind_param("iss", $employeeId, $autoDate, $autoTime);
        $stmtAutoOut->execute();
        $scanType = 'IN';
    } else {
        $scanType = ($lastScanType == 'IN') ? 'OUT' : 'IN';
    }
}

// 5️⃣ Determine lateness
$shift  = new DateTime($shiftStart);
$isLate = ($scanType == 'IN' && $now > $shift) ? 1 : 0;

// 6️⃣ Insert attendance
$stmt3 = $conn->prepare("
    INSERT INTO attendance (EmployeeID, WorkDate, ScanType, IsLate, ScanTime, Remarks)
    VALUES (?, ?, ?, ?, NOW(), 'NFC Scan')
");
$stmt3->bind_param("issi", $employeeId, $today, $scanType, $isLate);

if ($stmt3->execute()) {
    echo "SUCCESS";
} else {
    echo "ERROR";
}

$conn->close();
?>
