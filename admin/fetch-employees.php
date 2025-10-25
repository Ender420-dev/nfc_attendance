<?php
session_start();
include '../db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin'){
    echo json_encode([]);
    exit;
}

$result = $conn->query("SELECT EmployeeID, FirstName, LastName FROM employees ORDER BY FirstName ASC");
$employees = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $employees[] = $row;
    }
}

echo json_encode($employees);
$conn->close();
?>
