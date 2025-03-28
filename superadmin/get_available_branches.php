<?php
session_start();
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Fetch all branches from the available_branches table
$sql = "SELECT branch_name FROM branches ORDER BY branch_name ASC";
$result = $conn->query($sql);

$branches = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row['branch_name'];
    }
}

echo json_encode(["success" => true, "branches" => $branches]);

$conn->close();
?>