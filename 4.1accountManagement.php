<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "callecafe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}


$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$branch = $_SESSION['branch'];

// Get the status filter parameter
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'all';

// Build the SQL query based on the status filter
if ($status === 'all') {
    $sql = "SELECT ID, name, username, pin, status FROM cashier_users WHERE branch = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $branch);
} else {
    $sql = "SELECT ID, name, username, pin, status FROM cashier_users WHERE branch = ? AND LOWER(status) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $branch, $status);
}

$stmt->execute();
$result = $stmt->get_result();

$user = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user[] = $row;
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($user);
?>