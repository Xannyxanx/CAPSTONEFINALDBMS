<?php
session_start();
require 'vendor/autoload.php';  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "callecafe";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Invalid request method."]);
    exit();
}

// Get login credentials from the request
$data = json_decode(file_get_contents("php://input"), true);

$email = isset($data['email']) ? trim($data['email']) : null;
$password = isset($data['password']) ? trim($data['password']) : null;

// Validate inputs
if (empty($email) || empty($password)) {
    echo json_encode(["error" => "Please fill in all fields."]);
    exit();
}

// Fetch user details from the database
$stmt = $conn->prepare("SELECT id, name, email, password, branch FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $otp = rand(100000, 999999);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['branch'] = $user['branch'];
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_time'] = time(); // Store the current timestamp
        
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

            //Sender
            $mail->setFrom('cafeotphandler@gmail.com', 'Cafe OTP');
            $mail->addAddress($email);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "Your OTP is: $otp. It is valid for 3 minutes.";


            error_log("OTP: " . $otp);
            error_log("Email: " . $email);
            error_log("Session Variables: " . json_encode($_SESSION));
            // Sending email
            if ($mail->send()) {
                echo json_encode(["success" => true, "message" => "OTP sent"]);
                exit();
            } else {
                echo json_encode(["error" => "Error sending OTP: " . $mail->ErrorInfo]);
                exit();
            }
        } catch (Exception $e) {
            echo json_encode(["error" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
            exit();
        }
    } else {
        echo json_encode(["error" => "Incorrect email or password."]);
        exit();
    }
} else {
    echo json_encode(["error" => "Incorrect email or password."]);
    exit();
}

// Close connections
$stmt->close();
$conn->close();
?>

