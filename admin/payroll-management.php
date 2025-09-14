<?php
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payroll Management</title>
  <link rel="stylesheet" href="../css/admin.css?v=1.2"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
</head>
<style>
  .table {
  width: 100%;
  margin-top: 20px;
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.table h2 {
  margin-bottom: 15px;
  font-size: 20px;
  font-weight: 600;
}

.table table {
  width: 100%;
  border-collapse: collapse;
  table-layout: auto;
}

.table th, 
.table td {
  text-align: left;
  padding: 12px 15px;
  border-bottom: 1px solid #eee;
}

.table th {
  background-color: #f7f7f7;
  font-weight: 600;
}

.table tr:hover {
  background-color: #f1f1f1;
}

</style>
<body class="dashboard-page">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <img src="../images/salon.jpg" alt="Company Logo" />
    </div>
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
      <div class="profile-icon" id="profileIcon"><i class="fa-regular fa-circle-user"></i></div>
    </header>

    <section class="main-section">
      <div class="search-filter-container">
        <input type="text" id="searchInput" class="search" placeholder="Search employees..." />
        <div class="filter-dropdown">
          <button id="filterBtn" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
          <ul id="filterMenu" class="filter-menu"> 
            <li id="resetFilters">Reset</li> 
            <li id="sortAZ">A-Z</li>
          </ul>
        </div>
      </div>

      <!-- Payroll Table -->
      <div class="table">
        <h2>Payroll</h2>
        <table id="payrollTable">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Position</th>
              <th>Date Hired</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Dynamic rows will be loaded from DB -->
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script src="../js/main.js"></script>
</body>
</html>
