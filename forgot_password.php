<?php
session_start();
require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}


$email = isset($_POST['email']) ? trim($_POST['email']) : null;


if (empty($email)) {
    echo json_encode(["status" => "error", "message" => "Email is required."]);
    exit();
}


$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $userName = $user['name'];

    
    $newPassword = bin2hex(random_bytes(4)); 
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the password in the database
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->bind_param("si", $hashedPassword, $userId);

    if ($updateStmt->execute()) {
        // Send the new password via email
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'cafeotphandler@gmail.com'; 
            $mail->Password = 'onqt dipq smri tgfo'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            
            $mail->setFrom('cafeotphandler@gmail.com', 'Calle Cafe');
            $mail->addAddress($email, $userName);

            
            $mail->isHTML(true);
            $mail->Subject = 'Your New Password';
            $mail->Body = "
                <p>Hi <strong>$userName</strong>,</p>
                <p>Your password has been reset. Here is your new password:</p>
                <p><strong>$newPassword</strong></p>
                <p>Please log in and change your password immediately for security purposes.</p>
                <p>Thank you,<br>Calle Cafe Team</p>
            ";

            
            if ($mail->send()) {
                echo json_encode(["status" => "success", "message" => "A new password has been sent to your email."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to send email: " . $mail->ErrorInfo]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update password."]);
    }

    $updateStmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Email does not exist."]);
}

$stmt->close();
$conn->close();
?>