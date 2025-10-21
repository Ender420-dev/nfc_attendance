<?php
session_start();
include '../db.php';

// ✅ Allow employee login to work (case-insensitive role/session checks)
$role = $_SESSION['Role'] ?? $_SESSION['role'] ?? null;
$userID = $_SESSION['UserID'] ?? $_SESSION['user_id'] ?? null;

// ✅ Ensure logged-in user
if (empty($userID)) {
    header("Location: ../index.php");
    exit();
}

// ✅ Ensure role is Employee
if (empty($role) || strtolower($role) !== "employee") {
    header("Location: ../index.php");
    exit();
}

// ✅ Get EmployeeID linked to this user
$employeeId = null;
$stmt = $conn->prepare("SELECT EmployeeID FROM users WHERE UserID = ? LIMIT 1");
$stmt->bind_param("i", $userID);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $tmp = $res->fetch_assoc();
    $employeeId = intval($tmp['EmployeeID']);
}
$stmt->close();

if (empty($employeeId)) {
    die("<p style='color:red; text-align:center;'>No employee profile linked to this account. Contact admin.</p>");
}

// ✅ Fetch employee info
$stmt = $conn->prepare("SELECT FirstName, LastName, ShiftStart, ShiftEnd FROM employees WHERE EmployeeID = ? LIMIT 1");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$empRes = $stmt->get_result();

if (!$empRes || $empRes->num_rows === 0) {
    $stmt->close();
    die("<p style='color:red; text-align:center;'>Employee record not found.</p>");
}

$emp = $empRes->fetch_assoc();
$stmt->close();

// ✅ Fetch payroll total for this month
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(NetPay),0) AS totalEarnings
    FROM payroll
    WHERE EmployeeID = ?
      AND MONTH(ProcessedDate) = MONTH(CURDATE())
      AND YEAR(ProcessedDate) = YEAR(CURDATE())
");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$payRes = $stmt->get_result();
$pay = $payRes->fetch_assoc();
$stmt->close();

$shiftStart = !empty($emp['ShiftStart']) ? date("g:i A", strtotime($emp['ShiftStart'])) : 'N/A';
$shiftEnd   = !empty($emp['ShiftEnd']) ? date("g:i A", strtotime($emp['ShiftEnd']))   : 'N/A';

// Example data
$appointmentsToday = 8;
$totalClients = 128;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Employee Dashboard</title>
  <link rel="stylesheet" href="../css/employee.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
  <style>
    .dashboard { display:flex; gap:16px; flex-wrap:wrap; }
    .stat-card { background:#fff; padding:18px; border-radius:8px; box-shadow:0 6px 16px rgba(0,0,0,.06); width:240px; }
    .stat-info h3 { margin:0 0 6px; font-size:14px; color:#666; }
    .stat-info p { margin:0; font-size:20px; font-weight:700; }
    .stat-icon { font-size:28px; color:#777; margin-top:8px; }
  </style>
</head>
<body class="dashboard-page">

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo"><img src="../images/salon.jpg" alt="Company Logo" /></div>
  <nav>
    <ul class="sidebar-menu">
      <li><a href="employee-dashboard.php" class="menu-link active"><i class="fa fa-home"></i> Dashboard</a></li>
      <li><a href="attendance.php" class="menu-link"><i class="fa-solid fa-calendar"></i> Attendance</a></li>
      <li><a href="payroll.php" class="menu-link"><i class="fa-solid fa-money-bill"></i> Payroll</a></li>
      <li><a href="../logout.php" class="menu-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </nav>
</aside>

<main class="dashboard-content">
  <header class="dashboard-header">
    <button class="toggle-btn" id="toggleBtn"><i class="fa-solid fa-bars"></i></button>
    <div class="profile-icon" id="profileIcon">
      <i class="fa-regular fa-circle-user"></i>
      <span><?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?></span>
    </div>
  </header>

  <h1>Welcome, <?= htmlspecialchars($emp['FirstName']) ?>!</h1>

  <div class="dashboard">
    <div class="stat-card">
      <div class="stat-info">
        <h3>Today's Appointments</h3>
        <p><?= htmlspecialchars($appointmentsToday) ?></p>
        <h4 style="font-weight:600;color:#888">+2 from yesterday</h4>
      </div>
      <div class="stat-icon"><i class="fas fa-calendar"></i></div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Current Shift</h3>
        <p><?= htmlspecialchars($shiftStart . " - " . $shiftEnd) ?></p>
        <h4 style="font-weight:600;color:#888">Enjoy your shift!</h4>
      </div>
      <div class="stat-icon"><i class="fas fa-clock"></i></div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>This Month's Earnings</h3>
        <p>₱<?= number_format($pay['totalEarnings'] ?? 0, 2) ?></p>
        <h4 style="font-weight:600;color:#888">15% service commission</h4>
      </div>
      <div class="stat-icon"><i class="fas fa-sack-dollar"></i></div>
    </div>

    <div class="stat-card">
      <div class="stat-info">
        <h3>Total Clients</h3>
        <p><?= htmlspecialchars($totalClients) ?></p>
        <h4 style="font-weight:600;color:#888">12 new this month</h4>
      </div>
      <div class="stat-icon"><i class="fas fa-users"></i></div>
    </div>
  </div>

  <section class="dashboard-section">
    <div class="dashboard-grid">
      <div class="appointments-card">
        <div class="appointments-header">
          <h3>Today's Appointments</h3>
          <a href="#" class="view-all">View All</a>
        </div>

        <div class="appointment-item">
          <div class="icon"><i class="fas fa-cut"></i></div>
          <div class="details"><h4>Jennifer Lopez</h4><p>Full Color & Cut</p></div>
          <div class="time">10:30 AM</div>
        </div>

        <div class="appointment-item">
          <div class="icon"><i class="fas fa-spa"></i></div>
          <div class="details"><h4>Alexis Johnson</h4><p>Hair Treatment</p></div>
          <div class="time">12:00 PM</div>
        </div>

        <div class="appointment-item">
          <div class="icon"><i class="fas fa-brush"></i></div>
          <div class="details"><h4>Sophia Williams</h4><p>Styling Session</p></div>
          <div class="time">2:30 PM</div>
        </div>

        <button class="add-btn">Add New Appointment</button>
      </div>

      <div class="announcements-card">
        <div class="announcements-header"><h3>Team Announcements</h3></div>

        <div class="announcement-item highlight">
          <div class="icon"><i class="fas fa-bullhorn"></i></div>
          <div class="details">
            <h4>New Product Line</h4>
            <p>Starting next week, we'll carry the new Kerastase line. Training on Friday at 4pm.</p>
            <span class="time">2 hours ago</span>
          </div>
        </div>

        <div class="announcement-item">
          <div class="icon gray"><i class="fas fa-calendar-alt"></i></div>
          <div class="details">
            <h4>Holiday Hours</h4>
            <p>The salon will be closed on upcoming public holidays.</p>
            <span class="time">1 day ago</span>
          </div>
        </div>

        <div class="announcement-item">
          <div class="icon gray"><i class="fas fa-trophy"></i></div>
          <div class="details">
            <h4>Employee of the Month</h4>
            <p>Congratulations to Maria for achieving the highest client satisfaction ratings!</p>
            <span class="time">3 days ago</span>
          </div>
        </div>

        <button class="view-btn">View All Notifications</button>
      </div>
    </div>
  </section>
</main>

<script src="../js/main.js"></script>
</body>
</html>
