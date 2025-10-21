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
// Fetch employees with NFC card if available
$sql = "SELECT 
    e.EmployeeID, 
    e.FirstName, 
    e.LastName, 
    e.Position, 
    e.ContactInfo, 
    e.Status, 
    e.DateHired,
    c.CardUID
FROM employees e
LEFT JOIN nfc_card c
    ON e.NfcCardID = c.NfcCardID";

$result = $conn->query($sql);
$employees = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // If no CardUID, set a default value
    if (empty($row['CardUID']))
      $row['CardUID'] = 'No Card';
    $employees[] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employee Management</title>
  <link rel="stylesheet" href="../css/admin.css?v=1.4" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/logo.png" />
</head>
<style>
 /* Hide modals by default */
.modal {
  display: none;  /* hide */
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6); /* dark overlay */
  justify-content: center;
  align-items: center;
}

/* When modal is active, show it */
.modal.active {
  display: flex;
}

.modal-content {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
}

/* Close button */
.close-btn {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 22px;
  font-weight: bold;
  color: #333;
  text-decoration: none;
  cursor: pointer;
}

.close-btn:hover {
  color: #f44336;
}

/* Form labels and inputs */
.modal-content label {
  display: block;
  margin: 10px 0 5px;
  font-weight: 500;
}

.modal-content input,
.modal-content select {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 14px;
}

  /* Modal Actions Container */
  .modal-actions {
    display: flex;
    justify-content: flex-end;
    /* Align buttons to the right */
    gap: 10px;
    /* Space between buttons */
    margin-top: 20px;
  }

  /* Save Button */
  .modal-actions .btn-save {
    background-color: #4CAF50;
    /* Green */
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .modal-actions .btn-save:hover {
    background-color: #45a049;
    /* Darker green on hover */
  }

  /* Cancel Button */
  .modal-actions .btn-cancel {
    background-color: #f44336;
    /* Red */
    color: white;
    border: none;
    padding: 10px 20px;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .modal-actions .btn-cancel:hover {
    background-color: #d32f2f;
    /* Darker red on hover */
  }
</style>
<style>
  /* Add New Card Button */
  .modal-actions button[type="button"] {
    background-color: #2196F3;
    /* Blue */
    color: white;
    border: none;
    padding: 8px 16px;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-left: 5px;
  }

  .modal-actions button[type="button"]:hover {
    background-color: #1976d2;
    /* Darker blue on hover */
  }

  /* Checkbox styling */
  #addCardForm input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #4CAF50;
    /* Green accent */
    margin-right: 5px;
  }

  /* Label for checkbox */
  #addCardForm label {
    font-weight: 500;
    margin-right: 10px;
  }

  /* Add some spacing in the Add NFC Card modal */
  #addCardModal .modal-content {
    padding: 20px;
  }

  #addCardForm input[type="text"] {
    width: 80%;
    padding: 6px 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  /* Save Card Button */
  #addCardForm button {
    background-color: #4CAF50;
    /* Green */
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  #addCardForm button:hover {
    background-color: #45a049;
    /* Darker green */
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
        <li>
          <a href="admin-dashboard.php" class="menu-link ">
            <i class="fa fa-home"></i> Dashboard
          </a>
        </li>
        <li>
          <a href="employee-management.php" class="menu-link active">
            <i class="fa-solid fa-users"></i> Employee Management
          </a>
        </li>
        <li>
          <a href="admin-attendance.php" class="menu-link ">
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

    <section class="main-section">
      <div class="search-filter-container">
        <input type="text" id="searchInput" class="search" placeholder="Search employees..." />
        <a href="#addModal" class="add">+ Add Employee</a>
      </div>


      <!-- Employee List -->
      <div class="table">
        <h2>Employee List</h2>
        <table id="employeeTable" class="spread-table">
          <thead>
            <tr>
              <th>Employee ID</th>
              <th>Card UID</th>

              <th>First Name</th>
              <th>Last Name</th>
              <th>Position</th>
              <th>Contact Info</th>
              <th>Status</th>
              <th>Date Hired</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($employees as $emp): ?>
              <tr data-id="<?= $emp['EmployeeID'] ?>">
                <td><?= htmlspecialchars($emp['EmployeeID']) ?></td>
                <td><?= htmlspecialchars($emp['CardUID']) ?></td>
                <td><?= htmlspecialchars($emp['FirstName']) ?></td>
                <td><?= htmlspecialchars($emp['LastName']) ?></td>
                <td><?= htmlspecialchars($emp['Position']) ?></td>
                <td><?= htmlspecialchars($emp['ContactInfo']) ?></td>
                 <td><?= htmlspecialchars($emp['ContactInfo']) ?></td>
                <td><?= htmlspecialchars($emp['Status']) ?></td>
                <td><?= htmlspecialchars($emp['DateHired']) ?></td>
                <td>
                  <button class="view" onclick="openViewModal(this)">View</button>
                  <button class="edit" onclick="openEditModal(<?= $emp['EmployeeID'] ?>)">Edit</button>


               
                </td>
              </tr>
            </tbody>

            </tr>
            



            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal">
              <div class="modal-content">
                <a href="#" class="close-btn" onclick="closeModal('deleteModal')">&times;</a>
                <h2>Confirm Delete</h2>
                <p>Are you sure you want to remove this employee?</p>
                <form id="deleteForm" method="POST" action="employee-delete.php">
                  <input type="hidden" name="EmployeeID" id="deleteEmployeeID" />
                  <button type="submit" class="delete">Yes, Delete</button>
                  <button type="button" style="color: black;" class="cancel" onclick="closeModal('deleteModal')">Cancel</button>
                </form>
              </div>
            </div>

          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

