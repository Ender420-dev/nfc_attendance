<?php
$servername = "localhost:3307";
$username   = "root"; 
$password   = ""; 
$dbname     = "salon";

// Connect to DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from ESP8266
$cardUID = $_GET['uid'];

// 1. Find card in nfc_card
$sql = "SELECT NfcCardID FROM nfc_card WHERE CardUID = '$cardUID' AND IsActive = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nfcCardId = $row['NfcCardID'];

    // 2. Find employee linked to that card
    $sql2 = "SELECT EmployeeID, ShiftStart FROM employees WHERE NfcCardID = $nfcCardId";
    $result2 = $conn->query($sql2);

    if ($result2->num_rows > 0) {
        $row2       = $result2->fetch_assoc();
        $employeeId = $row2['EmployeeID'];
        $shiftStart = $row2['ShiftStart'];

       // 3. Check last scan to decide IN or OUT
$sqlLast = "SELECT ScanType, ScanTime 
FROM attendance 
WHERE EmployeeID = $employeeId 
ORDER BY ScanTime DESC LIMIT 1";
$resultLast = $conn->query($sqlLast);

if ($resultLast->num_rows > 0) {
$lastRow = $resultLast->fetch_assoc();
$lastScanType = $lastRow['ScanType'];
$lastScanTime = new DateTime($lastRow['ScanTime']);

// ⚡ Fix: If last scan was IN but no OUT yet (yesterday), auto-clockout
if ($lastScanType == 'IN' && $lastScanTime->format('Y-m-d') < date('Y-m-d')) {
$sqlAutoOut = "INSERT INTO attendance (EmployeeID, ScanType, IsLate, ScanTime, Remarks)
           VALUES ($employeeId, 'OUT', 0, '".$lastScanTime->format('Y-m-d 23:59:59')."', 'Auto Timeout')";
$conn->query($sqlAutoOut);

// Now start fresh for today
$scanType = 'IN';
} else {
// Normal toggle
$scanType = ($lastScanType == 'IN') ? 'OUT' : 'IN';
}
} else {
// First ever scan = IN
$scanType = 'IN';
}


        // 4. Determine lateness (only applies to TIME IN)
        $now    = new DateTime();
        $shift  = new DateTime($shiftStart);
        $isLate = ($scanType == 'IN' && $now > $shift) ? 1 : 0;

        // 5. Insert attendance record
        $sql3 = "INSERT INTO attendance (EmployeeID, ScanType, IsLate, ScanTime, Remarks)
                 VALUES ($employeeId, '$scanType', $isLate, NOW(), 'NFC Scan')";
        
        if ($conn->query($sql3) === TRUE) {
            echo "Attendance logged for EmployeeID $employeeId ($scanType)";
        } else {
            echo "Error: " . $conn->error;
        }

    } else {
        echo "No employee linked to this card.";
    }
} else {
    echo "Card not recognized or inactive.";
}

$conn->close();
?>
