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

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(["status" => "error", "message" => "Missing user ID"]);
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
if (!empty($_POST['status'])) {
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
    echo json_encode(["status" => "error", "message" => "SQL prepare error: " . $conn->error]);
    exit();
}

$stmt->bind_param(str_repeat("s", count($params) - 1) . "i", ...$params);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "User updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error updating user: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>