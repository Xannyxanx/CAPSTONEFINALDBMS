<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(["success" => false, "error" => "User not logged in."]));
}

// Fetch current discount values from the database
$query = "SELECT category, percentage FROM discounts";
$result = $conn->query($query);

if (!$result) {
    die(json_encode(["success" => false, "error" => "Query failed: " . $conn->error]));
}

$discounts = [];
while ($row = $result->fetch_assoc()) {
    $discounts[] = [
        "category" => $row["category"],
        "percentage" => $row["percentage"]
    ];
}

echo json_encode(["success" => true, "discounts" => $discounts]);

$conn->close();
?>