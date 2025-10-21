<?php
include '../db.php';
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit();
}
// Ensure only admin can access
if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "Admin") {
    header("Location: ../index.php");
    exit;
}
// Fetch payroll records
$sql = "SELECT p.PayrollID, e.FirstName, e.LastName, p.PayPeriod, p.GrossPay, p.Deduction, p.NetPay, p.Remarks 
        FROM payroll p
        JOIN employees e ON p.EmployeeID = e.EmployeeID
        ORDER BY p.PayPeriod DESC";
$result = $conn->query($sql);

// Weekly summary: assigned & completed appointments
$summaryQuery = $conn->query("
    SELECT e.EmployeeID, e.FirstName, e.LastName,
           COUNT(a.appointmentID) AS AssignedCount,
           SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) AS CompletedCount
    FROM employees e
    LEFT JOIN appointment a ON e.EmployeeID = a.EmployeeID
      AND YEARWEEK(a.dateAppointment, 1) = YEARWEEK(CURDATE(), 1)
    GROUP BY e.EmployeeID
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Payroll Management</title>
  <link rel="stylesheet" href="../css/admin.css?v=1.4" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
  <style>
    .table { width: 100%; margin-top: 20px; background: #fff; border-radius: 8px; padding: 20px;
             box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .table h2 { margin-bottom: 15px; font-size: 20px; font-weight: 600; }
    .table table { width: 100%; border-collapse: collapse; }
    .table th, .table td { text-align: left; padding: 12px 15px; border-bottom: 1px solid #eee; }
    .table th { background-color: #f7f7f7; font-weight: 600; }
    .table tr:hover { background-color: #f1f1f1; }
    .actions button { margin-right: 5px; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-view { background: #3498db; color: white; }
    .btn-edit { background: #f39c12; color: white; }
    .btn-paid { background: #27ae60; color: white; }
    .btn-payslip { background: #9b59b6; color: white; }
    .btn-generate { background: #2ecc71; color: white; padding: 10px 15px; border-radius: 6px; text-decoration: none; }
    .performance-table { margin-top: 40px; }
  </style>
</head>

<body class="dashboard-page">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo"><img src="../images/salon.jpg" alt="Company Logo" /></div>
    <nav>
      <ul class="sidebar-menu">
        <li><a href="admin-dashboard.php" class="menu-link"><i class="fa fa-home"></i> Dashboard</a></li>
        <li><a href="employee-management.php" class="menu-link"><i class="fa-solid fa-users"></i> Employee Management</a></li>
        <li><a href="admin-attendance.php" class="menu-link"><i class="fa-solid fa-calendar"></i> Attendance</a></li>
        <li><a href="attendance-management.php" class="menu-link"><i class="fa-solid fa-clipboard-list"></i> Attendance Management</a></li>
        <li><a href="payroll-management.php" class="menu-link active"><i class="fa-solid fa-money-bill"></i> Payroll Management</a></li>
        <li><a href="../index.php" class="menu-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
      </ul>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="dashboard-content">
  <header class="dashboard-header">
  <button class="toggle-btn" id="toggleBtn"><i class="fa-solid fa-bars"></i></button>
  <div class="profile-icon">
    <i class="fa-regular fa-circle-user"></i>
    <span style="margin-left:8px; font-weight:600;">
      <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>
    </span>
  </div>
</header>

    <section class="main-section">
      <div class="search-filter-container">
        <input type="text" id="searchInput" class="search" placeholder="Search payroll records..." />
        <button class="btn-generate" onclick="openPayrollModal()">
          <i class="fa-solid fa-plus"></i> Generate Payroll
        </button>
        <button class="btn-generate" onclick="generateThisWeek()">
          <i class="fa-solid fa-calendar-week"></i> Generate This Week
        </button>
      </div>

      <!-- Payroll Generate Modal -->
      <div id="payrollModal" class="modal" style="display:none;">
        <div class="modal-content">
          <span class="close-btn" onclick="closePayrollModal()">&times;</span>
          <h2>Generate Payroll (Custom Date Range)</h2>
          <form method="POST" action="payroll-generate-process.php">
            <label>Start Date:</label>
            <input type="date" name="startDate" required>
            <label>End Date:</label>
            <input type="date" name="endDate" required>
            <button type="submit" class="btn-generate">Generate</button>
          </form>
        </div>
      </div>

    <!-- Payroll Table -->
<div class="table">
  <h2>Payroll Records</h2>
  <table id="payrollTable">
    <thead>
      <tr>
        <th>Employee</th>
        <th>Pay Period</th>
        <th>Gross Pay</th>
        <th>Deductions</th>
        <th>Net Pay</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Fetch all payrolls and employee names
      $sql = "SELECT p.*, e.FirstName, e.LastName 
              FROM payroll p 
              JOIN employees e ON p.EmployeeID = e.EmployeeID 
              ORDER BY p.ProcessedDate DESC";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo "<tr>
                  <td>" . htmlspecialchars($row['FirstName']) . " " . htmlspecialchars($row['LastName']) . "</td>
                  <td>" . htmlspecialchars($row['PayPeriod']) . "</td>
                  <td>₱" . number_format($row['GrossPay'], 2) . "</td>
                  <td>₱" . number_format($row['Deduction'], 2) . "</td>
                  <td><strong>₱" . number_format($row['NetPay'], 2) . "</strong></td>
                  <td>" . htmlspecialchars($row['Remarks']) . "</td>
                  <td class='actions'>
                    <a href='payslip.php?id=" . $row['PayrollID'] . "' target='_blank' class='btn-payslip'>📄 Payslip</a>
                    <a href='payroll-mark-paid.php?id=" . $row['PayrollID'] . "' class='btn-paid' onclick='return confirm(\"Mark this payroll as paid?\")'>💰 Mark Paid</a>
                  </td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='7'>No payroll records found.</td></tr>";
      }
      ?>
    </tbody>
  </table>
</div>

      <!-- Weekly Performance Table -->
      <div class="table performance-table">
        <h2>This Week’s Employee Performance</h2>
        <table>
          <thead>
            <tr>
              <th>Employee</th>
              <th>Appointments Assigned</th>
              <th>Appointments Completed</th>
            </tr>
          </thead>
          <tbody>
            <?php
            while ($s = $summaryQuery->fetch_assoc()) {
              echo "<tr>
                      <td>" . htmlspecialchars($s['FirstName']) . " " . htmlspecialchars($s['LastName']) . "</td>
                      <td>" . (int)$s['AssignedCount'] . "</td>
                      <td>" . (int)$s['CompletedCount'] . "</td>
                    </tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script src="../js/main.js"></script>
  <script>
    function openPayrollModal() { document.getElementById("payrollModal").style.display = "block"; }
    function closePayrollModal() { document.getElementById("payrollModal").style.display = "none"; }

    // Auto-generate this week's payroll
    function generateThisWeek() {
      if (confirm("Generate payroll for this week (Monday–Sunday)?")) {
        window.location.href = "generate_payroll.php";
      }
    }

    // Search payroll table
    document.getElementById("searchInput").addEventListener("keyup", function () {
      let filter = this.value.toLowerCase();
      document.querySelectorAll("#payrollTable tbody tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
      });
    });
  </script>
</body>
</html>
