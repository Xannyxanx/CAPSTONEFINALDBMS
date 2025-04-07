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

// Validate required fields
if (!isset($_POST['id']) || empty($_POST['id'])) {
    error_log("Missing user ID");
    echo json_encode(["status" => "error", "message" => "User ID is required"]);
    exit();
}

$userID = intval($_POST['id']);
$fieldsToUpdate = [];
$params = [];

// Check which fields are provided and add them to the update query
if (!empty($_POST['username'])) {
    $fieldsToUpdate[] = "username = ?";
    $params[] = $_POST['username'];
}
if (!empty($_POST['pin'])) {
    $fieldsToUpdate[] = "pin = ?";
    $params[] = $_POST['pin'];
}
if (!empty($_POST['branch'])) {
    $fieldsToUpdate[] = "branch = ?";
    $params[] = $_POST['branch'];
}
if (isset($_POST['status'])) { // Allow "Inactive" or "0" as valid status
    $fieldsToUpdate[] = "status = ?";
    $params[] = $_POST['status'];
}

if (empty($fieldsToUpdate)) {
    echo json_encode(["status" => "error", "message" => "No fields to update"]);
    exit();
}

$params[] = $userID; // Add user ID as the last parameter
$sqlUpdate = "UPDATE `cashier_users` SET " . implode(", ", $fieldsToUpdate) . " WHERE ID = ?";
$stmt = $conn->prepare($sqlUpdate);

if (!$stmt) {
    error_log("SQL Error (Prepare): " . $conn->error);
    echo json_encode(["status" => "error", "message" => "SQL prepare error: " . $conn->error]);
    exit();
}

// Dynamically bind parameters
$stmt->bind_param(str_repeat("s", count($params) - 1) . "i", ...$params);

if (!$stmt->execute()) {
    error_log("SQL Error (Execute): " . $stmt->error);
    echo json_encode(["status" => "error", "message" => "Error updating user: " . $stmt->error]);
    exit();
}

$response = [
    "status" => "success",
    "message" => "User details have been updated successfully",
    "updatedFields" => array_combine(array_map(function ($field) {
        return explode(" = ", $field)[0];
    }, $fieldsToUpdate), array_slice($params, 0, -1))
];
error_log("Response: " . json_encode($response));

header('Content-Type: application/json');
echo json_encode($response);

$stmt->close();
$conn->close();
?>