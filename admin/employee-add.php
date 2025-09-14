<?php
session_start();
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = $conn->real_escape_string($_POST['FirstName'] ?? '');
    $lastName = $conn->real_escape_string($_POST['LastName'] ?? '');
    $position = $conn->real_escape_string($_POST['Position'] ?? '');
    $contactInfo = $conn->real_escape_string($_POST['ContactInfo'] ?? '');
    $status = $conn->real_escape_string($_POST['Status'] ?? 'Active');
    $dateHired = $conn->real_escape_string($_POST['DateHired'] ?? '');
    $nfcCardID = $_POST['NfcCardID'] ?? null; // raw value

    if (empty($firstName) || empty($lastName) || empty($position) || empty($dateHired)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: employee-management.php");
        exit;
    }

    // Check if NFC Card exists in nfc_card table
    if (!empty($nfcCardID)) {
        $checkSql = "SELECT * FROM nfc_card WHERE NfcCardID = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $nfcCardID);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) {
            $_SESSION['error'] = "The NFC Card ID does not exist in the system.";
            $checkStmt->close();
            header("Location: employee-management.php");
            exit;
        }
        $checkStmt->close();
    } else {
        $nfcCardID = NULL; // allows NULL for foreign key
    }

    $sql = "INSERT INTO employees (NfcCardID, FirstName, LastName, Position, ContactInfo, Status, DateHired) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $nfcCardID, $firstName, $lastName, $position, $contactInfo, $status, $dateHired);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Employee added successfully.";
    } else {
        $_SESSION['error'] = "Error adding employee: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    header("Location: employee-management.php");
    exit;
} else {
    header("Location: employee-management.php");
    exit;
}
?>
