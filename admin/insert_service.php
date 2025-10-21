<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ProcessName = $_POST['ProcessName'];
    $ProcessPrice = $_POST['ProcessPrice'];
    $specialization = $_POST['specialization'];

    $stmt = $conn->prepare("INSERT INTO services (ProcessName, ProcessPrice, specialization) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $ProcessName, $ProcessPrice, $specialization);
    $stmt->execute();
    $stmt->close();

    header("Location: admin-dashboard.php");
    exit;
}
?>
