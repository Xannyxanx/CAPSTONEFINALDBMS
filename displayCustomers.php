<?php
session_start();
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

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$branch = $_SESSION['branch'];

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 8; // Number of records per page
$offset = ($page - 1) * $limit;

// Fetch total record count
$totalQuery = "SELECT COUNT(*) AS total FROM dapitancustomers WHERE branch = '$branch'";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch records for the current page
$sql = "SELECT ID, name, citizen, food, city, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number
        FROM dapitancustomers
        WHERE branch = '$branch'
        ORDER BY time DESC
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$customers = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    "customers" => $customers,
    "totalPages" => $totalPages,
    "currentPage" => $page
]);
?>