<?php
session_start();
include '../db.php';

// Ensure only logged-in employee can access
if (!isset($_SESSION['EmployeeID']) || $_SESSION['Role'] !== "Employee") {
    header("Location: ../index.php");
    exit;
}

$employeeID = $_SESSION['EmployeeID'];
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
      <li><a href="employee-dashboard.php" class="menu-link"><i class="fa fa-home"></i> Dashboard</a></li>
      <li><a href="attendance.php" class="menu-link active"><i class="fa-solid fa-calendar"></i> Attendance</a></li>
      <li><a href="payroll.php" class="menu-link"><i class="fa-solid fa-money-bill"></i> Payroll</a></li>
      <li><a href="../logout.php" class="menu-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
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
    <tbody id="attendanceBody">
      <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
    </tbody>
  </table>
</div>
</section>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
  fetch("fetch_attendance.php")
    .then(response => response.json())
    .then(data => {
      const tbody = document.getElementById("attendanceBody");
      tbody.innerHTML = ""; // clear "Loading..."

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No attendance records found.</td></tr>';
        return;
      }

      data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.WorkDate}</td>
          <td>${row.ScanType}</td>
          <td>${row.ScanTime}</td>
          <td>${row.IsLate == 1 ? "Yes" : "No"}</td>
          <td>${row.Remarks}</td>
        `;
        tbody.appendChild(tr);
      });
    })
    .catch(err => {
      console.error("Error fetching attendance:", err);
      document.getElementById("attendanceBody").innerHTML =
        '<tr><td colspan="5" style="text-align:center;color:red;">Error loading records.</td></tr>';
    });
});
document.addEventListener("DOMContentLoaded", () => {
  let lastScanID = null; // store last seen attendance record

  function loadAttendance(showNotif = false) {
    fetch("fetch_attendance.php")
      .then(response => response.json())
      .then(data => {
        const tbody = document.getElementById("attendanceBody");
        tbody.innerHTML = ""; // clear table

        if (data.length === 0) {
          tbody.innerHTML =
            '<tr><td colspan="5" style="text-align:center;">No attendance records found.</td></tr>';
          return;
        }

        data.forEach((row, index) => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td>${row.WorkDate}</td>
            <td>${row.ScanType}</td>
            <td>${row.ScanTime}</td>
            <td>${row.IsLate == 1 ? "Yes" : "No"}</td>
            <td>${row.Remarks}</td>
          `;
          tbody.appendChild(tr);
        });

        // 🔔 Notification check
        const latest = data[0]; // first row = latest
        if (showNotif && lastScanID !== null && latest.AttendanceID !== lastScanID) {
          showNotification(`${latest.ScanType} recorded at ${latest.ScanTime}`);
        }

        // update last seen ID
        lastScanID = latest.AttendanceID;
      })
      .catch(err => {
        console.error("Error fetching attendance:", err);
        document.getElementById("attendanceBody").innerHTML =
          '<tr><td colspan="5" style="text-align:center;color:red;">Error loading records.</td></tr>';
      });
  }

  // 🔔 Simple notification popup
  function showNotification(message) {
    let notif = document.createElement("div");
    notif.textContent = "✅ " + message;
    notif.style.position = "fixed";
    notif.style.top = "20px";
    notif.style.right = "20px";
    notif.style.background = "#4caf50";
    notif.style.color = "white";
    notif.style.padding = "10px 15px";
    notif.style.borderRadius = "8px";
    notif.style.boxShadow = "0 2px 6px rgba(0,0,0,0.3)";
    document.body.appendChild(notif);

    setTimeout(() => notif.remove(), 3000);
  }

  // first load (no notif)
  loadAttendance(false);

  // refresh every 5 seconds (with notif check)
  setInterval(() => loadAttendance(true), 1000);
});
</script>

<script src="../js/main.js"></script>
</body>
</html>
