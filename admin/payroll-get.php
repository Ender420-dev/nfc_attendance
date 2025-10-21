<?php
include '../db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT p.*, e.FirstName, e.LastName, e.Position
            FROM payroll p
            JOIN employees e ON p.EmployeeID = e.EmployeeID
            WHERE p.PayrollID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo json_encode($result);
}
?>
