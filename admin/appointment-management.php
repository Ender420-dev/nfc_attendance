<?php
session_start();
include '../db.php';

// 🔐 Session & Role Validation
$role = $_SESSION['role'] ?? null;
$userID = $_SESSION['user_id'] ?? null;

if (!$role || $role !== 'Admin' || !$userID) {
    session_unset();
    session_destroy();
    header("Refresh:3; url=../index.php");
    echo "<p style='text-align:center; color:red; font-weight:bold;'>⚠️ Unauthorized access. Redirecting to login...</p>";
    exit;
}

// 🧭 Optional date filter
$filterDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 🧩 Fetch appointments with employee name and process name
$sql = "
  SELECT 
    a.appointmentID,
    a.clientName,
    a.EmployeeID,
    e.FirstName,
    e.LastName,
    p.processID,
    p.ProcessName,
    p.processPrice,
    p.specialization AS processSpec,
    a.Time,
    a.dateAppointment,
    a.specialization AS appointmentSpec,
    a.status
  FROM appointment a
  LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID
  LEFT JOIN services p ON a.processType = p.processID
  WHERE DATE(a.dateAppointment) = ?
  ORDER BY a.Time ASC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $filterDate);
$stmt->execute();
$result = $stmt->get_result();

// 🧩 Fetch all services for processType dropdown
$servicesQuery = "SELECT processID, ProcessName, processPrice, specialization FROM services ORDER BY ProcessName ASC";
$servicesResult = $conn->query($servicesQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Appointment Management</title>
<link rel="stylesheet" href="../css/admin.css?v=1.4">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" href="../images/logo.png"/>
<style>
.table { width: 100%; border-collapse: collapse; margin-top: 15px; overflow-x:auto; }
th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
th { background-color: #f5f5f5; }
.green { color: green; font-weight: bold; }
.yellow { color: goldenrod; font-weight: bold; }
.filter-box { margin-bottom: 15px; }
.filter-box input[type="date"] { padding: 6px 10px; }
.filter-box button { padding: 6px 10px; }
.modal { display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; }
.modal-content { background:white; padding:20px; border-radius:10px; width:400px; max-width:90%; position:relative; }
.close-btn { position:absolute; top:10px; right:15px; cursor:pointer; font-size:22px; }
.modal-content label { display:block; margin-top:10px; font-weight:600; }
.modal-content input, .modal-content select { width:100%; padding:6px; margin-top:4px; }
.save-btn { margin-top:10px; background:#007bff; color:white; border:none; padding:8px 12px; cursor:pointer; border-radius:5px; }
.save-btn:hover { background:#0056b3; }
.price-display { font-weight:bold; color:#444; margin-top:4px; }
</style>
</head>
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
      <li><a href="payroll-management.php" class="menu-link"><i class="fa-solid fa-money-bill"></i> Payroll Management</a></li>
      <li><a href="appointment-management.php" class="menu-link active"><i class="fa-solid fa-calendar-check"></i> Appointment Management</a></li>
      <li><a href="../index.php" class="menu-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
  </nav>
</aside>

<!-- Main Content -->
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
    <input type="text" id="searchInput" class="search" placeholder="Search appointments..."/>
  </div>

  <div class="filter-box">
    <form method="GET" action="">
      <label for="date">Filter by Date:</label>
      <input type="date" id="date" name="date" value="<?= htmlspecialchars($filterDate); ?>" required>
      <button type="submit">Filter</button>
      <a href="appointment-management.php"><button type="button">Show All</button></a>
    </form>
  </div>

  <div class="table">
    <h2>Appointment Records</h2>
    <table id="Table" class="spread-table">
      <thead>
        <tr>
          <th>Client Name</th>
          <th>Assigned Employee</th>
          <th>Process Type</th>
          <th>Specialization</th>
          <th>Appointment Time</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="appointmentTableBody">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $statusClass = ($row['status'] === 'Completed') ? 'green' : 'yellow';
            $employeeName = !empty($row['FirstName']) ? $row['FirstName'] . ' ' . $row['LastName'] : '—';
          ?>
          <tr 
            data-id="<?= $row['appointmentID']; ?>" 
            data-employee="<?= $row['EmployeeID']; ?>" 
            data-processid="<?= $row['processID']; ?>" 
            data-price="<?= htmlspecialchars($row['processPrice']); ?>" 
            data-spec="<?= htmlspecialchars($row['processSpec']); ?>"
          >
            <td><?= htmlspecialchars($row['clientName']); ?></td>
            <td><?= htmlspecialchars($employeeName); ?></td>
            <td><?= htmlspecialchars($row['ProcessName']); ?></td>
            <td><?= htmlspecialchars($row['appointmentSpec']); ?></td>
            <td><?= htmlspecialchars($row['Time']); ?></td>
            <td><?= htmlspecialchars($row['dateAppointment']); ?></td>
            <td class="<?= $statusClass; ?>"><?= htmlspecialchars($row['status']); ?></td>
            <td>
              <button class="edit" onclick="openEditModal(<?= $row['appointmentID']; ?>)">Edit</button>
              <button class="delete" onclick="deleteAppointment(<?= $row['appointmentID']; ?>)">Delete</button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="8" style="text-align:center;color:gray;">No appointments found for this date.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
</main>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
    <h2>Edit Appointment</h2>
    <form id="editForm" class="edit-form">
      <input type="hidden" name="appointmentID" id="editAppointmentID">

      <label>Client Name</label>
      <input type="text" name="clientName" id="editClient" required>

      <label>Assigned Employee</label>
      <select name="EmployeeID" id="editEmployee" required>
        <option value="">-- Select Employee --</option>
        <?php
          $empRes = $conn->query("SELECT EmployeeID, CONCAT(FirstName, ' ', LastName) AS fullName FROM employees");
          while($emp = $empRes->fetch_assoc()):
        ?>
          <option value="<?= $emp['EmployeeID'] ?>"><?= htmlspecialchars($emp['fullName']) ?></option>
        <?php endwhile; ?>
      </select>

      <label>Process Type</label>
      <select name="processType" id="editProcessType" required>
        <option value="">-- Select Process --</option>
        <?php 
        $servicesResult->data_seek(0); // Reset pointer
        while ($srv = $servicesResult->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($srv['processID']); ?>" 
                  data-name="<?= htmlspecialchars($srv['ProcessName']); ?>"
                  data-price="<?= htmlspecialchars($srv['processPrice']); ?>"
                  data-spec="<?= htmlspecialchars($srv['specialization']); ?>">
            <?= htmlspecialchars($srv['ProcessName']); ?>
          </option>
        <?php endwhile; ?>
      </select>

      <div id="priceDisplay" class="price-display"></div>

      <label>Specialization</label>
      <input type="text" name="specialization" id="editSpec" required>

      <label>Date</label>
      <input type="date" name="dateAppointment" id="editDate" required>

      <label>Time</label>
      <input type="time" name="Time" id="editTime" required>

      <label>Status</label>
      <select name="status" id="editStatus" required>
        <option value="Pending">Pending</option>
        <option value="Ongoing">Ongoing</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
      </select>

      <button type="submit" class="save-btn">💾 Save Changes</button>
    </form>
  </div>
</div>

<script>
function closeModal(id){ document.getElementById(id).style.display='none'; }

// ✅ Open Edit Modal and auto-select fields
function openEditModal(id) {
  const row = document.querySelector(`tr[data-id='${id}']`);
  if (!row) return alert('Row not found');

  document.getElementById('editAppointmentID').value = id;
  document.getElementById('editClient').value = row.cells[0].textContent.trim();
  document.getElementById('editEmployee').value = row.getAttribute('data-employee') || "";

  const processID = row.getAttribute('data-processid');
  const processSelect = document.getElementById('editProcessType');
  processSelect.value = processID;

  const selectedOption = processSelect.options[processSelect.selectedIndex];
  document.getElementById('priceDisplay').textContent = selectedOption.getAttribute('data-price') ? `Price: ₱${selectedOption.getAttribute('data-price')}` : "";
  document.getElementById('editSpec').value = selectedOption.getAttribute('data-spec') || "";

  document.getElementById('editTime').value = row.cells[4].textContent.trim();
  document.getElementById('editDate').value = row.cells[5].textContent.trim();
  document.getElementById('editStatus').value = row.cells[6].textContent.trim();

  document.getElementById('editModal').style.display = 'flex';
}

// 🧩 Update price and specialization dynamically when process type changes
document.getElementById('editProcessType').addEventListener('change', function() {
  const selected = this.options[this.selectedIndex];
  const price = selected.getAttribute('data-price');
  const spec = selected.getAttribute('data-spec');

  document.getElementById('priceDisplay').textContent = price ? `Price: ₱${price}` : "";
  document.getElementById('editSpec').value = spec || "";
});

// ✅ Submit Edit Form
document.getElementById('editForm').addEventListener('submit', (e) => {
  e.preventDefault();
  fetch('update-appointment.php', {
    method: 'POST',
    body: new FormData(e.target)
  })
  .then(r => r.text())
  .then(data => {
    alert(data);
    location.reload();
  })
  .catch(err => console.error(err));
});

// ✅ Delete Function
function deleteAppointment(id){
  if(confirm('Delete this appointment?')){
    fetch('delete-appointment.php',{
      method:'POST',
      body:new URLSearchParams({appointmentID:id})
    }).then(r=>r.text()).then(d=>{
      alert(d);
      document.querySelector(`tr[data-id='${id}']`).remove();
    }).catch(err=>console.error(err));
  }
}

// ✅ Search Filter
document.getElementById('searchInput').addEventListener('keyup',()=>{
  const f=document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#appointmentTableBody tr').forEach(r=>{
    const n=r.cells[0].textContent.toLowerCase();
    r.style.display=n.includes(f)?'':'none';
  });
});
</script>

<script src="../js/main.js"></script>
</body>
</html>
