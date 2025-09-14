<?php 
session_start();
include '../db.php';

// Fetch total employees
$totalEmployees = 0;
$presentEmployees = 0;
$leaveEmployees = 0;

$sql = "SELECT COUNT(*) as total FROM employees";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $totalEmployees = $row['total'];
}

// Present employees
$sql = "SELECT COUNT(*) as present FROM employees WHERE Status = 'Present'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $presentEmployees = $row['present'];
}

// Employees on leave
$sql = "SELECT COUNT(*) as onLeave FROM employees WHERE Status = 'Leave'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $leaveEmployees = $row['onLeave'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/style copy.css"/>
  <link rel="stylesheet" href="../css/admin.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
</head>
<body class="dashboard-page">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <img src="../images/salon.jpg" alt="Company Logo" />
    </div>
    <nav>
      <ul class="sidebar-menu">
        <li>
          <a href="admin-dashboard.php" class="menu-link active">
            <i class="fa fa-home"></i> Dashboard
          </a>
        </li>
        <li>
          <a href="employee-management.php" class="menu-link">
            <i class="fa-solid fa-users"></i> Employee Management
          </a>
        </li>
        <li>
          <a href="admin-attendance.php" class="menu-link">
            <i class="fa-solid fa-calendar"></i> Attendance
          </a>
        </li>
        <li>
          <a href="attendance-management.php" class="menu-link">
            <i class="fa-solid fa-clipboard-list"></i> Attendance Management
          </a>
        </li>
        <li>
          <a href="payroll-management.php" class="menu-link">
            <i class="fa-solid fa-money-bill"></i> Payroll Management
          </a>
        </li>
        <li>
          <a href="../index.php" class="menu-link">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
          </a>
        </li>
      </ul>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="dashboard-content">
    <header class="dashboard-header">
      <button class="toggle-btn" id="toggleBtn">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="profile-icon" id="profileIcon">
        <i class="fa-regular fa-circle-user"></i>
      </div>
    </header>

    <h1>Admin Dashboard</h1>

    <!-- Stats Section -->
    <div class="dashboard">
      <div class="stats">

        <div class="stat-card">
          <div class="stat-info">
            <h3>Total Employees</h3>
            <p>Registered employees in the system</p>
            <h2><?php echo $totalEmployees; ?></h2>
          </div>
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3>Pending Payrolls</h3>
            <p>To be processed</p>
            <h2>--</h2>
          </div>
          <div class="stat-icon">
            <i class="fas fa-wallet"></i>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3>Present Employees</h3>
            <p>Currently clocked in</p>
            <h2><?php echo $presentEmployees; ?></h2>
          </div>
          <div class="stat-icon">
            <i class="fas fa-user-check"></i>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-info">
            <h3>Employees on Leave</h3>
            <p>Approved leave requests</p>
            <h2><?php echo $leaveEmployees; ?></h2>
          </div>
          <div class="stat-icon">
            <i class="fas fa-user-minus"></i>
          </div>
        </div>

      </div>
    </div>

    <!-- Placeholders for future database-driven sections -->
    <section class="dashboard-section">
      <div class="dashboard-grid">

        <div class="appointments-card">
          <div class="appointments-header">
            <h3>Today's Appointments</h3>
            <a href="#" class="view-all">View All</a>
          </div>

          <p style="text-align:center; color:gray;">No appointments available.</p>
          <button class="add-btn">Add New Appointment</button>
        </div>

        <div class="announcements-card">
          <div class="announcements-header">
            <h3>Team Announcements</h3>
          </div>

          <p style="text-align:center; color:gray;">No announcements yet.</p>
          <button class="view-btn">View All Notifications</button>
        </div>

      </div>
    </section>

  </main>
  <script src="../js/main.js"></script>
</body>
</html>
