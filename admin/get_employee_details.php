<?php
include '../db.php';

if (isset($_GET['id'])) {
    $employeeId = intval($_GET['id']);

    $sql = "SELECT e.EmployeeID, e.FirstName, e.LastName, e.Position, 
                   e.NfcCardID, e.Status, e.ContactInfo, e.DateHired,
                   u.Username, u.Password, u.Role, u.email
            FROM employees e
            LEFT JOIN users u ON e.EmployeeID = u.EmployeeID
            WHERE e.EmployeeID = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "Employee not found"]);
    }

    $stmt->close();
}
$conn->close();
?>
