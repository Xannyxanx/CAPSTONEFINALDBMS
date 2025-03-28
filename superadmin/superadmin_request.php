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

// Get branch filter parameter, default to 'all'
$branch = isset($_GET['branch']) ? strtolower(trim($_GET['branch'])) : 'all';

// Build the SQL query based on the branch filter
if ($branch === 'all') {
    // When 'all' is selected, fetch all requests
    $sql = "SELECT * FROM cashier_requests";
} else {
    // When a specific branch is selected
    $sql = "SELECT * FROM cashier_requests WHERE LOWER(branch) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $branch);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode($requests);
    exit();
}

// For 'all' option, use direct query
$result = $conn->query($sql);
$requests = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($requests);
?>