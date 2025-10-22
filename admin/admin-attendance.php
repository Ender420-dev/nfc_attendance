<?php
session_start();
include '../db.php';

// 🔐 Session and Role Validation
$role = $_SESSION['role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$role || $role !== 'Admin' || !$userID) {
    session_unset();
    session_destroy();
    header("Refresh:3; url=../index.php");
    echo "<p style='text-align:center; color:red; font-weight:bold;'>⚠️ Unauthorized access. Redirecting to login...</p>";
    exit;
}

// 🧭 Date filter (optional)
$filterDate = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : null;

// 🧩 Query attendance (filtered or all)
if ($filterDate) {
    $sql = "
        SELECT 
            a.AttendanceID, 
            e.FirstName, 
            e.LastName, 
            a.ScanType, 
            a.IsLate, 
            a.WorkDate, 
            a.ScanTime, 
            a.Remarks
        FROM attendance a
        LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID
        WHERE DATE(a.WorkDate) = ?
        ORDER BY a.ScanTime DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $filterDate);
} else {
    $sql = "
        SELECT 
            a.AttendanceID, 
            e.FirstName, 
            e.LastName, 
            a.ScanType, 
            a.IsLate, 
            a.WorkDate, 
            a.ScanTime, 
            a.Remarks
        FROM attendance a
        LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID
        ORDER BY a.WorkDate DESC, a.ScanTime DESC
    ";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Attendance Management</title>
<style>
  .green { color: green; font-weight: bold; }
  .yellow { color: goldenrod; font-weight: bold; }
  table { width: 100%; border-collapse: collapse; margin-top: 15px; }
  th, td { padding: 8px 10px; border: 1px solid #ccc; text-align: center; }
  th { background-color: #f5f5f5; }
  .filter-box { margin-bottom: 15px; }
  .filter-box input[type="date"] { padding: 5px 8px; }
  .filter-box button { padding: 6px 10px; cursor: pointer; }
</style>
<link rel="stylesheet" href="../css/admin.css?v=1.4"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" href="../images/logo.png"/>
</head>
<body class="dashboard-page">

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="../images/salon.jpg" alt="Company Logo" />
  </div>
  <nav>
    <ul class="sidebar-menu">
      <li><a href="admin-dashboard.php" class="menu-link"><i class="fa fa-home"></i> Dashboard</a></li>
      <li><a href="employee-management.php" class="menu-link"><i class="fa-solid fa-users"></i> Employee Management</a></li>
      <li><a href="admin-attendance.php" class="menu-link active"><i class="fa-solid fa-calendar"></i> Attendance</a></li>
      <li><a href="attendance-management.php" class="menu-link"><i class="fa-solid fa-clipboard-list"></i> Attendance Management</a></li>
      <li><a href="payroll-management.php" class="menu-link"><i class="fa-solid fa-money-bill"></i> Payroll Management</a></li>
      <li><a href="../index.php" class="menu-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </nav>
</aside>

<main class="dashboard-content">
<header class="dashboard-header">
  <button class="toggle-btn" id="toggleBtn"><i class="fa-solid fa-bars"></i></button>
  <div class="profile-icon">
    <i class="fa-regular fa-circle-user"></i>
    <span style="margin-left:8px; font-weight:600;">
      <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>
    </span>
  </div>
</header>

<section class="main-section">
  <div class="search-filter-container">
    <input type="text" id="searchInput" class="search" placeholder="Search employees..."/>
  </div>

  <!-- 🔍 Filter by date -->
  <div class="filter-box">
    <form method="GET" action="">
      <label for="date">Filter by Date:</label>
      <input type="date" id="date" name="date" value="<?= htmlspecialchars($filterDate ?? ''); ?>" max="<?= date('Y-m-d'); ?>">
      <button type="submit">Filter</button>
      <a href="admin-attendance.php"><button type="button">Show All</button></a>
    </form>
  </div>

  <div class="table">
    <h2>Employee Attendance Records <?= $filterDate ? "for $filterDate" : "(All Records)"; ?></h2>
    <table id="Table" class="spread-table">
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Scan Type</th>
          <th>Work Date</th>
          <th>Scan Time</th>
          <th>Status</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody id="attendanceTableBody">
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <?php
              $statusClass = !empty($row['IsLate']) ? 'yellow' : 'green';
              $statusText = !empty($row['IsLate']) ? 'Late' : 'On Time';
            ?>
            <tr data-id="<?= $row['AttendanceID']; ?>">
              <td><?= htmlspecialchars($row['FirstName'] . " " . $row['LastName']); ?></td>
              <td><?= htmlspecialchars($row['ScanType']); ?></td>
              <td><?= htmlspecialchars($row['WorkDate']); ?></td>
              <td><?= htmlspecialchars($row['ScanTime']); ?></td>
              <td class="<?= $statusClass; ?>"><?= $statusText; ?></td>
              <td><?= !empty($row['Remarks']) ? htmlspecialchars($row['Remarks']) : '—'; ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align:center;color:gray;">No attendance records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
</main>

<script>
// 🔍 Search filter
const searchInput = document.getElementById("searchInput");
searchInput.addEventListener("keyup", () => {
  const filter = searchInput.value.toLowerCase();
  document.querySelectorAll("#attendanceTableBody tr").forEach(row => {
    const name = row.cells[0].textContent.toLowerCase();
    row.style.display = name.includes(filter) ? "" : "none";
  });
});
</script>
<script src="../js/main.js"></script>
</body>
</html>
