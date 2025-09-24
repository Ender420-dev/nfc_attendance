<?php
session_start();
include '../db.php';

// Ensure only logged-in employee can access
if (!isset($_SESSION['EmployeeID']) || $_SESSION['Role'] !== "Employee") {
    header("Location: ../index.php");
    exit;
}

$employeeID = $_SESSION['EmployeeID'];

// Fetch attendance records
$sql = "SELECT WorkDate, ScanType, IsLate, ScanTime, Remarks 
        FROM attendance 
        WHERE EmployeeID = ? 
        ORDER BY WorkDate DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Employee Attendance</title>
  <link rel="stylesheet" href="../css/employee.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
</head>
<body class="dashboard-page">
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="../images/salon.jpg" alt="Company Logo" />
  </div>
  <nav>
    <ul class="sidebar-menu">
      <li>
        <a href="employee-dashboard.php" class="menu-link">
          <i class="fa fa-home"></i> Dashboard
        </a>
      </li>
      <li>
        <a href="attendance.php" class="menu-link active">
          <i class="fa-solid fa-calendar"></i> Attendance
        </a>
      </li>
      <li>
        <a href="payroll.php" class="menu-link">
          <i class="fa-solid fa-money-bill"></i> Payroll
        </a>
      </li>
      <li>
        <a href="../logout.php" class="menu-link">
          <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
      </li>
    </ul>
  </nav>
</aside>

<main class="dashboard-content">
  <header class="dashboard-header">
    <button class="toggle-btn" id="toggleBtn"><i class="fa-solid fa-bars"></i></button>
    <div class="profile-icon" id="profileIcon"><i class="fa-regular fa-circle-user"></i></div>
  </header>

<section class="main-section">
<div class="table">
  <h2>Attendance History</h2>
  <table id="Table">
    <thead>
      <tr>
        <th>DATE</th>
        <th>SCAN TYPE</th>
        <th>TIME</th>
        <th>IS LATE</th>
        <th>REMARKS</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['WorkDate']); ?></td>
            <td><?= htmlspecialchars($row['ScanType']); ?></td>
            <td><?= htmlspecialchars($row['ScanTime']); ?></td>
            <td><?= $row['IsLate'] ? "Yes" : "No"; ?></td>
            <td><?= htmlspecialchars($row['Remarks']); ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align:center;">No attendance records found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</section>
</main>

<script src="../js/main.js"></script>
</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>
