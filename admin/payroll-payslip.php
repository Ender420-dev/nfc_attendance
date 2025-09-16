<?php
include '../db.php';

if (isset($_GET['id'])) {
    $payrollID = intval($_GET['id']);

    // Fetch payroll and employee info
    $sql = "SELECT p.*, e.FirstName, e.LastName, e.Position 
            FROM payroll p 
            JOIN employees e ON p.EmployeeID = e.EmployeeID 
            WHERE PayrollID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $payrollID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        // Safely extract start and end dates from PayPeriod
        $dates = explode(" to ", $result['PayPeriod']);
        $startDate = $dates[0];
        $endDate   = isset($dates[1]) ? $dates[1] : $dates[0];

        // 1. Total days worked
        $sqlDays = "SELECT COUNT(DISTINCT DATE(ScanTime)) AS DaysWorked
                    FROM attendance
                    WHERE EmployeeID=? AND DATE(ScanTime) BETWEEN ? AND ?";
        $stmtDays = $conn->prepare($sqlDays);
        $stmtDays->bind_param("iss", $result['EmployeeID'], $startDate, $endDate);
        $stmtDays->execute();
        $daysWorked = $stmtDays->get_result()->fetch_assoc()['DaysWorked'] ?? 0;

        // 2. Total hours worked (pair IN & OUT correctly)
        $sqlHours = "SELECT ScanType, ScanTime
                     FROM attendance
                     WHERE EmployeeID=? AND DATE(ScanTime) BETWEEN ? AND ?
                     ORDER BY ScanTime ASC";
        $stmtHours = $conn->prepare($sqlHours);
        $stmtHours->bind_param("iss", $result['EmployeeID'], $startDate, $endDate);
        $stmtHours->execute();
        $resHours = $stmtHours->get_result();

        $totalSeconds = 0;
        $lastIn = null;

        while ($row = $resHours->fetch_assoc()) {
            if ($row['ScanType'] == 'IN') {
                $lastIn = new DateTime($row['ScanTime']);
            } elseif ($row['ScanType'] == 'OUT' && $lastIn) {
                $out = new DateTime($row['ScanTime']);
                $diff = $out->getTimestamp() - $lastIn->getTimestamp();
                if ($diff > 0) $totalSeconds += $diff;
                $lastIn = null; // reset for next IN-OUT pair
            }
        }

        $totalHours = round($totalSeconds / 3600, 2); // convert to hours
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Payslip</title>
  <style>
    body { font-family: Arial, sans-serif; }
    .payslip {
      width: 400px;
      margin: auto;
      padding: 20px;
      border: 1px solid #333;
      border-radius: 8px;
    }
    h2 { text-align: center; }
    .payslip p { margin: 6px 0; }
  </style>
</head>
<body>
  <div class="payslip">
    <h2>Payslip</h2>
    <?php if ($result): ?>
      <p><strong>Name:</strong> <?= htmlspecialchars($result['FirstName']." ".$result['LastName']) ?></p>
      <p><strong>Position:</strong> <?= htmlspecialchars($result['Position']) ?></p>
      <p><strong>Pay Period:</strong> <?= htmlspecialchars($result['PayPeriod']) ?></p>
      <p><strong>Days Worked:</strong> <?= $daysWorked ?></p>
      <p><strong>Total Hours:</strong> <?= $totalHours ?> hrs</p>
      <p><strong>Gross Pay:</strong> ₱<?= number_format($result['GrossPay'],2) ?></p>
      <p><strong>Deductions:</strong> ₱<?= number_format($result['Deduction'],2) ?></p>
      <p><strong>Net Pay:</strong> ₱<?= number_format($result['NetPay'],2) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($result['Remarks']) ?></p>
      <hr>
      <button onclick="window.print()">🖨 Print Payslip</button>
    <?php else: ?>
      <p style="color:red; text-align:center;">Payroll record not found.</p>
    <?php endif; ?>
  </div>
</body>
</html>
