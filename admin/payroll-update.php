<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['PayrollID'];
    $gross = $_POST['GrossPay'];
    $deduction = $_POST['Deduction'];
    $remarks = $_POST['Remarks'];

    $net = $gross - $deduction;

    $stmt = $conn->prepare("UPDATE payroll SET GrossPay=?, Deduction=?, NetPay=?, Remarks=? WHERE PayrollID=?");
    $stmt->bind_param("dddsi", $gross, $deduction, $net, $remarks, $id);
    
    echo $stmt->execute() ? "success" : "error";
}
?>
