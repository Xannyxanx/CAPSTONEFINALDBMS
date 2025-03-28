<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "archived";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html");
    exit();
}

$branch = $_SESSION['branch'];
$period = $_GET['period']; // 'week', 'month', or 'year'
$chartType = $_GET['chartType']; // 'pie' or 'bar'

// Calculate date range based on the selected period
$currentDate = date('Y-m-d');
switch ($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-1 week'));
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-1 month'));
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $startDate = $currentDate; // Default to today
        break;
}

// Function to get all table names within the date range
function getTableNames($conn, $startDate, $currentDate) {
    $tables = [];
    $start = new DateTime($startDate);
    $end = new DateTime($currentDate);

    // Loop through each date in the range
    for ($date = $start; $date <= $end; $date->modify('+1 day')) {
        $tableName = $date->format('Y-m-d');
        // Check if the table exists
        $result = $conn->query("SHOW TABLES LIKE '$tableName'");
        if ($result->num_rows > 0) {
            $tables[] = $tableName;
        }
    }
    return $tables;
}

// Get all relevant table names
$tableNames = getTableNames($conn, $startDate, $currentDate);

if (empty($tableNames)) {
    // No tables found for the selected period
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Fetch data for the Pie Chart (Food Categories)
if ($chartType === 'pie') {
    $drinks = 0;
    $pastry = 0;
    $pasta = 0;

    // Loop through each table and aggregate data
    foreach ($tableNames as $table) {
        $sql = "SELECT 
                    SUM(CASE WHEN LOWER(food) LIKE '%drink%' THEN 1 ELSE 0 END) AS drinks,
                    SUM(CASE WHEN LOWER(food) LIKE '%pastry%' THEN 1 ELSE 0 END) AS pastry,
                    SUM(CASE WHEN LOWER(food) LIKE '%pasta%' THEN 1 ELSE 0 END) AS pasta
                FROM `$table`
                WHERE branch = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $branch);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $drinks += $data['drinks'];
        $pastry += $data['pastry'];
        $pasta += $data['pasta'];
    }

    // Format data for the pie chart
    $pieData = [$drinks, $pastry, $pasta];

    header('Content-Type: application/json');
    echo json_encode($pieData);
}

// Fetch data for the Bar Chart (Senior Citizens, PWD, Others, Total)
elseif ($chartType === 'bar') {
    $senior = 0;
    $pwd = 0;
    $others = 0;
    $total = 0;

    // Loop through each table and aggregate data
    foreach ($tableNames as $table) {
        $sql = "SELECT 
                    SUM(CASE WHEN citizen = 'Senior Citizen' THEN 1 ELSE 0 END) AS senior,
                    SUM(CASE WHEN citizen = 'PWD' THEN 1 ELSE 0 END) AS pwd,
                    SUM(CASE WHEN citizen NOT IN ('Senior Citizen', 'PWD') THEN 1 ELSE 0 END) AS others,
                    COUNT(*) AS total
                FROM `$table`
                WHERE branch = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $branch);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $senior += $data['senior'];
        $pwd += $data['pwd'];
        $others += $data['others'];
        $total += $data['total'];
    }

    // Format data for the bar chart
    $barData = [$senior, $pwd, $others, $total];

    header('Content-Type: application/json');
    echo json_encode($barData);
}

$conn->close();
?>