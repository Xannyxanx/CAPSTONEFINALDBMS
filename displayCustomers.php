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
$orderType = isset($_GET['orderType']) ? strtolower(trim($_GET['orderType'])) : 'all';
$limit = 8;
$offset = ($page - 1) * $limit;

// Get all records first
$sql = "SELECT ID, name, citizen, food, city, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number 
        FROM dapitancustomers 
        WHERE branch = '$branch'";

$result = $conn->query($sql);
$allRecords = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allRecords[] = $row;
    }
}

// Apply order type filtering
if ($orderType !== 'all') {
    // Group records by control number
    $groupedRecords = [];
    foreach ($allRecords as $record) {
        $controlNumber = $record['control_number'];
        if (!isset($groupedRecords[$controlNumber])) {
            $groupedRecords[$controlNumber] = [];
        }
        $groupedRecords[$controlNumber][] = $record;
    }

    // Filter based on order type
    if ($orderType === 'group') {
        // Keep only group orders (control numbers with more than 1 record)
        $groupedRecords = array_filter($groupedRecords, function($group) {
            return count($group) > 1;
        });
    } else if ($orderType === 'single') {
        // Keep only single orders (control numbers with exactly 1 record)
        $groupedRecords = array_filter($groupedRecords, function($group) {
            return count($group) === 1;
        });
    }

    // Flatten the grouped records back into a single array
    $allRecords = [];
    foreach ($groupedRecords as $group) {
        $allRecords = array_merge($allRecords, $group);
    }
}

// Sort by time
usort($allRecords, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Calculate pagination
$totalRecords = count($allRecords);
$totalPages = ceil($totalRecords / $limit);
$paginatedRecords = array_slice($allRecords, $offset, $limit);

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    "customers" => $paginatedRecords,
    "totalPages" => $totalPages,
    "currentPage" => $page
]);
?>