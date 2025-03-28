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
    header("Location: 0.login.html");
    exit();
}

// Get branch and status filter parameters
$branch = isset($_GET['branch']) ? strtolower(trim($_GET['branch'])) : 'all';
$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'active'; // Default to 'active'

// Build the SQL query based on the filters
if ($branch === 'all' && $status === 'all') {
    // Fetch all users when no filters are applied
    $sql = "SELECT * FROM cashier_users";
    $stmt = $conn->prepare($sql);
} elseif ($branch === 'all') {
    // Fetch users based on status only
    $sql = "SELECT * FROM cashier_users WHERE LOWER(status) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
} elseif ($status === 'all') {
    // Fetch users based on branch only
    $sql = "SELECT * FROM cashier_users WHERE LOWER(branch) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $branch);
} else {
    // Fetch users based on both branch and status
    $sql = "SELECT * FROM cashier_users WHERE LOWER(branch) = ? AND LOWER(status) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $branch, $status);
}

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($users);
?>