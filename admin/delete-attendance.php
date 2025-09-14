<?php
session_start();
include '../db.php';

if(isset($_POST['AttendanceID'])) {
    $attendanceID = intval($_POST['AttendanceID']);

    // Delete attendance record
    $sql = "DELETE FROM attendance WHERE AttendanceID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $attendanceID);

    if($stmt->execute()) {
        echo "Attendance record deleted successfully.";
    } else {
        echo "Failed to delete attendance record.";
    }

    $stmt->close();
} else {
    echo "No Attendance ID provided.";
}
?>
