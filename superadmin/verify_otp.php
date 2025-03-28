<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $userOtp = $data['otp'];
    $sessionOtp = $_SESSION['otp'];
    $otpTime = isset($_SESSION['otp_time']) ? $_SESSION['otp_time'] : null;
    $currentTime = time();

    if ($otpTime === null) {
        echo json_encode(["success" => false, "error" => "OTP time not set. Please request a new OTP."]);
        exit();
    }

    // Check if OTP is expired (5 minutes = 300 seconds)
    if (($currentTime - $otpTime) > 180) {
        echo json_encode(["success" => false, "error" => "OTP expired. Please try again."]);
        exit();
    }

    if ($userOtp == $sessionOtp) {
        echo json_encode(["success" => true, "message" => "OTP Verified!"]);
        exit();
    } else {
        echo json_encode(["success" => false, "error" => "Invalid OTP. Please try again."]);
        exit();
    }
}
?>