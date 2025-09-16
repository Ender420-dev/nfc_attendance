<?php
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payroll Management</title>
  <link rel="stylesheet" href="../css/admin.css?v=1.2" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
  <style>
    .table {
      width: 100%;
      margin-top: 20px;
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
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

    .actions button {
      margin-right: 5px;
      padding: 6px 10px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .btn-view {
      background: #3498db;
      color: white;
    }

    .btn-edit {
      background: #f39c12;
      color: white;
    }

    .btn-paid {
      background: #27ae60;
      color: white;
    }

    .btn-payslip {
      background: #9b59b6;
      color: white;
    }

    .btn-generate {
      background: #2ecc71;
      color: white;
      padding: 10px 15px;
      border-radius: 6px;
      text-decoration: none;
    }
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
        <li><a href="employee-management.php" class="menu-link"><i class="fa-solid fa-users"></i> Employee
            Management</a></li>
        <li><a href="admin-attendance.php" class="menu-link"><i class="fa-solid fa-calendar"></i> Attendance</a></li>
        <li><a href="attendance-management.php" class="menu-link"><i class="fa-solid fa-clipboard-list"></i> Attendance
            Management</a></li>
        <li><a href="payroll-management.php" class="menu-link active"><i class="fa-solid fa-money-bill"></i> Payroll
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
        <input type="text" id="searchInput" class="search" placeholder="Search payroll records..." />
        <button class="btn-generate" onclick="openPayrollModal()">
          <i class="fa-solid fa-plus"></i> Generate Payroll
        </button>

      </div>
      <!-- Payroll Generate Modal -->
      <div id="payrollModal" class="modal" style="display:none;">
        <div class="modal-content">
          <span class="close-btn" onclick="closePayrollModal()">&times;</span>
          <h2>Generate Payroll</h2>
          <form method="POST" action="payroll-generate-process.php">
            <label>Start Date:</label>
            <input type="date" name="startDate" required>

            <label>End Date:</label>
            <input type="date" name="endDate" required>

            <button type="submit" class="btn-generate">Generate</button>
          </form>
        </div>
      </div>

      <!-- Payroll Table -->
      <div class="table">
        <h2>Payroll Records</h2>
        <table id="payrollTable">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Pay Period</th>
              <th>Gross Pay</th>
              <th>Deductions</th>
              <th>Net Pay</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT p.PayrollID, e.FirstName, e.LastName, p.PayPeriod, p.GrossPay, p.Deduction, p.NetPay, p.Remarks 
                    FROM payroll p
                    JOIN employees e ON p.EmployeeID = e.EmployeeID
                    ORDER BY p.PayPeriod DESC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['FirstName']) . " " . htmlspecialchars($row['LastName']) . "</td>
                        <td>" . htmlspecialchars($row['PayPeriod']) . "</td>
                        <td>₱" . number_format($row['GrossPay'], 2) . "</td>
                        <td>₱" . number_format($row['Deduction'], 2) . "</td>
                        <td><strong>₱" . number_format($row['NetPay'], 2) . "</strong></td>
                        <td>" . htmlspecialchars($row['Remarks']) . "</td>
                       <td class='actions'>
  <button class='btn-view' data-id='" . $row['PayrollID'] . "'>👁 View</button>
  <button class='btn-edit' data-id='" . $row['PayrollID'] . "'>✏ Edit</button>
  <a href='payroll-mark-paid.php?id=" . $row['PayrollID'] . "' class='btn-paid' onclick='return confirm(\"Mark as paid?\")'>💰 Mark Paid</a>
  <a href='payroll-payslip.php?id=" . $row['PayrollID'] . "' target='_blank' class='btn-payslip'>📄 Payslip</a>
</td>


                      </tr>";
              }
            } else {
              echo "<tr><td colspan='7'>No payroll records found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
        <!-- VIEW MODAL -->
<div id="viewModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" id="closeView">&times;</span>
    <h2>Payroll Details</h2>
    <div id="viewContent">
      <!-- Payroll info loads here via AJAX -->
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" id="closeEdit">&times;</span>
    <h2>Edit Payroll</h2>
    <form id="editForm">
      <input type="hidden" name="PayrollID" id="editPayrollID">
      <label>Gross Pay:</label>
      <input type="number" step="0.01" name="GrossPay" id="editGrossPay"><br>
      <label>Deductions:</label>
      <input type="number" step="0.01" name="Deduction" id="editDeduction"><br>
      <label>Remarks:</label>
      <input type="text" name="Remarks" id="editRemarks"><br>
      <button type="submit">💾 Save</button>
    </form>
  </div>
</div>

      </div>
    </section>
  </main>

  <script src="../js/main.js"></script>
  <script>
    function openPayrollModal() {
      document.getElementById("payrollModal").style.display = "block";
    }
    function closePayrollModal() {
      document.getElementById("payrollModal").style.display = "none";
    }

    // Search payroll records
    document.getElementById("searchInput").addEventListener("keyup", function () {
      let filter = this.value.toLowerCase();
      document.querySelectorAll("#payrollTable tbody tr").forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
      });
    });
document.addEventListener("DOMContentLoaded", function() {
  // Open VIEW modal
  document.querySelectorAll(".btn-view").forEach(btn => {
    btn.addEventListener("click", function() {
      let id = this.dataset.id;
      fetch("payroll-get.php?id=" + id)
        .then(res => res.json())
        .then(data => {
          let html = `
            <p><strong>Name:</strong> ${data.FirstName} ${data.LastName}</p>
            <p><strong>Position:</strong> ${data.Position}</p>
            <p><strong>Pay Period:</strong> ${data.PayPeriod}</p>
            <p><strong>Gross Pay:</strong> ₱${parseFloat(data.GrossPay).toFixed(2)}</p>
            <p><strong>Deductions:</strong> ₱${parseFloat(data.Deduction).toFixed(2)}</p>
            <p><strong>Net Pay:</strong> ₱${parseFloat(data.NetPay).toFixed(2)}</p>
            <p><strong>Status:</strong> ${data.Remarks}</p>
          `;
          document.getElementById("viewContent").innerHTML = html;
          document.getElementById("viewModal").style.display = "block";
        });
    });
  });

  // Open EDIT modal
  document.querySelectorAll(".btn-edit").forEach(btn => {
    btn.addEventListener("click", function() {
      let id = this.dataset.id;
      fetch("payroll-get.php?id=" + id)
        .then(res => res.json())
        .then(data => {
          document.getElementById("editPayrollID").value = data.PayrollID;
          document.getElementById("editGrossPay").value = data.GrossPay;
          document.getElementById("editDeduction").value = data.Deduction;
          document.getElementById("editRemarks").value = data.Remarks;
          document.getElementById("editModal").style.display = "block";
        });
    });
  });

  // Submit EDIT form
  document.getElementById("editForm").addEventListener("submit", function(e) {
    e.preventDefault();
    fetch("payroll-update.php", {
      method: "POST",
      body: new FormData(this)
    })
    .then(res => res.text())
    .then(result => {
      if (result.trim() === "success") {
        alert("Payroll updated!");
        location.reload();
      } else {
        alert("Error updating payroll.");
      }
    });
  });

  // Close modals
  document.getElementById("closeView").onclick = () => document.getElementById("viewModal").style.display = "none";
  document.getElementById("closeEdit").onclick = () => document.getElementById("editModal").style.display = "none";
});

  </script>
</body>

</html>