<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$servername = "localhost";
$username = "root";
$password = "";
$archiveDb = "archived"; 

$conn = new mysqli($servername, $username, $password, $archiveDb);

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

$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';
$query = isset($_GET['query']) ? $_GET['query'] : '';
$orderType = isset($_GET['orderType']) ? strtolower(trim($_GET['orderType'])) : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 8;
$offset = ($page - 1) * $limit;

if (empty($fromDate) || empty($toDate)) {
    echo json_encode([
        "records" => [],
        "totalPages" => 0,
        "currentPage" => 1
    ]);
    exit();
}

// Convert date range into an array of dates
$startDate = new DateTime($fromDate);
$endDate = new DateTime($toDate);
$dateInterval = new DateInterval('P1D');
$dateRange = new DatePeriod($startDate, $dateInterval, $endDate->add($dateInterval));

// Initialize variables for merging records
$allRecords = [];

// Loop through each date in the range
foreach ($dateRange as $date) {
    $currentDate = $date->format('Y-m-d');

    // Check if the table exists
    $tableExistsQuery = "SHOW TABLES LIKE '$currentDate'";
    $tableExistsResult = $conn->query($tableExistsQuery);

    if ($tableExistsResult->num_rows == 0) {
        continue;
    }

    // Base query
    $sql = "SELECT ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number 
            FROM `$currentDate` 
            WHERE date = '$currentDate' AND branch = '$branch'";

    // Apply search filter if provided
    if (!empty($query)) {
        $query = $conn->real_escape_string($query);
        $sql .= " AND (
            ID LIKE '%$query%' OR 
            name LIKE '%$query%' OR 
            citizen LIKE '%$query%' OR 
            city LIKE '%$query%' OR
            food LIKE '%$query%' OR 
            date LIKE '%$query%' OR 
            time LIKE '%$query%' OR 
            cashier LIKE '%$query%'
        )";
    }

    $sql .= " ORDER BY time DESC";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $allRecords[] = $row;
        }
    }
}

// Apply order type filtering globally after merging all records
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

// Calculate total records and apply pagination
$totalRecords = count($allRecords);
$totalPages = ceil($totalRecords / $limit);
$paginatedRecords = array_slice($allRecords, $offset, $limit);

$conn->close();

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode([
    "records" => $paginatedRecords,
    "totalPages" => $totalPages,
    "currentPage" => $page
]);
?>