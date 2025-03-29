<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "callecafe";


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}


if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "User not logged in."]));
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT name, email, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode($user);
} else {
    echo json_encode(["error" => "User not found."]);
}

$stmt->close();
$conn->close();
?>
