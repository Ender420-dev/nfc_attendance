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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Attendance</title>
  <link rel="stylesheet" href="../css/admin.css?v=1.4" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />

  <style>
    /* Toast notification styles */
    .toast {
      visibility: hidden;
      min-width: 250px;
      background: #333;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 12px;
      position: fixed;
      z-index: 1000;
      left: 50%;
      bottom: 30px;
      transform: translateX(-50%);
      font-size: 15px;
      opacity: 0;
      transition: opacity 0.5s, bottom 0.5s;
    }
    .toast.show {
      visibility: visible;
      opacity: 1;
      bottom: 50px;
    }
  </style>
</head>
<body class="dashboard-page">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <img src="../images/salon.jpg" alt="Company Logo" />
    </div>
    <nav>
      <ul class="sidebar-menu">
        <li>
          <a href="admin-dashboard.php" class="menu-link ">
            <i class="fa fa-home"></i> Dashboard
          </a>
        </li>
        <li>
          <a href="employee-management.php" class="menu-link">
            <i class="fa-solid fa-users"></i> Employee Management
          </a>
        </li>
        <li>
          <a href="admin-attendance.php" class="menu-link active">
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
<div class="table">
  <h2>Employee Attendance</h2>
  <table id="Table">
    <thead>
      <tr>
        <th>Employee</th>
        <th>Scan Type</th>
        <th>Time</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody id="attendanceBody">
      <tr><td colspan="4" style="text-align:center;">Loading...</td></tr>
    </tbody>
  </table>
</div>
</section>
</main>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
function showToast(message) {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.className = "toast show";
  setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
}

let lastLatestID = 0; // track new records

function fetchAttendance() {
  fetch("fetch_admin_attendance.php")
    .then(response => response.json())
    .then(data => {
      const tbody = document.getElementById("attendanceBody");
      tbody.innerHTML = "";

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No attendance records found.</td></tr>';
        return;
      }

      data.forEach((row, index) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.FirstName} ${row.LastName}</td>
          <td>${row.ScanType}</td>
          <td>${row.ScanTime}</td>
          <td>${row.Remarks ?? ""}</td>
        `;
        tbody.appendChild(tr);
      });

      // Check for new record
      const latestID = parseInt(data[0].AttendanceID);
      if (latestID > lastLatestID) {
        if (lastLatestID !== 0) {
          showToast(`🔔 ${data[0].FirstName} ${data[0].LastName} just ${data[0].ScanType}!`);
        }
        lastLatestID = latestID;
      }
    })
    .catch(err => {
      console.error("Error fetching attendance:", err);
    });
}

// Initial load
fetchAttendance();
// Refresh every 5s
setInterval(fetchAttendance, 1000);
</script>

<script src="../js/main.js"></script>
</body>
</html>
