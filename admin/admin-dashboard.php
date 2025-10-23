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
// =========================
// EMPLOYEE STATS
// =========================
$totalEmployees = 0;
$presentEmployees = 0;
$leaveEmployees = 0;

$sql = "SELECT COUNT(*) as total FROM employees";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) $totalEmployees = $row['total'];

$sql = "SELECT COUNT(*) as present FROM employees WHERE Status = 'Present'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) $presentEmployees = $row['present'];

$sql = "SELECT COUNT(*) as onLeave FROM employees WHERE Status = 'Leave'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) $leaveEmployees = $row['onLeave'];

// =========================
// TODAY'S APPOINTMENTS
// Use DATE(...) in case dateAppointment is DATETIME
// =========================
$today = date('Y-m-d');
$appointments = [];
$sql = "SELECT a.*, e.FirstName, e.LastName 
        FROM appointment a 
        LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID 
        WHERE DATE(a.dateAppointment) = '$today' 
        ORDER BY a.Time ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $appointments[] = $row;
}

// =========================
// EMPLOYEE LIST
// =========================
$employees = [];
$sql = "SELECT EmployeeID, FirstName, LastName FROM employees ORDER BY FirstName ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $employees[] = $row;
}

// =========================
// SERVICES LIST
// =========================
// alias ProcessID to lower-case key so PHP array index is predictable
$services = [];
$sql = "SELECT ProcessID AS processID, ProcessName, ProcessPrice, specialization FROM services ORDER BY ProcessName ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) $services[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/style copy.css"/>
  <link rel="stylesheet" href="../css/admin.css?v=2.1"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
  <style>
    .appointment-list { list-style: none; padding: 0; }
    .appointment-list li { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); padding-top: 80px; }
    .modal-content { background: #fff; margin: auto; padding: 20px; width: 90%; max-width: 500px; border-radius: 10px; }
    .close { float: right; font-size: 22px; cursor: pointer; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
    .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
    .submit-btn { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
    .secondary-btn { background-color: #007bff; color: white; padding: 6px 10px; border: none; border-radius: 5px; cursor: pointer; }
    .flash { padding:10px; border-radius:5px; margin:10px 0; }
    .flash-success { background:#e6ffed; border:1px solid #a6f3b0; color:#066a1a; }
    .flash-error { background:#ffe6e6; border:1px solid #f3a6a6; color:#6a0606; }
    .status-badge {
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  margin-left: 6px;
  text-transform: capitalize;
}
.status-badge.pending { background: #ffe4a3; color: #8a6d00; }
.status-badge.confirmed { background: #b3e5fc; color: #01579b; }
.status-badge.completed { background: #c8f7c5; color: #256029; }
.status-badge.canceled { background: #ffcdd2; color: #b71c1c; }

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
        <li><a href="admin-dashboard.php" class="menu-link active"><i class="fa fa-home"></i> Dashboard</a></li>
        <li><a href="employee-management.php" class="menu-link"><i class="fa-solid fa-users"></i> Employee Management</a></li>
        <li><a href="admin-attendance.php" class="menu-link"><i class="fa-solid fa-calendar"></i> Attendance</a></li>
        
        <li><a href="attendance-management.php" class="menu-link"><i class="fa-solid fa-clipboard-list"></i> Attendance Management</a></li>
        <li><a href="payroll-management.php" class="menu-link"><i class="fa-solid fa-money-bill"></i> Payroll Management</a></li>
        <li><a href="appointment-management.php" class="menu-link "><i class="fa-solid fa-calendar-check"></i> Appointment Management</a></li>

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
      <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>
    </span>
  </div>
</header>


    <h1>Admin Dashboard</h1>

    <!-- flash messages -->
    <?php if (!empty($_SESSION['success'])): ?>
      <div class="flash flash-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
      <div class="flash flash-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Stats Section -->
    <div class="dashboard">
      <div class="stats">
        <div class="stat-card"><div class="stat-info"><h3>Total Employees</h3><h2><?php echo $totalEmployees; ?></h2></div><div class="stat-icon"><i class="fas fa-users"></i></div></div>
        <div class="stat-card"><div class="stat-info"><h3>Present</h3><h2><?php echo $presentEmployees; ?></h2></div><div class="stat-icon"><i class="fas fa-user-check"></i></div></div>
        <div class="stat-card"><div class="stat-info"><h3>On Leave</h3><h2><?php echo $leaveEmployees; ?></h2></div><div class="stat-icon"><i class="fas fa-user-clock"></i></div></div>
      </div>
    </div>

    <!-- Appointment Section -->
    <section class="dashboard-section">
      <div class="dashboard-grid">

        <!-- Today's Appointments -->
        <div class="appointments-card">
          <div class="appointments-header">
            <h3>Today's Appointments</h3>
            <button class="add-btn" id="openModal">+ Add Appointment</button>
            <button class="secondary-btn" id="openServiceModal">Manage Services</button>
          </div>
          <?php if (!empty($appointments)) : ?>
  <ul class="appointment-list">
    <?php foreach ($appointments as $a): ?>
      <li>
        <div>
          <strong><?php echo htmlspecialchars($a['clientName']); ?></strong><br>
          <?php echo htmlspecialchars($a['processType']); ?> 
          (<?php echo htmlspecialchars($a['specialization']); ?>)<br>
          <small>
            <?php echo htmlspecialchars($a['FirstName']." ".$a['LastName']); ?> • 
            <?php echo !empty($a['Time']) ? date('g:i A', strtotime($a['Time'])) : ''; ?>
            <span class="status-badge <?php echo strtolower($a['status']); ?>">
              <?php echo htmlspecialchars($a['status']); ?>
            </span>
          </small>
        </div>
        <button 
          class="secondary-btn editStatusBtn" 
          data-id="<?php echo $a['appointmentID']; ?>" 
          data-status="<?php echo htmlspecialchars($a['status']); ?>"
        >
          Edit
        </button>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p style="text-align:center; color:gray;">No appointments today.</p>
<?php endif; ?>

        </div>

        <div class="announcements-card">
          <div class="announcements-header"><h3>Team Announcements</h3></div>
          <p style="text-align:center; color:gray;">No announcements yet.</p>
        </div>
      </div>
    </section>
  </main>
<!-- Edit Appointment Status Modal -->
<div id="editStatusModal" class="modal">
  <div class="modal-content">
    <span class="close" id="closeEditStatus">&times;</span>
    <h2>Update Appointment Status</h2>

    <form action="update_status.php" method="POST">
      <input type="hidden" name="appointmentID" id="editAppointmentID">

      <div class="form-group">
        <label>Status</label>
        <select name="status" id="editStatusSelect" required>
          <option value="Pending">Pending</option>
          <option value="Confirmed">Confirmed</option>
          <option value="Completed">Completed</option>
          <option value="Canceled">Canceled</option>
        </select>
      </div>

      <button type="submit" class="submit-btn">Update Status</button>
    </form>
  </div>
</div>

  <!-- Add Appointment Modal -->
  <div id="appointmentModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <span class="close" id="closeModal" title="Close">&times;</span>
      <h2>Add New Appointment</h2>
      <form action="insert_appointment.php" method="POST" id="appointmentForm">
        <div class="form-group">
          <label>Client Name</label>
          <input type="text" name="clientName" required>
        </div>
        <div class="form-group">
          <label>Process Type</label>
          <select name="processID" id="processTypeSelect" required>
            <option value="">-- Select Service --</option>
            <?php foreach ($services as $s): ?>
              <option 
                value="<?php echo htmlspecialchars($s['processID']); ?>"
                data-price="<?php echo htmlspecialchars($s['ProcessPrice']); ?>"
                data-specialization="<?php echo htmlspecialchars($s['specialization']); ?>">
                <?php echo htmlspecialchars($s['ProcessName']); ?> (₱<?php echo htmlspecialchars($s['ProcessPrice']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Price</label>
          <input type="text" id="servicePrice" readonly>
        </div>
        <div class="form-group">
          <label>Specialization</label>
          <input type="text" name="specialization" id="serviceSpecialization" readonly>
        </div>
        <div class="form-group">
          <label>Assign Employee</label>
          <select name="employeeID" required>
            <option value="">-- Select --</option>
            <?php foreach ($employees as $emp): ?>
              <option value="<?php echo $emp['EmployeeID']; ?>">
                <?php echo htmlspecialchars($emp['FirstName'].' '.$emp['LastName']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Date</label>
          <input type="date" name="appointmentDate" required value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="form-group">
          <label>Time</label>
          <input type="time" name="appointmentTime" required>
        </div>
        <div style="text-align:right;">
          <button type="submit" class="submit-btn">Save Appointment</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Manage Services Modal -->
  <div id="serviceModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <span class="close" id="closeServiceModal" title="Close">&times;</span>
      <h2>Manage Services</h2>
      <form action="insert_service.php" method="POST">
        <div class="form-group"><label>Process Name</label><input type="text" name="ProcessName" required></div>
        <div class="form-group"><label>Price</label><input type="number" step="0.01" name="ProcessPrice" required></div>
        <div class="form-group"><label>Specialization</label><input type="text" name="specialization" required></div>
        <div style="text-align:right;"><button type="submit" class="submit-btn">Add Service</button></div>
      </form>
      <hr>
      <h3>Existing Services</h3>
      <ul style="list-style:none; padding:0;">
        <?php foreach ($services as $s): ?>
          <li style="padding:5px 0; display:flex; justify-content:space-between;">
            <span><?php echo htmlspecialchars($s['ProcessName']); ?> (₱<?php echo htmlspecialchars($s['ProcessPrice']); ?>)</span>
            <a href="delete_service.php?id=<?php echo urlencode($s['processID']); ?>" style="color:red;" onclick="return confirm('Delete service?');">Delete</a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <script src="../js/main.js"></script>

  <script>
    // Elements (guard in case element not present)
    const modal = document.getElementById('appointmentModal');
    const openModalBtn = document.getElementById('openModal');
    const closeModalBtn = document.getElementById('closeModal');

    const serviceModal = document.getElementById('serviceModal');
    const openServiceModalBtn = document.getElementById('openServiceModal');
    const closeServiceModalBtn = document.getElementById('closeServiceModal');

    // Safely add event listeners only if elements exist
    if (openModalBtn && modal) openModalBtn.addEventListener('click', () => { modal.style.display = 'block'; modal.setAttribute('aria-hidden','false'); });
    if (closeModalBtn && modal) closeModalBtn.addEventListener('click', () => { modal.style.display = 'none'; modal.setAttribute('aria-hidden','true'); });

    if (openServiceModalBtn && serviceModal) openServiceModalBtn.addEventListener('click', () => { serviceModal.style.display = 'block'; serviceModal.setAttribute('aria-hidden','false'); });
    if (closeServiceModalBtn && serviceModal) closeServiceModalBtn.addEventListener('click', () => { serviceModal.style.display = 'none'; serviceModal.setAttribute('aria-hidden','true'); });

    window.addEventListener('click', (e) => {
      if (e.target === modal) { modal.style.display = 'none'; modal.setAttribute('aria-hidden','true'); }
      if (e.target === serviceModal) { serviceModal.style.display = 'none'; serviceModal.setAttribute('aria-hidden','true'); }
    });

    // Auto fill price & specialization when selecting service
    const processSelect = document.getElementById('processTypeSelect');
    const servicePrice = document.getElementById('servicePrice');
    const serviceSpecialization = document.getElementById('serviceSpecialization');

    if (processSelect) {
      processSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (!selected) return;
        const price = selected.getAttribute('data-price') || '';
        const specialization = selected.getAttribute('data-specialization') || '';

        // show numeric price without currency in hidden field if you prefer,
        // but for UX we display with ₱
        servicePrice.value = price !== '' ? `₱${price}` : '';
        serviceSpecialization.value = specialization;
      });
    }
    // ============================
// Edit Appointment Status Modal
// ============================
const editStatusModal = document.getElementById('editStatusModal');
const closeEditStatus = document.getElementById('closeEditStatus');

document.querySelectorAll('.editStatusBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.getAttribute('data-id');
    const status = btn.getAttribute('data-status');

    document.getElementById('editAppointmentID').value = id;
    document.getElementById('editStatusSelect').value = status;

    editStatusModal.style.display = 'block';
  });
});

closeEditStatus.onclick = () => editStatusModal.style.display = 'none';

window.onclick = (e) => {
  if (e.target == editStatusModal) editStatusModal.style.display = 'none';
};

  </script>
</body>
</html>
