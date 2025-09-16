<?php
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['PayrollID']);
    $gross = floatval($_POST['GrossPay']);
    $deduction = floatval($_POST['Deduction']);
    $remarks = trim($_POST['Remarks']);
    if ($remarks === "" || $remarks === "0") {
        $remarks = "Pending"; // fallback
    }
    
    $net = $gross - $deduction;

    $sql = "UPDATE payroll 
            SET GrossPay=?, Deduction=?, NetPay=?, Remarks=? 
            WHERE PayrollID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddsi", $gross, $deduction, $net, $remarks, $id);
    
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
}
?>
