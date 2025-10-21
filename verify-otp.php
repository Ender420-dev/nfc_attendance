<?php
session_start();
include 'db.php';

if (!isset($_SESSION['OTP']) || !isset($_SESSION['PendingUser'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otpInput = trim($_POST['otp']);
    $otpStored = $_SESSION['OTP'];
    $otpExpire = $_SESSION['OTP_Expire'];
    $pendingUser = $_SESSION['PendingUser'];

    // Check if OTP expired
    if (time() > $otpExpire) {
        echo "<p style='color:red; text-align:center;'>❌ OTP expired. Please log in again.</p>";
        session_destroy();
        exit;
    }

    // Check if OTP matches
    if ($otpInput == $otpStored) {

        // Fetch employee name for display (optional)
        $fullName = $pendingUser['Username'];
        if (!empty($pendingUser['UserID'])) {
            $stmt = $conn->prepare("
                SELECT FirstName, LastName 
                FROM employees 
                WHERE EmployeeID = ?
            ");
            $stmt->bind_param("i", $pendingUser['UserID']);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $emp = $res->fetch_assoc();
                $fullName = $emp['FirstName'] . ' ' . $emp['LastName'];
            }
            $stmt->close();
        }

        // Set final session values
        $_SESSION['user_id']   = $pendingUser['UserID'];
        $_SESSION['username']  = $pendingUser['Username'];
        $_SESSION['role']      = $pendingUser['Role'];
        $_SESSION['email']     = $pendingUser['Email'];
        $_SESSION['user_name'] = $fullName;

        // Clear temporary OTP data
        unset($_SESSION['OTP'], $_SESSION['OTP_Expire'], $_SESSION['PendingUser']);

        // Redirect based on role
        switch ($pendingUser['Role']) {
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
                echo "<p style='color:red; text-align:center;'>❌ Unknown role. Contact admin.</p>";
                session_destroy();
                exit;
        }
        exit;
    } else {
        echo "<p style='color:red; text-align:center;'>❌ Invalid OTP. Please try again.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Salon System</title>
    <link rel="stylesheet" href="css/index.css" />
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2>🔐 Verify OTP</h2>
            <p>We’ve sent a 6-digit OTP to your email <b><?php echo htmlspecialchars($_SESSION['PendingUser']['Email']); ?></b></p>

            <form method="POST">
                <div class="input-group">
                    <label for="otp">Enter OTP</label>
                    <input type="text" id="otp" name="otp" maxlength="6" placeholder="Enter your OTP" required>
                </div>
                <button type="submit" class="btn-login">Verify</button>
            </form>

            <p style="text-align:center; margin-top:10px;">
                <a href="index.php">← Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>
