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
        // Split PayPeriod "YYYY-MM-DD to YYYY-MM-DD"
        $dates = explode(" to ", $result['PayPeriod']);
        $startDate = trim($dates[0]);
        $endDate   = isset($dates[1]) ? trim($dates[1]) : $startDate;

        // Count total days worked
        $sqlDays = "SELECT COUNT(DISTINCT WorkDate) AS DaysWorked
                    FROM attendance
                    WHERE EmployeeID=? AND WorkDate BETWEEN ? AND ?";
        $stmtDays = $conn->prepare($sqlDays);
        $stmtDays->bind_param("iss", $result['EmployeeID'], $startDate, $endDate);
        $stmtDays->execute();
        $daysWorked = $stmtDays->get_result()->fetch_assoc()['DaysWorked'] ?? 0;

        // Calculate total time (pair IN and OUT)
        $sqlTime = "SELECT WorkDate, ScanType, ScanTime 
                    FROM attendance
                    WHERE EmployeeID=? AND WorkDate BETWEEN ? AND ?
                    ORDER BY WorkDate, ScanTime ASC";
        $stmtTime = $conn->prepare($sqlTime);
        $stmtTime->bind_param("iss", $result['EmployeeID'], $startDate, $endDate);
        $stmtTime->execute();
        $resTime = $stmtTime->get_result();

        $totalSeconds = 0;
        $inTime = null;
        $currentDate = null;

        while ($row = $resTime->fetch_assoc()) {
            if ($row['ScanType'] === 'IN') {
                $inTime = strtotime($row['ScanTime']);
                $currentDate = $row['WorkDate'];
            } elseif ($row['ScanType'] === 'OUT' && $inTime && $currentDate === $row['WorkDate']) {
                $outTime = strtotime($row['ScanTime']);
                if ($outTime > $inTime) $totalSeconds += ($outTime - $inTime);
                $inTime = null;
            }
        }

        $totalHours = round($totalSeconds / 3600, 2);

        // 🔽 Fetch commission breakdown
        $sqlComm = "SELECT ServiceName, ServicePrice, CommissionEarned 
                    FROM payroll_commissions 
                    WHERE PayrollID = ?";
        $stmtComm = $conn->prepare($sqlComm);
        $stmtComm->bind_param("i", $payrollID);
        $stmtComm->execute();
        $commissionList = $stmtComm->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payslip</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f9f9f9; }
    .payslip {
      width: 420px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    h2, h3 { text-align: center; margin-bottom: 10px; }
    .payslip p { margin: 6px 0; font-size: 15px; }
    .payslip strong { width: 140px; display: inline-block; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 6px;
      text-align: left;
      font-size: 14px;
    }
    th { background-color: #f0f0f0; }
    .total-row {
      font-weight: bold;
      background: #f8f8f8;
    }
    button { background: #3498db; color: #fff; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
    button:hover { background: #2980b9; }
  </style>
</head>
<body>
  <div class="payslip">
    <h2>Employee Payslip</h2>
    <?php if ($result): ?>
      <p><strong>Name:</strong> <?= htmlspecialchars($result['FirstName'].' '.$result['LastName']) ?></p>
      <p><strong>Position:</strong> <?= htmlspecialchars($result['Position']) ?></p>
      <p><strong>Pay Period:</strong> <?= htmlspecialchars($result['PayPeriod']) ?></p>
      <p><strong>Days Worked:</strong> <?= $daysWorked ?></p>
      <!-- <p><strong>Total Hours:</strong> <?= number_format($totalHours, 2) ?> hrs</p> -->
      <p><strong>Gross Pay:</strong> ₱<?= number_format($result['GrossPay'], 2) ?></p>
      <p><strong>Deductions:</strong> ₱<?= number_format($result['Deduction'], 2) ?></p>
      <p><strong>Net Pay:</strong> ₱<?= number_format($result['NetPay'], 2) ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars($result['Remarks']) ?></p>

      <?php if ($commissionList && $commissionList->num_rows > 0): ?>
        <h3>Commission Breakdown</h3>
        <table>
          <thead>
            <tr>
              <th>Service</th>
              <th>Price (₱)</th>
              <th>Commission (₱)</th>
            </tr>
          </thead>
          <tbody>
            <?php 
              $totalCommission = 0;
              while ($c = $commissionList->fetch_assoc()):
                $totalCommission += $c['CommissionEarned'];
            ?>
              <tr>
                <td><?= htmlspecialchars($c['ServiceName']) ?></td>
                <td><?= number_format($c['ServicePrice'], 2) ?></td>
                <td><?= number_format($c['CommissionEarned'], 2) ?></td>
              </tr>
            <?php endwhile; ?>
            <tr class="total-row">
              <td colspan="2">Total Commission</td>
              <td>₱<?= number_format($totalCommission, 2) ?></td>
            </tr>
          </tbody>
        </table>
      <?php endif; ?>

      <hr>
      <p><strong>Processed By:</strong> <?= htmlspecialchars($result['ProcessedBy']) ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($result['ProcessedDate']) ?></p>
      <div style="text-align:center;"><button onclick="window.print()">🖨 Print Payslip</button></div>
    <?php else: ?>
      <p style="color:red; text-align:center;">No record found.</p>
    <?php endif; ?>
  </div>
</body>
</html>
