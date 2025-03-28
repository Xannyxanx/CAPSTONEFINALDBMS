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
$selectedBranch = isset($_GET['branch']) ? strtolower(trim($_GET['branch'])) : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 8; // Number of records per page
$offset = ($page - 1) * $limit;

// Initialize $branches to avoid undefined variable error
$branches = [];

if (isset($_GET['fetchBranches']) && $_GET['fetchBranches'] === 'true') {
    // Fetch distinct branches from the branches table
    $branchConn = new mysqli($servername, $username, $password, "callecafe");
    if ($branchConn->connect_error) {
        die("Connection failed: " . $branchConn->connect_error);
    }

    $sqlBranches = "SELECT branch_name FROM branches WHERE branch_name IS NOT NULL AND branch_name != '' AND LOWER(branch_name) != 'superadmin'";
    $resultBranches = $branchConn->query($sqlBranches);

    if ($resultBranches->num_rows > 0) {
        while ($row = $resultBranches->fetch_assoc()) {
            $branches[] = $row['branch_name'];
        }
    }
    $branchConn->close();

    header('Content-Type: application/json');
    echo json_encode(["branches" => $branches]);
    exit();
}

if (empty($fromDate) || empty($toDate)) {
    echo json_encode([
        "branches" => $branches,
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
$totalRecords = 0;

// Loop through each date in the range
foreach ($dateRange as $date) {
    $currentDate = $date->format('Y-m-d');

    // Check if the table for the current date exists
    $tableExistsQuery = "SHOW TABLES LIKE '$currentDate'";
    $tableExistsResult = $conn->query($tableExistsQuery);

    if ($tableExistsResult->num_rows == 0) {
        continue; // Skip this date if the table does not exist
    }

    // Fetch records for the current date
    $sql = "SELECT ID, name, citizen, food, city, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number 
            FROM `$currentDate` 
            WHERE date = '$currentDate'";
    if ($selectedBranch !== 'all') {
        $sql .= " AND LOWER(branch) = '$selectedBranch'";
    }
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

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $allRecords[] = $row;
        }
    }
}

// Calculate total records and apply pagination
$totalRecords = count($allRecords);
$totalPages = ceil($totalRecords / $limit);
$paginatedRecords = array_slice($allRecords, $offset, $limit);

$conn->close();

// Include branches, records, and pagination info in the JSON response
header('Content-Type: application/json');
echo json_encode([
    "branches" => $branches,
    "records" => $paginatedRecords,
    "totalPages" => $totalPages,
    "currentPage" => $page
]);
?>