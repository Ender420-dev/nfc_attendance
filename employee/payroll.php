<?php
session_start();
include '../db.php';

// Make sure employee is logged in
if (!isset($_SESSION['EmployeeID']) || $_SESSION['Role'] !== "Employee") {
  header("Location: ../index.php");
  exit;
}

$employeeId = $_SESSION['EmployeeID'];

$sql = "SELECT PayPeriod, GrossPay, Deduction, NetPay, Remarks, ProcessedBy, ProcessedDate
        FROM payroll
        WHERE EmployeeID = ?
        ORDER BY ProcessedDate DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Employee Payroll</title>
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
    <i class="fa fa-home"></i>
    Dashboard
  </a>
</li>
<li>
  <a href="attendance.php" class="menu-link">
    <i class="fa-solid fa-calendar"></i>
    Attendance
  </a>
</li>

<li>
  <a href="Payroll.php" class="menu-link active">
    <i class="fa-solid fa-money-bill"></i>
    Payroll
  </a>
</li>
<li>
  <a href="../logout.php" class="menu-link">
    <i class="fa-solid fa-right-from-bracket"></i>
    Logout
  </a>
</li>
  </ul>
</nav>
</aside>

<main class="dashboard-content">
  <header class="dashboard-header">
    <button class="toggle-btn" id="toggleBtn">
      <i class="fa-solid fa-bars"></i>
    </button>
      <div class="profile-icon" id="profileIcon">
        <i class="fa-regular fa-circle-user"></i>
      </div>
    </div>
  </header>

<section class="main-section">
<div class="search-filter-container">
  <input type="text" id="searchInput" class="search" placeholder="Search attendance..." />
  <div class="filter-dropdown">
    <button id="filterBtn" class="filter-btn">
      <i class="fa-solid fa-filter"></i> Filter
    </button>
    <ul id="filterMenu" class="filter-menu">
            <li class="has-submenu">
          Pay Period 
          <ul class="submenu">
            <li><input type="date" id="dateFilter" class="submenu-input" /></li>
          </ul>
        <li class="has-submenu">
          Status 
          <ul class="submenu">
            <li data-value="all">All</li>
            <li data-value="paid">Paid</li>
            <li data-value="unpaid">Unpaid</li>
          </ul>
        </li>
    </div>
  </div>

<div class="table">
  <h2>Payroll Summary</h2>
  <table id="Table">
  <thead>
  <tr>
    <th>Pay Period</th>
    <th>Gross Pay</th>
    <th>Deduction</th>
    <th>Net Pay</th>
    <th>Remarks</th>
  </tr>
</thead>

<tbody>
<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $remarksClass = strtolower($row['Remarks']) === 'paid' ? 'green' : 'red';

        echo "<tr>
                <td>{$row['PayPeriod']}</td>
                <td>₱".number_format($row['GrossPay'], 2)."</td>
                <td>₱".number_format($row['Deduction'], 2)."</td>
                <td>₱".number_format($row['NetPay'], 2)."</td>
                <td class='status {$remarksClass}'>{$row['Remarks']}</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6'>No payroll records found for your account.</td></tr>";
}
$stmt->close();
$conn->close();
?>
</tbody>
  </table>
</div>
  </main>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const filterBtn = document.getElementById("filterBtn");
  const filterMenu = document.getElementById("filterMenu");
  const dateFilter = document.getElementById("dateFilter");
  const table = document.getElementById("Table");
  const tbody = table.tBodies[0];
  const rows = Array.from(tbody.rows);

  let selectedFilter = "all";

  filterBtn.addEventListener("click", () => {
    filterMenu.style.display = filterMenu.style.display === "block" ? "none" : "block";
  });

  document.addEventListener("click", (e) => {
    if (!filterBtn.contains(e.target) && !filterMenu.contains(e.target)) {
      filterMenu.style.display = "none";
    }
  });

  document.querySelectorAll(".submenu li[data-value]").forEach(item => {
    item.addEventListener("click", () => {
      selectedFilter = item.getAttribute("data-value");
      filterMenu.style.display = "none";
      filterTable();
    });
  });

  document.getElementById("resetFilters").addEventListener("click", () => {
    searchInput.value = "";
    dateFilter.value = "";
    selectedFilter = "all";
    rows.forEach(row => row.style.display = "");
    filterMenu.style.display = "none";
  });

  function filterTable() {
    const searchValue = searchInput.value.toLowerCase();
    const dateValue = dateFilter.value; 

    let monthYear = "";
    if (dateValue) {
      const d = new Date(dateValue);
      const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
      ];
      monthYear = `${monthNames[d.getMonth()]} ${d.getFullYear()}`;
    }

    rows.forEach(row => {
      const cells = row.cells;
      const payPeriodText = cells[0].textContent.trim(); 
      const statusText = cells[5].textContent.trim().toLowerCase();
      const rowText = Array.from(cells).map(c => c.textContent.toLowerCase()).join(" ");

      const matchesSearch = rowText.includes(searchValue);
      const matchesStatus = selectedFilter === "all" || statusText === selectedFilter.toLowerCase();
      const matchesPayPeriod = !monthYear || payPeriodText === monthYear;

      row.style.display = (matchesSearch && matchesStatus && matchesPayPeriod) ? "" : "none";
    });
  }

  searchInput.addEventListener("input", filterTable);
  dateFilter.addEventListener("change", filterTable);

  filterTable();
});


</script>

<script src="../js/main.js"></script>
</body>
</html>
