<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['startDate'];
    $endDate   = $_POST['endDate'];

    $dailyRate     = 500;   // example daily rate
    $lateDeduction = 50;    // deduction per late

    // Fetch active employees
    $empResult = $conn->query("SELECT EmployeeID, FirstName, LastName FROM employees WHERE Status='Active'");

    if ($empResult && $empResult->num_rows > 0) {
        while ($emp = $empResult->fetch_assoc()) {
            $empID = $emp['EmployeeID'];

            // Days worked (distinct WorkDate)
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT WorkDate) AS DaysWorked
                FROM attendance
                WHERE EmployeeID = ? AND WorkDate BETWEEN ? AND ?
            ");
            $stmt->bind_param("iss", $empID, $startDate, $endDate);
            $stmt->execute();
            $daysWorked = $stmt->get_result()->fetch_assoc()['DaysWorked'] ?? 0;

            // Late count
            $stmt = $conn->prepare("
                SELECT COUNT(*) AS LateCount
                FROM attendance
                WHERE EmployeeID = ? AND IsLate = 1 AND WorkDate BETWEEN ? AND ?
            ");
            $stmt->bind_param("iss", $empID, $startDate, $endDate);
            $stmt->execute();
            $lateCount = $stmt->get_result()->fetch_assoc()['LateCount'] ?? 0;

            // Payroll calculation
            $grossPay  = $daysWorked * $dailyRate;
            $deduction = $lateCount * $lateDeduction;
            $netPay    = $grossPay - $deduction;

            // Insert payroll record
            $sql = "INSERT INTO payroll (EmployeeID, PayPeriod, GrossPay, Deduction, NetPay, Remarks)
                    VALUES (?, ?, ?, ?, ?, 'Pending')";
            $stmt = $conn->prepare($sql);
            $payPeriod = $startDate . " to " . $endDate;
            $stmt->bind_param("issdd", $empID, $payPeriod, $grossPay, $deduction, $netPay);
            $stmt->execute();
        }
    }

    echo "<script>alert('Payroll generated successfully!'); window.location.href='payroll-management.php';</script>";
}
?>
