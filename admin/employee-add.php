<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = trim($_POST['FirstName']);
    $lastName    = trim($_POST['LastName']);
    $position    = trim($_POST['Position']);
    $contactInfo = trim($_POST['ContactInfo']);
    $status      = $_POST['Status'];
    $dateHired   = $_POST['DateHired'];
    $nfcCardID   = $_POST['NfcCardID'] ?: NULL;

    $username    = trim($_POST['Username']);
    $password    = $_POST['Password'];
    $role        = $_POST['Role'];
    $email       = $_POST['email'] ?? '';

    // ⚠️ Check if Date Hired is not in the future
    if (strtotime($dateHired) > strtotime(date('Y-m-d'))) {
        echo "<script>alert('❌ Date Hired cannot be a future date!'); window.history.back();</script>";
        exit;
    }

    // 🔍 Check for duplicate name
    $stmtCheck = $conn->prepare("
        SELECT EmployeeID FROM employees 
        WHERE LOWER(FirstName) = LOWER(?) AND LOWER(LastName) = LOWER(?)
    ");
    $stmtCheck->bind_param("ss", $firstName, $lastName);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        echo "<script>alert('❌ Employee with the same name already exists!'); window.history.back();</script>";
        exit;
    }

    // ✅ Insert into employees
    $stmtEmp = $conn->prepare("
        INSERT INTO employees (FirstName, LastName, Position, ContactInfo, Status, DateHired, NfcCardID) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtEmp->bind_param("sssssss", $firstName, $lastName, $position, $contactInfo, $status, $dateHired, $nfcCardID);
    $stmtEmp->execute();

    $employeeID = $conn->insert_id;

    // ✅ Insert login info
    $stmtUser = $conn->prepare("
        INSERT INTO users (EmployeeID, Username, Password, Role, email) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtUser->bind_param("issss", $employeeID, $username, $password, $role, $email);
    $stmtUser->execute();

    $stmtEmp->close();
    $stmtUser->close();

    echo "<script>alert('✅ Employee added successfully!'); window.location.href='employee-management.php';</script>";
    exit;
}
?>
