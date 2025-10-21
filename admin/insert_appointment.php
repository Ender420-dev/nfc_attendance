<?php
session_start();
include '../db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $clientName       = trim($_POST['clientName']);
    $employeeID       = intval($_POST['employeeID']);
    $processID        = intval($_POST['processID']);
    $specialization   = trim($_POST['specialization']);
    $appointmentDate  = $_POST['appointmentDate'];
    $appointmentTime  = $_POST['appointmentTime'];

    // ✅ Validate required fields
    if (empty($clientName) || empty($employeeID) || empty($processID) || empty($appointmentDate) || empty($appointmentTime)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: admin-dashboard.php");
        exit;
    }

    // ✅ Verify that selected process exists
    $processQuery = $conn->prepare("SELECT ProcessName, ProcessPrice FROM services WHERE ProcessID = ?");
    $processQuery->bind_param("i", $processID);
    $processQuery->execute();
    $processResult = $processQuery->get_result();
    $processData = $processResult->fetch_assoc();

    if (!$processData) {
        $_SESSION['error'] = "Invalid process selected.";
        header("Location: admin-dashboard.php");
        exit;
    }

    // ✅ Insert appointment using processType as INT (foreign key)
    $stmt = $conn->prepare("
        INSERT INTO appointment (
            clientName, 
            EmployeeID, 
            processType, 
            specialization, 
            dateAppointment, 
            Time, 
            date_created, 
            status
        ) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), 'Pending')
    ");

    $stmt->bind_param("siisss", 
        $clientName, 
        $employeeID, 
        $processID,  // processType stores the process ID, not name
        $specialization, 
        $appointmentDate, 
        $appointmentTime
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Appointment successfully added for client: $clientName.";
    } else {
        $_SESSION['error'] = "❌ Error adding appointment: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: admin-dashboard.php");
    exit;
} else {
    header("Location: admin-dashboard.php");
    exit;
}
?>
