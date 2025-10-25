<?php
include '../db.php';

if (isset($_POST['processName'])) {
    $processName = $_POST['processName'];
    $query = "SELECT ProcessName, specialization, ProcessPrice FROM services WHERE ProcessName = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $processName);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'No record found']);
    }
}
?>
