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

// 🧭 Get date filter from query string
$filterDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// 🧩 Query attendance with join on employees
$sql = "
  SELECT 
    a.AttendanceID, 
    e.FirstName, 
    e.LastName, 
    a.ScanType, 
    a.IsLate, 
    a.WorkDate, 
    a.ScanTime, 
    a.Remarks
  FROM attendance a
  LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID
  WHERE DATE(a.WorkDate) = ?
  ORDER BY a.ScanTime DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $filterDate);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Attendance Management</title>
  <style>
    .green {
      color: green;
      font-weight: bold;
    }

    .yellow {
      color: goldenrod;
      font-weight: bold;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th,
    td {
      padding: 8px 10px;
      border: 1px solid #ccc;
      text-align: center;
    }

    th {
      background-color: #f5f5f5;
    }

    .filter-box {
      margin-bottom: 15px;
    }

    .filter-box input[type="date"] {
      padding: 5px 8px;
    }

    .filter-box button {
      padding: 6px 10px;
    }
  </style>
  <link rel="stylesheet" href="../css/admin.css?v=1.4" />
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
        <input type="text" id="searchInput" class="search" placeholder="Search employees..." />

        <!-- Add Manual Attendance Button -->
        <div style="margin-left: 15px; display: inline-block;">
          <button class="btn-save" onclick="openModal('addAttendanceModal')">
            + Add Manual Attendance
          </button>
        </div>
      </div>
      <!-- Manual Attendance Modal -->
      <div id="addAttendanceModal" class="modal">
        <div class="modal-content">
          <span class="close-btn" onclick="closeModal('addAttendanceModal')">&times;</span>
          <h2>Add Manual Attendance</h2>
          <form id="addAttendanceForm">
            <label for="employeeId">Employee:</label>
            <label for="employeeId">Employee:</label>
            <select name="employeeId" id="employeeId" required>
              <option value="">-- Select Employee --</option>
            </select>


            <label for="scanType">Scan Type:</label>
            <select name="scanType" id="scanType" required>
              <option value="IN">Check In</option>
              <option value="OUT">Check Out</option>
            </select>

            <label for="scanTime">Scan Date & Time:</label>
            <input type="datetime-local" id="scanTime" name="scanTime" value="<?= date('Y-m-d\TH:i'); ?>" required>

            <label for="remarks">Remarks:</label>
            <input type="text" id="remarks" name="remarks" placeholder="Optional">

            <div class="modal-actions">
              <button type="submit" class="btn-save">💾 Add Attendance</button>
              <button type="button" class="btn-cancel" onclick="closeModal('addAttendanceModal')">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- 🔍 Filter by date -->
      <div class="filter-box">
        <form method="GET" action="">
          <label for="date">Filter by Date:</label>
          <input type="date" id="date" name="date" value="<?= htmlspecialchars($filterDate); ?>"
            max="<?= date('Y-m-d'); ?>" required>
          <button type="submit">Filter</button>
          <a href="admin-attendance.php"><button type="button">Show All</button></a>
        </form>
      </div>
      <div class="table">
        <h2>Employee Attendance Records</h2>
        <table id="Table" class="spread-table">
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Scan Type</th>
              <th>Work Date</th>
              <th>Scan Time</th>
              <th>Status</th>
              <th>Remarks</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="attendanceTableBody">
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                $statusClass = !empty($row['IsLate']) ? 'yellow' : 'green';
                $statusText = !empty($row['IsLate']) ? 'Late' : 'On Time';
                ?>
                <tr data-id="<?= $row['AttendanceID']; ?>">
                  <td><?= htmlspecialchars($row['FirstName'] . " " . $row['LastName']); ?></td>
                  <td><?= htmlspecialchars($row['ScanType']); ?></td>
                  <td><?= htmlspecialchars($row['WorkDate']); ?></td>
                  <td><?= htmlspecialchars($row['ScanTime']); ?></td>
                  <td class="<?= $statusClass; ?>"><?= $statusText; ?></td>
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
                <td colspan="7" style="text-align:center;color:gray;">No attendance records found for this date.</td>
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
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    function closeModal(id) {
      document.getElementById(id).style.display = "none";
    }
// -------------------- MODAL --------------------
function openModal(id) {
  document.getElementById(id).style.display = 'flex';

  if (id === 'addAttendanceModal') {
    // Fetch employees dynamically
    fetch('fetch-employees.php')
      .then(res => res.json())
      .then(data => {
        const select = document.getElementById('employeeId');
        select.innerHTML = '<option value="">-- Select Employee --</option>'; // reset
        data.forEach(emp => {
          const opt = document.createElement('option');
          opt.value = emp.EmployeeID;
          opt.textContent = emp.FirstName + ' ' + emp.LastName;
          select.appendChild(opt);
        });
      })
      .catch(err => console.error(err));
  }
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}

// -------------------- FORM SUBMISSION --------------------
document.getElementById('addAttendanceForm').addEventListener('submit', function(e){
  e.preventDefault(); // prevent default form submission

  const formData = new FormData(this);

  fetch('manual_attendance_insert.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if(data.success) {
      alert('Attendance added successfully!');

      // Optionally, add row to table without reload
      const tbody = document.getElementById('attendanceTableBody');
      const newRow = document.createElement('tr');

      const statusClass = data.isLate ? 'yellow' : 'green';
      const statusText = data.isLate ? 'Late' : 'On Time';

      newRow.innerHTML = `
        <td>${data.FirstName} ${data.LastName}</td>
        <td>${data.ScanType}</td>
        <td>${data.ScanTime.split('T')[0]}</td>
        <td>${data.ScanTime.split('T')[1]}</td>
        <td class="${statusClass}">${statusText}</td>
        <td>${data.Remarks || '—'}</td>
        <td>
          <button class="view">View</button>
          <button class="edit">Edit</button>
          <button class="delete">Remove</button>
        </td>
      `;
      tbody.prepend(newRow);

      closeModal('addAttendanceModal');
      this.reset();
    } else {
      alert('Error: ' + data.error);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Something went wrong!');
  });
});


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
      document.getElementById('editScanTime').value = scanTime.toISOString().slice(0, 16);

      // Remarks (cell 4)
      document.getElementById('editRemarks').value = row.cells[4].textContent;

      document.getElementById('editAttendanceModal').style.display = 'flex';
    }

    // Handle Edit Form submission
    document.getElementById('editAttendanceForm').addEventListener('submit', function (e) {
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
      if (confirm("Are you sure you want to delete this attendance record?")) {
        fetch('delete-attendance.php', {
          method: 'POST',
          body: new URLSearchParams({ AttendanceID: attendanceID })
        })
          .then(res => res.text())
          .then(data => {
            alert(data);
            // Remove row from table without reloading
            const row = document.querySelector(`tr[data-id='${attendanceID}']`);
            if (row) row.remove();
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