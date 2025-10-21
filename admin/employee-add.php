<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName   = $_POST['FirstName'];
    $lastName    = $_POST['LastName'];
    $position    = $_POST['Position'];
    $contactInfo = $_POST['ContactInfo'];
    $status      = $_POST['Status'];
    $dateHired   = $_POST['DateHired'];
    $nfcCardID   = $_POST['NfcCardID'] ?: NULL; // optional card

    $username    = $_POST['Username'];
    $password    = $_POST['Password']; // ⚠️ plain text
    $role        = $_POST['Role'];
    $email        = $_POST['email'];


    // Insert into employees table
    $stmtEmp = $conn->prepare("
        INSERT INTO employees (FirstName, LastName, Position, ContactInfo, Status, DateHired, NfcCardID) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtEmp->bind_param("sssssss", $firstName, $lastName, $position, $contactInfo, $status, $dateHired, $nfcCardID);
    $stmtEmp->execute();

    // Get EmployeeID
    $employeeID = $conn->insert_id;

    // Insert login info into users table
    $stmtUser = $conn->prepare("
        INSERT INTO users (EmployeeID, Username, Password, Role, email) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtUser->bind_param("isss", $employeeID, $username, $password, $role);
    $stmtUser->execute();

    $stmtEmp->close();
    $stmtUser->close();

    header("Location: employee-management.php");
    exit;
}
?>
