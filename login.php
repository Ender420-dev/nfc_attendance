<?php
session_start();
include 'db.php';
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM users WHERE Username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($password === $row['Password']) {

            // ✅ Check if employee is terminated
            if ($row['Role'] === "Employee") {
                $empCheck = $conn->prepare("SELECT Status FROM employees WHERE EmployeeID = ?");
                $empCheck->bind_param("i", $row['EmployeeID']);
                $empCheck->execute();
                $empRes = $empCheck->get_result()->fetch_assoc();
                $empCheck->close();

                if ($empRes && $empRes['Status'] === 'Terminated') {
                    echo "<p style='color:red; text-align:center;'>❌ Your account has been terminated. Contact HR.</p>";
                    exit;
                }

                $_SESSION['EmployeeID'] = $row['EmployeeID'];
            }

            // ✅ Generate OTP
            $otp = rand(100000, 999999);
            $_SESSION['OTP'] = $otp;
            $_SESSION['OTP_Expire'] = time() + 300; // 5 min expiry
            $_SESSION['PendingUser'] = [
                'UserID' => $row['EmployeeID'], // store EmployeeID for name lookup
                'Username' => $row['Username'],
                'Role' => $row['Role'],
                'Email' => $row['email']
            ];

            // ✅ Send OTP email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'cannonroly123@gmail.com'; // your Gmail
                $mail->Password = 'xuvuhhjjvixcyjta'; // Gmail App password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('cannonroly123@gmail.com', 'Salon System');
                $mail->addAddress($row['email']);
                $mail->isHTML(true);
                $mail->Subject = 'Salon System OTP Verification';
                $mail->Body = "<h3>Your OTP Code is: <b>$otp</b></h3><p>This code expires in 5 minutes.</p>";

                $mail->send();

                // ✅ Redirect to OTP verification page
                header("Location: verify-otp.php");
                exit;

            } catch (Exception $e) {
                echo "<p style='color:red; text-align:center;'>❌ Could not send OTP. Error: {$mail->ErrorInfo}</p>";
            }

        } else {
            echo "<p style='color:red; text-align:center;'>❌ Invalid password.</p>";
        }
    } else {
        echo "<p style='color:red; text-align:center;'>❌ User not found.</p>";
    }

    $stmt->close();
}
$conn->close();
?>
