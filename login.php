<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch user by username
    $sql = "SELECT * FROM users WHERE Username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Password check (plain text for now)
        if ($password === $row['Password']) {

            // If Employee, check termination status
            if ($row['Role'] === "Employee") {
                $empCheck = $conn->prepare("SELECT Status FROM employees WHERE EmployeeID = ?");
                $empCheck->bind_param("i", $row['EmployeeID']); // Use EmployeeID, not UserID
                $empCheck->execute();
                $empRes = $empCheck->get_result()->fetch_assoc();
                $empCheck->close();

                if ($empRes && $empRes['Status'] === 'Terminated') {
                    echo "<p style='color:red; text-align:center;'>❌ Your account has been terminated. Contact HR.</p>";
                    exit;
                }

                // Store EmployeeID in session
                $_SESSION['EmployeeID'] = $row['EmployeeID'];
            }

            // Store general session data
            $_SESSION['UserID']   = $row['UserID'];
            $_SESSION['Username'] = $row['Username'];
            $_SESSION['Role']     = $row['Role'];

            // Redirect by role
            switch ($row['Role']) {
                case "Admin":
                    header("Location: admin/admin-dashboard.php");
                    break;
                case "Owner":
                    header("Location: owner/owner-dashboard.php");
                    break;
                case "Employee":
                    header("Location: employee/employee-dashboard.php");
                    break;
                default:
                    echo "<p style='color:red; text-align:center;'>❌ Role not recognized</p>";
            }
            exit;
        } else {
            echo "<p style='color:red; text-align:center;'>❌ Invalid password</p>";
        }
    } else {
        echo "<p style='color:red; text-align:center;'>❌ User not found</p>";
    }

    $stmt->close();
}
$conn->close();
?>
