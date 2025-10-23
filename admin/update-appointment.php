<?php
include '../db.php';

$id = $_POST['appointmentID'];
$client = $_POST['clientName'];
$emp = $_POST['EmployeeID'];
$process = $_POST['processType'];
$spec = $_POST['specialization'];
$date = $_POST['dateAppointment'];
$time = $_POST['Time'];
$status = $_POST['status'];

$sql = "UPDATE appointment 
        SET clientName=?, EmployeeID=?, processType=?, specialization=?, dateAppointment=?, Time=?, status=? 
        WHERE appointmentID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sisssssi", $client, $emp, $process, $spec, $date, $time, $status, $id);

echo $stmt->execute() ? "✅ Appointment updated successfully!" : "❌ Update failed: " . $conn->error;
?>
