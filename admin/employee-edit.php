<?php
include '../db.php'; // your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeID = $_POST['EmployeeID'];

    $firstName   = $_POST['FirstName'];
    $lastName    = $_POST['LastName'];
    $position    = $_POST['Position'];
    $nfcCardID   = !empty($_POST['NfcCardID']) ? $_POST['NfcCardID'] : NULL;
    $status      = $_POST['Status'];
    $contactInfo = $_POST['ContactInfo'];
    $dateHired   = $_POST['DateHired'];

    $username = $_POST['Username'];
    $password = $_POST['Password']; // ⚠️ plain text (as per your note)
    $role     = $_POST['Role'];

    $conn->begin_transaction();

    try {
        // 1. Update employees table
        $sqlEmp = "UPDATE employees 
        SET FirstName=?, LastName=?, Position=?, NfcCardID=?, 
            Status=?, ContactInfo=?, DateHired=? 
        WHERE EmployeeID=?";
$stmtEmp = $conn->prepare($sqlEmp);
$stmtEmp->bind_param(
 "sssisssi",
 $firstName,
 $lastName,
 $position,
 $nfcCardID,
 $status,
 $contactInfo,
 $dateHired,
 $employeeID
);
$stmtEmp->execute();


        // 2. Check if user already exists
        $checkUser = $conn->prepare("SELECT UserID FROM users WHERE EmployeeID=?");
        $checkUser->bind_param("i", $employeeID);
        $checkUser->execute();
        $checkUser->store_result();

        if ($checkUser->num_rows > 0) {
            // Update user login info
            $sqlUser = "UPDATE users 
                        SET Username=?, Password=?, Role=? 
                        WHERE EmployeeID=?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("sssi", $username, $password, $role, $employeeID);
            $stmtUser->execute();
        } else {
            // Create user login if doesn’t exist
            $sqlUser = "INSERT INTO users (EmployeeID, Username, Password, Role) 
                        VALUES (?, ?, ?, ?)";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("isss", $employeeID, $username, $password, $role);
            $stmtUser->execute();
        }

        $conn->commit();

        echo "<script>
                alert('Employee updated successfully!');
                window.location.href='employee-management.php';
              </script>";
    } catch (Exception $e) {
        $conn->rollback();
        die("Error updating employee: " . $e->getMessage());
    }
}
?>