<!-- View Employee Modal -->
<div id="viewModal" class="modal">
              <div class="modal-content">
                <span class="close-btn" onclick="closeModal('viewModal')">&times;</span>
                <h2 class="modal-title">Employee Details</h2>

                <div class="modal-body">
                  <div class="details-grid">
                    <div class="detail-item">
                      <label>Employee ID</label>
                      <span id="viewEmployeeID"></span>
                    </div>
                    <div class="detail-item">
                      <label>NFC Card ID</label>
                      <span id="viewNfcCardID"></span>
                    </div>
                    <div class="detail-item">
                      <label>Position ID</label>
                      <span id="viewPositionID"></span>
                    </div>
                    <div class="detail-item">
                      <label>First Name</label>
                      <span id="viewFirstName"></span>
                    </div>
                    <div class="detail-item">
                      <label>Last Name</label>
                      <span id="viewLastName"></span>
                    </div>
                    <div class="detail-item">
                      <label>Position</label>
                      <span id="viewPosition"></span>
                    </div>
                    <div class="detail-item">
                      <label>Contact Info</label>
                      <span id="viewContactInfo"></span>
                    </div>
                    <div class="detail-item">
                      <label>Email</label>
                      <span id="viewEmail"></span>
                    </div>
                    <div class="detail-item">
                      <label>Status</label>
                      <span id="viewStatus"></span>
                    </div>
                    <div class="detail-item">
                      <label>Date Hired</label>
                      <span id="viewDateHired"></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>


            <!-- Edit Employee Modal -->
            <div id="editModal" class="modal">
              <div class="modal-content">
                <a href="#" class="close-btn" onclick="closeModal('editModal')">&times;</a>
                <h2>Edit Employee</h2>
                <form id="editForm" method="POST" action="employee-edit.php">

                  <!-- Hidden EmployeeID -->
                  <input type="hidden" name="EmployeeID" id="editEmployeeID">

                  <label>First Name:</label>
                  <input type="text" name="FirstName" id="editFirstName" required />

                  <label>Last Name:</label>
                  <input type="text" name="LastName" id="editLastName" required />

                  <label>Position:</label>
                  <input type="text" name="Position" id="editPosition" required />

                  <label>NFC Card:</label>
                  <select name="NfcCardID" id="editNfcCardID">
                    <option value="">No Card</option>
                    <?php
                    $cardsSql = "SELECT NfcCardID, CardUID FROM nfc_card WHERE IsActive = 1";
                    $cardsResult = $conn->query($cardsSql);
                    if ($cardsResult && $cardsResult->num_rows > 0) {
                      while ($card = $cardsResult->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($card['NfcCardID']) . '">'
                          . htmlspecialchars($card['CardUID']) . '</option>';
                      }
                    }
                    ?>
                  </select>

                  <label>Status:</label>
                  <select name="Status" id="editStatus" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>

                  <label>Contact Info:</label>
                  <input type="text" name="ContactInfo" id="editContactInfo" />

                  <label>Date Hired:</label>
                  <input type="date" name="DateHired" id="editDateHired" required />
                  <label>Email:</label>
                  <input type="email" name="email" id="email" required />

                  <label>Username:</label>
                  <input type="text" name="Username" id="editUsername" required />

                  <label>Password:</label>
                  <input type="text" name="Password" id="editPassword" required />

                  <label>Role:</label>
                  <select name="Role" id="editRole" required>
                    <option value="Admin">Admin</option>
                    <option value="Owner">Owner</option>
                    <option value="Employee">Employee</option>
                  </select>

                  <div class="modal-actions">
                    <button type="submit" class="btn-save">💾 Update Employee</button>
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                    <div class="form-group">
                      <button type="button" onclick="openAddCardModal()">+ Add New Card</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>


      <!-- Export Buttons -->
      <!--  -->
    </section>
  </main>

  <!-- Add Employee Modal -->
  <!-- Add Employee Modal -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <a href="#" class="close-btn">&times;</a>
      <h2>Add Employee</h2>
      <form id="addForm" method="POST" action="employee-add.php">

        <label>First Name:</label>
        <input type="text" name="FirstName" required />

        <label>Last Name:</label>
        <input type="text" name="LastName" required />

        <label>Position:</label>
        <input type="text" name="Position" required />

        <label>NFC Card:</label>
        <select name="NfcCardID">
          <option value="">No Card</option>
          <?php
          // Fetch available NFC cards
          $cardsSql = "SELECT NfcCardID, CardUID FROM nfc_card WHERE IsActive = 1";
          $cardsResult = $conn->query($cardsSql);
          if ($cardsResult && $cardsResult->num_rows > 0) {
            while ($card = $cardsResult->fetch_assoc()) {
              echo '<option value="' . htmlspecialchars($card['NfcCardID']) . '">'
                . htmlspecialchars($card['CardUID']) . '</option>';
            }
          }
          ?>
        </select>

        <label>Status:</label>
        <select name="Status" required>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>

        <label>Contact Info:</label>
        <input type="text" name="ContactInfo" />

        <label>Date Hired:</label>
        <input type="date" name="DateHired" required />
        <label>Username:</label>
        <input type="text" name="Username" required />

        <label>Password:</label>
        <input type="text" name="Password" required />

        <label>Role:</label>
        <select name="Role" required>
          <option value="Admin">Admin</option>
          <option value="Owner">Owner</option>
          <option value="Employee" selected>Employee</option>
        </select>


        <div class="modal-actions">
          <button type="submit" class="btn-save">💾 Add Employee</button>
         
          <div class="form-group">

            <button type="button" onclick="openAddCardModal()">+ Add New Card</button>
          </div>

        </div>

      </form>
      <div id="addCardModal" class="modal">
        <div class="modal-content">
          <span class="close-btn" onclick="closeModal('addCardModal')">&times;</span>
          <h2>Add NFC Card</h2>
          <form id="addCardForm">
            <label>Card UID:</label>
            <input type="text" id="newCardUID" required><br><br>

            <label>Active:</label>
            <input type="checkbox" id="newIsActive" checked><br><br>

            <button type="button" onclick="saveNewCard()">Save Card</button>
          </form>
        </div>
      </div>


    </div>
  </div>


  <script src="../js/main.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

  <script>
  // 🔹 Open modal (unified)
  function openModal(id) {
    document.getElementById(id).classList.add("active");
  }

  // 🔹 Close modal (unified)
  function closeModal(id) {
    document.getElementById(id).classList.remove("active");
  }

  // 🔹 Add Card Modal
  function openAddCardModal() {
    openModal('addCardModal');
  }

  function saveNewCard() {
    let CardUID = document.getElementById('newCardUID').value;
    let IsActive = document.getElementById('newIsActive').checked ? 1 : 0;

    // Automatically set IssueDate = today, ExpiryDate = today + 1 year
    let today = new Date();
    let IssueDate = today.toISOString().split('T')[0];

    let nextYear = new Date();
    nextYear.setFullYear(today.getFullYear() + 1);
    let ExpiryDate = nextYear.toISOString().split('T')[0];

    fetch('nfc-card-add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ CardUID, IssueDate, ExpiryDate, IsActive })
    })
      .then(res => res.text())
      .then(data => {
        alert('Card added successfully!');
        document.getElementById('addNfcCardID').value = CardUID;
        closeModal('addCardModal');
      })
      .catch(err => console.error(err));
  }

  // 🔹 View Employee Modal
  function openViewModal(button) {
    const row = button.closest('tr');
    document.getElementById("viewEmployeeID").innerText = row.children[0].innerText;
    document.getElementById("viewNfcCardID").innerText = row.children[1].innerText;
    document.getElementById("viewFirstName").innerText = row.children[2].innerText;
    document.getElementById("viewLastName").innerText = row.children[3].innerText;
    document.getElementById("viewPosition").innerText = row.children[4].innerText;
    document.getElementById("viewContactInfo").innerText = row.children[5].innerText;
    document.getElementById("viewStatus").innerText = row.children[6].innerText;
    document.getElementById("viewDateHired").innerText = row.children[7].innerText;

    openModal("viewModal");
  }

  // 🔹 Edit Employee Modal
  function openEditModal(employeeId) {
    fetch("get_employee_details.php?id=" + employeeId)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }

        // Fill form fields
        document.getElementById("editEmployeeID").value = data.EmployeeID;
        document.getElementById("editFirstName").value = data.FirstName;
        document.getElementById("editLastName").value = data.LastName;
        document.getElementById("editPosition").value = data.Position;
        document.getElementById("editStatus").value = data.Status;
        document.getElementById("editContactInfo").value = data.ContactInfo;
        document.getElementById("editDateHired").value = data.DateHired;

        document.getElementById("editUsername").value = data.Username || "";
        document.getElementById("editPassword").value = data.Password || "";
        document.getElementById("editRole").value = data.Role || "Employee";
        document.getElementById("email").value = data.email || "";

        // Select NFC card if assigned
        if (data.NfcCardID) {
          document.getElementById("editNfcCardID").value = data.NfcCardID;
        } else {
          document.getElementById("editNfcCardID").value = "";
        }

        openModal("editModal");
      })
      .catch(err => console.error(err));
  }

  // 🔹 Delete Employee Modal
  function openDeleteModal(button) {
    const row = button.closest('tr');
    document.getElementById("deleteEmployeeID").value = row.children[0].innerText;
    openModal("deleteModal");
  }

  // 🔹 Close modal if clicked outside
  window.onclick = function (event) {
    document.querySelectorAll(".modal").forEach(modal => {
      if (event.target === modal) {
        modal.classList.remove("active");
      }
    });
  };

  // 🔹 Search
  document.getElementById("searchInput").addEventListener("keyup", function () {
    let filter = this.value.toLowerCase();
    document.querySelectorAll("#employeeTable tbody tr").forEach(row => {
      row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
  });

  // 🔹 Export CSV
  document.getElementById("exportCSV").addEventListener("click", () => {
    let rows = document.querySelectorAll("table tr");
    let csv = "";
    rows.forEach(row => {
      let cols = row.querySelectorAll("td, th");
      let rowData = Array.from(cols).map(col => `"${col.innerText}"`).join(",");
      csv += rowData + "\n";
    });
    let blob = new Blob([csv], { type: "text/csv" });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "employees.csv";
    link.click();
  });

  // 🔹 Export Excel
  document.getElementById("exportExcel").addEventListener("click", () => {
    const table = document.getElementById("employeeTable");
    const wb = XLSX.utils.table_to_book(table, { sheet: "Employees" });
    XLSX.writeFile(wb, "employees.xlsx");
  });

  // 🔹 Export PDF
  document.getElementById("exportPDF").addEventListener("click", () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Employee List", 14, 16);
    doc.autoTable({ html: "#employeeTable", startY: 20 });
    doc.save("employees.pdf");
  });
</script>

</body>

</html>