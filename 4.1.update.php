<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$servername = "localhost";
$username = "root";
$password = "";
$customersDb = "callecafe";

$conn = new mysqli($servername, $username, $password, $customersDb);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

if (!isset($_SESSION['branch'])) {
    error_log("Session branch not set");
    echo json_encode(["status" => "error", "message" => "Session branch is missing."]);
    exit();
}

$branch = $_SESSION['branch'];

error_log("Received POST data: " . print_r($_POST, true));

if (!isset($_POST['id']) || empty($_POST['id']) || !isset($_POST['pin']) || empty($_POST['pin']) || !isset($_POST['username']) || empty($_POST['username']) || !isset($_POST['status'])) { 
    error_log("Missing Required Fields: " . print_r($_POST, true));
    echo json_encode(["status" => "error", "message" => "Missing Required Fields"]);
    exit();
}

$userID = intval($_POST['id']);
$newPin = $_POST['pin'];
$newUsername = $_POST['username'];
$newStatus = $_POST['status'];
error_log("Updating user ID: " . $userID . " with new PIN: " . $newPin . " and new username: " . $newUsername . " and new status: " . $newStatus);

    $sqlUpdate = "UPDATE `cashier_users` SET pin = ?, username = ?, status = ? WHERE ID = ?";


$stmt = $conn->prepare($sqlUpdate);
$stmt->bind_param("sssi", $newPin, $newUsername, $newStatus, $userID);

if (!$stmt->execute()) {
    error_log("SQL Error (Update): " . $stmt->error);
    echo json_encode(["status" => "error", "message" => "Error updating user: " . $stmt->error]);
    exit();
}

$response = ["status" => "success", "message" => "User PIN and username have been updated"];
error_log("Response: " . json_encode($response));

header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
?>