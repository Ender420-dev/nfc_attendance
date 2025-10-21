<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['startDate'];
    $endDate   = $_POST['endDate'];

    $dailyBudget    = 200;   // ₱200 per day
    $commissionRate = 0.40;  // 40% per completed appointment

    // Fetch all active employees
    $empResult = $conn->query("SELECT EmployeeID, FirstName, LastName FROM employees WHERE Status='Active'");

    if ($empResult && $empResult->num_rows > 0) {
        while ($emp = $empResult->fetch_assoc()) {
            $empID = $emp['EmployeeID'];

            // Count distinct workdays
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT WorkDate) AS DaysWorked
                FROM attendance
                WHERE EmployeeID = ? AND WorkDate BETWEEN ? AND ?
            ");
            $stmt->bind_param("iss", $empID, $startDate, $endDate);
            $stmt->execute();
            $daysWorked = $stmt->get_result()->fetch_assoc()['DaysWorked'] ?? 0;

            // Count total appointments assigned
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS AssignedCount
                FROM appointment
                WHERE EmployeeID = ? AND dateAppointment BETWEEN ? AND ?
            ");
            $stmt->bind_param("iss", $empID, $startDate, $endDate);
            $stmt->execute();
            $assignedCount = $stmt->get_result()->fetch_assoc()['AssignedCount'] ?? 0;

            // ✅ Join appointment with services for completed ones
            $sql = "
                SELECT COUNT(*) AS CompletedCount, IFNULL(SUM(s.ProcessPrice), 0) AS TotalValue
                FROM appointment a
                JOIN services s ON a.processType = s.processID
                WHERE a.EmployeeID = ?
                  AND LOWER(a.status) = 'completed'
                  AND a.dateAppointment BETWEEN ? AND ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $empID, $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();

            $completedCount = (int)($row['CompletedCount'] ?? 0);
            $totalValue = (float)($row['TotalValue'] ?? 0);

            // 💰 Compute pay
            $basePay = $daysWorked * $dailyBudget;
            $commission = $totalValue * $commissionRate;
            $grossPay = $basePay + $commission;
            $deduction = 0;
            $netPay = $grossPay - $deduction;

            // Remarks show summary info
            $remarks = "Assigned: $assignedCount | Completed: $completedCount | Sales: ₱" . number_format($totalValue, 2);
            $payPeriod = "$startDate to $endDate";

            // 🔹 Insert payroll record
            $stmt = $conn->prepare("
                INSERT INTO payroll (EmployeeID, PayPeriod, GrossPay, Deduction, NetPay, Remarks, ProcessedBy, ProcessedDate)
                VALUES (?, ?, ?, ?, ?, ?, 'System', NOW())
            ");
            $stmt->bind_param("issdds", $empID, $payPeriod, $grossPay, $deduction, $netPay, $remarks);
            $stmt->execute();

            // ✅ Get inserted payroll ID
            $payrollID = $conn->insert_id;

            // 🔽 Save commission breakdown per appointment
            $commissionSQL = "
                SELECT a.appointmentID, s.ProcessName, s.ProcessPrice
                FROM appointment a
                JOIN services s ON a.processType = s.processID
                WHERE a.EmployeeID = ?
                  AND LOWER(a.status) = 'completed'
                  AND a.dateAppointment BETWEEN ? AND ?
            ";
            $commStmt = $conn->prepare($commissionSQL);
            $commStmt->bind_param("iss", $empID, $startDate, $endDate);
            $commStmt->execute();
            $commResult = $commStmt->get_result();

            while ($c = $commResult->fetch_assoc()) {
                $commissionEarned = $c['ProcessPrice'] * $commissionRate;

                $insertComm = $conn->prepare("
                    INSERT INTO payroll_commissions (PayrollID, AppointmentID, ServiceName, ServicePrice, CommissionEarned)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insertComm->bind_param(
                    "iisdd",
                    $payrollID,
                    $c['appointmentID'],
                    $c['ProcessName'],
                    $c['ProcessPrice'],
                    $commissionEarned
                );
                $insertComm->execute();
            }
        }

        echo "<script>alert('✅ Weekly payroll with commissions generated successfully!'); window.location.href='payroll-management.php';</script>";
    } else {
        echo "<script>alert('⚠️ No active employees found.'); window.location.href='payroll-management.php';</script>";
    }
}
?>
