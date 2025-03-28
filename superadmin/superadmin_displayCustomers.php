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

// Pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 8; // Number of records per page
$offset = ($page - 1) * $limit;

// Fetch distinct branches from the branches table
$sqlBranches = "SELECT branch_name FROM branches WHERE branch_name IS NOT NULL AND branch_name != '' AND LOWER(branch_name) != 'superadmin'";
$resultBranches = $conn->query($sqlBranches);

$branches = [];
if ($resultBranches->num_rows > 0) {
    while ($row = $resultBranches->fetch_assoc()) {
        $branches[] = $row['branch_name'];
    }
}

// Fetch customers based on the selected branch
$selectedBranch = isset($_GET['branch']) ? strtolower(trim($_GET['branch'])) : 'all';

$totalQuery = "SELECT COUNT(*) AS total FROM dapitancustomers";
if ($selectedBranch !== "all") {
    $totalQuery .= " WHERE LOWER(branch) = '$selectedBranch'";
}
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

$sql = "SELECT ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number
        FROM dapitancustomers";
if ($selectedBranch !== "all") {
    $sql .= " WHERE LOWER(branch) = '$selectedBranch'";
}
$sql .= " ORDER BY time DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

$customers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

$conn->close();

// Include both branches and customers in the JSON response
header('Content-Type: application/json');
echo json_encode([
    "branches" => $branches,
    "customers" => $customers,
    "totalPages" => $totalPages,
    "currentPage" => $page
]);
?>