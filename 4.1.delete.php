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


if (!isset($_POST['id']) || empty($_POST['id'])) {
    error_log("No user ID received: " . print_r($_POST, true));
    echo json_encode(["status" => "error", "message" => "No user ID provided"]);
    exit();
}

$userID = intval($_POST['id']);
error_log("Approving user ID: " . $userID);

    $checkUserQuery = "SELECT * FROM `cashier_users` WHERE ID = $userID";


$result = $conn->query($checkUserQuery);
if ($result->num_rows == 0) {
    error_log("User with ID $userID not found in users table.");
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
}

    $sqlDelete = "DELETE FROM `cashier_users` WHERE ID = ?";



$stmt = $conn->prepare($sqlDelete);
$stmt->bind_param("i", $userID);

if (!$stmt->execute()) {
    error_log("SQL Error (Delete): " . $stmt->error);
    echo json_encode(["status" => "error", "message" => "Error deleting user: " . $stmt->error]);
    exit();
}

echo json_encode(["status" => "success", "message" => "User account has been deleted"]);

$stmt->close();
$conn->close();
?>