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
// Fetch attendance + employee info
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
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Attendance Management</title>
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
          <a href="admin-attendance.php" class="menu-link ">
            <i class="fa-solid fa-calendar"></i> Attendance
          </a>
        </li>
        <li>
          <a href="attendance-management.php" class="menu-link active">
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
  <div class="search-filter-container">
    <input type="text" id="searchInput" class="search" placeholder="Search employees..."/>
  </div>

  <div class="table">
  <h2>Employee Attendance Records</h2>
  <table id="Table" class="spread-table">
    <thead>
      <tr>
        <th>Full Name</th>
        <th>Scan Type</th>
        <th>Scan Time</th>
        <th>Status</th>
        <th>Remarks</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="attendanceTableBody">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <?php
            $statusClass = !empty($row['IsLate']) ? 'yellow' : 'green';
            $statusText = !empty($row['IsLate']) ? 'Late' : 'On Time';
          ?>
          <tr data-id="<?= $row['AttendanceID']; ?>">
            <td><?= htmlspecialchars($row['FirstName'] . " " . $row['LastName']); ?></td>
            <td><?= htmlspecialchars($row['ScanType']); ?></td>
            <td><?= !empty($row['ScanTime']) ? date("Y-m-d H:i:s", strtotime($row['ScanTime'])) : '—'; ?></td>
            <td class="status <?= $statusClass; ?>"><?= $statusText; ?></td>
            <td><?= !empty($row['Remarks']) ? htmlspecialchars($row['Remarks']) : '—'; ?></td>
            <td>
  <button class="view" onclick="openViewModal(<?= $row['AttendanceID']; ?>)">View</button>
  <button class="edit" onclick="openEditModal(<?= $row['AttendanceID']; ?>)">Edit</button>
  <button class="delete" onclick="openDeleteModal(<?= $row['AttendanceID']; ?>)">Remove</button>
</td>

          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align:center;color:gray;">No records found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>


</section>
</main>
<!-- View Attendance Modal -->
<div id="viewAttendanceModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal('viewAttendanceModal')">&times;</span>
    <h2 class="modal-title">Attendance Details</h2>
    <div class="details-grid">
      <div class="detail-item">
        <label>Employee Name</label>
        <span id="viewEmployeeName"></span>
      </div>
      <div class="detail-item">
        <label>Scan Type</label>
        <span id="viewScanType"></span>
      </div>
      <div class="detail-item">
        <label>Scan Time</label>
        <span id="viewScanTime"></span>
      </div>
      <div class="detail-item">
        <label>Status</label>
        <span id="viewStatus"></span>
      </div>
      <div class="detail-item">
        <label>Remarks</label>
        <span id="viewRemarks"></span>
      </div>
    </div>
  </div>
</div>

<!-- Edit Attendance Modal -->
<div id="editAttendanceModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal('editAttendanceModal')">&times;</span>
    <h2 class="modal-title">Edit Attendance</h2>
    <form id="editAttendanceForm">
      <input type="hidden" id="editAttendanceID" name="AttendanceID">
      <label>Scan Type</label>
      <select id="editScanType" name="ScanType">
        <option value="IN">Check In</option>
        <option value="OUT">Check Out</option>
      </select>
      <label>Scan Time</label>
      <input type="datetime-local" id="editScanTime" name="ScanTime">
      <label>Remarks</label>
      <input type="text" id="editRemarks" name="Remarks">
      <button type="submit" class="save-btn">Save Changes</button>
    </form>
  </div>
</div>

<script>
// Close modal helper
function closeModal(id) {
  document.getElementById(id).style.display = "none";
}

// -------------------- VIEW --------------------
function openViewModal(attendanceID) {
  const row = document.querySelector(`tr[data-id='${attendanceID}']`);
  document.getElementById('viewEmployeeName').textContent = row.cells[0].textContent;
  document.getElementById('viewScanType').textContent = row.cells[1].textContent;
  document.getElementById('viewScanTime').textContent = row.cells[2].textContent;
  document.getElementById('viewStatus').textContent = row.cells[3].textContent;
  document.getElementById('viewRemarks').textContent = row.cells[4].textContent;

  document.getElementById('viewAttendanceModal').style.display = 'flex';
}

// -------------------- EDIT --------------------
function openEditModal(attendanceID) {
  const row = document.querySelector(`tr[data-id='${attendanceID}']`);
  document.getElementById('editAttendanceID').value = attendanceID;

  // Scan Type (cell 1)
  const scanType = row.cells[1].textContent.trim();
  document.getElementById('editScanType').value = (scanType === 'IN' || scanType === 'Check In') ? 'IN' : 'OUT';

  // Scan Time (cell 2)
  const scanTime = new Date(row.cells[2].textContent);
  document.getElementById('editScanTime').value = scanTime.toISOString().slice(0,16);

  // Remarks (cell 4)
  document.getElementById('editRemarks').value = row.cells[4].textContent;

  document.getElementById('editAttendanceModal').style.display = 'flex';
}

// Handle Edit Form submission
document.getElementById('editAttendanceForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('update-attendance.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    alert(data); // Replace with nicer notification if needed
    location.reload(); // Reload table after update
  })
  .catch(err => console.error(err));
});

// -------------------- DELETE --------------------
function openDeleteModal(attendanceID) {
  if(confirm("Are you sure you want to delete this attendance record?")) {
    fetch('delete-attendance.php', {
      method: 'POST',
      body: new URLSearchParams({ AttendanceID: attendanceID })
    })
    .then(res => res.text())
    .then(data => {
      alert(data);
      // Remove row from table without reloading
      const row = document.querySelector(`tr[data-id='${attendanceID}']`);
      if(row) row.remove();
    })
    .catch(err => console.error(err));
  }
}

// -------------------- SEARCH --------------------
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
