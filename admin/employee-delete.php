<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeID = $_POST['EmployeeID'] ?? null;

    if ($employeeID) {
        try {
            // Begin transaction
            $conn->begin_transaction();

            // Delete from users table first (if foreign key constraint exists)
            $sqlUser = "DELETE FROM users WHERE EmployeeID = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("i", $employeeID);
            $stmtUser->execute();

            // Delete from employees table
            $sqlEmp = "DELETE FROM employees WHERE EmployeeID = ?";
            $stmtEmp = $conn->prepare($sqlEmp);
            $stmtEmp->bind_param("i", $employeeID);
            $stmtEmp->execute();

            $conn->commit();

            echo "<script>
                    alert('Employee deleted successfully!');
                    window.location.href='employee-management.php';
                  </script>";
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Error: Employee ID is missing.";
    }
}
?>
