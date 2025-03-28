<?php
session_start();

// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$customersDb = "callecafe";

$conn = new mysqli($servername, $username, $password, $customersDb);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

// Check if password is sent via POST
if (!isset($_POST['password']) || empty($_POST['password'])) {
    echo json_encode(["success" => false, "message" => "No password provided"]);
    exit();
}

$userID = $_SESSION['user_id'];
$password = $_POST['password'];

// Verify the password
$sql = "SELECT password FROM users WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($hashedPassword);
$stmt->fetch();

if (password_verify($password, $hashedPassword)) {
    echo json_encode(["success" => true, "message" => "Password verified"]);
} else {
    echo json_encode(["success" => false, "message" => "Incorrect password"]);
}

$stmt->close();
$conn->close();
?>