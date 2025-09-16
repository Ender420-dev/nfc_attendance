<?php
session_start();
include '../db.php';

// Fetch attendance history (latest first)
$sql = "SELECT a.AttendanceID, a.ScanTime, a.ScanType, a.Remarks,
               e.FirstName, e.LastName
        FROM attendance a
        JOIN employees e ON a.EmployeeID = e.EmployeeID
        ORDER BY a.ScanTime DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance</title>
  <link rel="stylesheet" href="../css/admin.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
</head>

<style>
  /* Modal Background */
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    padding-top: 80px;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background: rgba(0, 0, 0, 0.5);
  }

  /* Modal Content */
  .modal-content {
    background: #fff;
    margin: auto;
    padding: 20px;
    border-radius: 10px;
    width: 400px;
    max-width: 90%;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.3);
  }

  .modal-content h2 {
    margin-bottom: 15px;
    text-align: center;
  }

  .modal-content label {
    display: block;
    margin: 10px 0 5px;
  }

  .modal-content input,
  .modal-content select {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
  }

  .modal-content .btn-submit {
    width: 100%;
    padding: 10px;
    background: #2d89ef;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }

  .modal-content .btn-submit:hover {
    background: #1a5bbf;
  }

  .close {
    float: right;
    font-size: 22px;
    cursor: pointer;
  }

  /* Add Attendance Button */
  .btn-add {
    display: inline-block;
    background: #2d89ef;
    /* Primary blue */
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 15px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
  }

  .btn-add:hover {
    background: #1a5bbf;
    /* Darker blue on hover */
    transform: translateY(-2px);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
  }

  .btn-add:active {
    transform: translateY(0);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
  }

  .table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }

  .table-header h2 {
    margin: 0;
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
        <li><a href="employee-management.php" class="menu-link"><i class="fa-solid fa-users"></i> Employee
            Management</a></li>
        <li><a href="admin-attendance.php" class="menu-link active"><i class="fa-solid fa-calendar"></i> Attendance</a>
        </li>
        <li><a href="attendance-management.php" class="menu-link"><i class="fa-solid fa-clipboard-list"></i> Attendance
            Management</a></li>
        <li><a href="payroll-management.php" class="menu-link"><i class="fa-solid fa-money-bill"></i> Payroll
            Management</a></li>
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
            <li class="has-submenu">Date
              <ul class="submenu">
                <li><input type="date" id="dateFilter"></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>

      <div class="clock" id="clock"></div>

      <div class="table">
        <div class="table-header">
          <h2>Attendance History</h2>
          <button id="openModalBtn" class="btn-add">+ Add Attendance</button>
        </div>

        <table id="Table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Date</th>
              <th>Time</th>
              <th>Type</th>
              <th>Remarks</th> <!-- Added Remarks column -->
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()):
                $date = date("Y-m-d", strtotime($row['ScanTime']));
                $time = date("H:i:s", strtotime($row['ScanTime']));
                ?>
                <tr>
                  <td><?php echo $row['FirstName'] . " " . $row['LastName']; ?></td>
                  <td><?php echo $date; ?></td>
                  <td><?php echo $time; ?></td>
                  <td><?php echo $row['ScanType']; ?></td>
                  <td><?php echo isset($row['Remarks']) ? $row['Remarks'] : ''; ?></td> <!-- Display Remarks -->
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" style="text-align:center; color:gray;">No attendance records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>

      </div>
    </section>
  </main>
  <!-- Modal -->
  <div id="attendanceModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Manual Attendance Input</h2>
    <form id="attendanceForm" method="POST" action="manual_attendance_insert.php">
      <label for="employeeId">Employee ID</label>
      <input type="text" name="employeeId" id="employeeId" required>
      <div id="employeeInfo" style="margin:10px 0; font-weight: bold; color: #2d89ef;"></div>

      <label for="scanType">Scan Type</label>
      <select name="scanType" id="scanType" required>
        <option value="IN">Time In</option>
        <option value="OUT">Time Out</option>
      </select>

      <label for="scanTime">Date & Time</label>
      <input type="datetime-local" name="scanTime" id="scanTime" required>

      <input type="hidden" name="remarks" id="remarks" value="Manual Entry">

      <button type="submit" class="btn-submit">Save Attendance</button>
    </form>
  </div>
</div>

<script>
const modal = document.getElementById("attendanceModal");
const openBtn = document.getElementById("openModalBtn");
const closeBtn = document.querySelector(".close");
const employeeIdInput = document.getElementById("employeeId");
const infoBox = document.getElementById("employeeInfo");
const attendanceForm = document.getElementById("attendanceForm");
const tableBody = document.querySelector("#Table tbody");

// Open modal
openBtn.onclick = () => {
  modal.style.display = "block";
  setCurrentDateTime();
  employeeIdInput.value = "";
  infoBox.innerHTML = "";
  employeeIdInput.focus();
};

// Close modal
closeBtn.onclick = () => modal.style.display = "none";
window.onclick = e => { if (e.target == modal) modal.style.display = "none"; }

// Set current date/time
function setCurrentDateTime() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2,"0");
  const d = String(now.getDate()).padStart(2,"0");
  const h = String(now.getHours()).padStart(2,"0");
  const min = String(now.getMinutes()).padStart(2,"0");
  document.getElementById("scanTime").value = `${y}-${m}-${d}T${h}:${min}`;
}

// Fetch employee info as you type
employeeIdInput.addEventListener("input", function () {
  const empId = this.value.trim();
  if (!empId) return infoBox.innerHTML = "";

  fetch("get_employee.php?id=" + empId)
    .then(res => res.json())
    .then(data => {
      if (data.error) infoBox.innerHTML = `<span style="color:red;">${data.error}</span>`;
      else infoBox.innerHTML = `${data.FirstName} ${data.LastName} - ${data.Position}`;
    })
    .catch(()=> infoBox.innerHTML = `<span style="color:red;">Error fetching data</span>`);
});

// Submit attendance via AJAX
attendanceForm.addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(attendanceForm);

  fetch("manual_attendance_insert.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if(data.success){
      modal.style.display = "none";
      // Reset form
      attendanceForm.reset();
      setCurrentDateTime();
      infoBox.innerHTML = "";
      employeeIdInput.focus();

      // Add new row to table
      const newRow = document.createElement("tr");
      const scanDate = new Date(data.ScanTime); // Use returned ScanTime
      const date = scanDate.toISOString().split("T")[0];
      const time = scanDate.toTimeString().split(" ")[0];
      newRow.innerHTML = `
        <td>${data.FirstName} ${data.LastName}</td>
        <td>${date}</td>
        <td>${time}</td>
        <td>${data.ScanType}</td>
        <td>${data.Remarks}</td>
      `;
      tableBody.prepend(newRow);

    } else {
      alert(data.error || "Failed to save attendance");
    }
  })
  .catch(()=> alert("Error saving attendance"));
});
</script>

  <script src="../js/main.js"></script>
</body>

</html>