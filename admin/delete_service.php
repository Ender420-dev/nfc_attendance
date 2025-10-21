<?php
include '../db.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM services WHERE processID = $id");
}
header("Location: admin-dashboard.php");
exit;
?>
