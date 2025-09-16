<?php
// payroll-mark-paid.php
include '../db.php';

$id = intval($_GET['id']);
$sql = "UPDATE payroll SET Remarks='Paid' WHERE PayrollID=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: payroll-management.php?status=success");
} else {
    header("Location: payroll-management.php?status=error");
}

?>
