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
$cardUID = $_GET['uid'] ?? '';

if (!$cardUID) {
    die("No UID received.");
}

// 1. Find card in nfc_card
$stmt = $conn->prepare("SELECT NfcCardID FROM nfc_card WHERE CardUID = ? AND IsActive = 1");
$stmt->bind_param("s", $cardUID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row       = $result->fetch_assoc();
    $nfcCardId = $row['NfcCardID'];

    // 2. Find employee linked to that card
    $stmt2 = $conn->prepare("SELECT EmployeeID, ShiftStart FROM employees WHERE NfcCardID = ?");
    $stmt2->bind_param("i", $nfcCardId);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->num_rows > 0) {
        $row2       = $result2->fetch_assoc();
        $employeeId = $row2['EmployeeID'];
        $shiftStart = $row2['ShiftStart'];

        $today = date('Y-m-d');
        $now   = new DateTime();

        // 3. Check last scan
        $stmtLast = $conn->prepare("
            SELECT ScanType, ScanTime 
            FROM attendance 
            WHERE EmployeeID = ? 
            ORDER BY ScanTime DESC LIMIT 1
        ");
        $stmtLast->bind_param("i", $employeeId);
        $stmtLast->execute();
        $resultLast = $stmtLast->get_result();

        $scanType = 'IN'; // default first scan is IN

        if ($resultLast->num_rows > 0) {
            $lastRow      = $resultLast->fetch_assoc();
            $lastScanType = $lastRow['ScanType'];
            $lastScanTime = new DateTime($lastRow['ScanTime']);

            // Auto-clockout if last scan was IN yesterday
            if ($lastScanType == 'IN' && $lastScanTime->format('Y-m-d') < $today) {
                $stmtAutoOut = $conn->prepare("
                    INSERT INTO attendance (EmployeeID, WorkDate, ScanType, IsLate, ScanTime, Remarks) 
                    VALUES (?, ?, 'OUT', 0, ?, 'Auto Timeout')
                ");
                $autoTime = $lastScanTime->format('Y-m-d 23:59:59');
                $autoDate = $lastScanTime->format('Y-m-d');
                $stmtAutoOut->bind_param("iss", $employeeId, $autoDate, $autoTime);
                $stmtAutoOut->execute();

                // Now start fresh for today
                $scanType = 'IN';
            } else {
                // Normal toggle
                $scanType = ($lastScanType == 'IN') ? 'OUT' : 'IN';
            }
        }

        // 4. Determine lateness (only applies to TIME IN)
        $shift  = new DateTime($shiftStart);
        $isLate = ($scanType == 'IN' && $now > $shift) ? 1 : 0;

        // 5. Insert attendance record
        $stmt3 = $conn->prepare("
            INSERT INTO attendance (EmployeeID, WorkDate, ScanType, IsLate, ScanTime, Remarks)
            VALUES (?, ?, ?, ?, NOW(), 'NFC Scan')
        ");
        $stmt3->bind_param("issi", $employeeId, $today, $scanType, $isLate);

        if ($stmt3->execute()) {
            echo "✅ Attendance logged for EmployeeID $employeeId ($scanType)";
        } else {
            echo "❌ Error: " . $conn->error;
        }

    } else {
        echo "⚠️ No employee linked to this card.";
    }
} else {
    echo "⚠️ Card not recognized or inactive.";
}

$conn->close();
?>
