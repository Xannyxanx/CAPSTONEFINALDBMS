<?php
session_start();
require 'vendor/autoload.php';  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure the email is retrieved from the session
    if (!isset($_SESSION['email'])) {
        echo json_encode(["success" => false, "error" => "Email not set in session."]);
        exit();
    }

    $email = $_SESSION['email'];
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time(); // Store the current timestamp

    $mail = new PHPMailer(true);  
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cafeotphandler@gmail.com';  
        $mail->Password = 'ztul dnsu rhpg avjq';  
        $mail->SMTPSecure = 'tls'; 
        $mail->Port = 587;

        //Sender
        $mail->setFrom('cafeotphandler@gmail.com', 'CAFE OTP');
        $mail->addAddress($email);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your OTP is: $otp. It is valid for 3 minutes.";

        // Sending email
        if ($mail->send()) {
            echo json_encode(["success" => true, "message" => "OTP sent"]);
            exit();
        } else {
            echo json_encode(["success" => false, "error" => "Error sending OTP: " . $mail->ErrorInfo]);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
        exit();
    }
}
?>