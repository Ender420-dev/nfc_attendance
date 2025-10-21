<?php
include '../db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointmentID = $_POST['appointmentID'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE appointment SET status = ? WHERE appointmentID = ?");
    $stmt->bind_param("si", $status, $appointmentID);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating status: " . $conn->error;
    }

    header("Location: admin-dashboard.php");
    exit();
}
?>
