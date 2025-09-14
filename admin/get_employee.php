<?php
include '../db.php';

header('Content-Type: application/json'); // always return JSON

if (isset($_GET['id'])) {
    $employeeId = intval($_GET['id']);

    $stmt = $conn->prepare("SELECT FirstName, LastName, Position FROM employees WHERE EmployeeID = ? LIMIT 1");
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "Employee not found"]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "No employee ID provided"]);
}

$conn->close();
?>
