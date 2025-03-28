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

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html");
    exit();
}

// Fetch distinct branches from the users table
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html");
    exit();
}

// Fetch distinct branches from the users table, excluding "superadmin"
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

$customers = [];
$counters = [
    "totalDiscounted" => 0,
    "totalSenior" => 0,
    "totalPWD" => 0
];

if ($selectedBranch === 'all') {
    // Fetch all customers from all branches
    $sqlTable = "SELECT ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number 
                 FROM dapitancustomers
                 ORDER BY time DESC 
                 LIMIT 5";

    $sqlCounters = "SELECT 
        COUNT(*) AS totalDiscounted,
        SUM(CASE WHEN LOWER(TRIM(citizen)) = 'senior citizen' THEN 1 ELSE 0 END) AS totalSenior,
        SUM(CASE WHEN LOWER(TRIM(citizen)) = 'pwd' THEN 1 ELSE 0 END) AS totalPWD
        FROM dapitancustomers";
} else {
    // Fetch customers for the selected branch
    $sqlTable = "SELECT ID, name, citizen, food, date, time, cashier, discounted_price
                 FROM dapitancustomers
                 WHERE LOWER(branch) = ?
                 ORDER BY time DESC 
                 LIMIT 5";

    $sqlCounters = "SELECT 
        COUNT(*) AS totalDiscounted,
        SUM(CASE WHEN LOWER(TRIM(citizen)) = 'senior citizen' THEN 1 ELSE 0 END) AS totalSenior,
        SUM(CASE WHEN LOWER(TRIM(citizen)) = 'pwd' THEN 1 ELSE 0 END) AS totalPWD
        FROM dapitancustomers
        WHERE LOWER(branch) = ?";
}

$stmt = $conn->prepare($sqlTable);
if ($selectedBranch !== 'all') {
    $stmt->bind_param("s", $selectedBranch);
}
$stmt->execute();
$resultTable = $stmt->get_result();

if ($resultTable->num_rows > 0) {
    while ($row = $resultTable->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Fetch counters
$stmtCounters = $conn->prepare($sqlCounters);
if ($selectedBranch !== 'all') {
    $stmtCounters->bind_param("s", $selectedBranch);
}
$stmtCounters->execute();
$resultCounters = $stmtCounters->get_result();
$counters = $resultCounters->fetch_assoc();

// Ensure counters are set to 0 if null
$counters['totalDiscounted'] = $counters['totalDiscounted'] ?? 0;
$counters['totalSenior'] = $counters['totalSenior'] ?? 0;
$counters['totalPWD'] = $counters['totalPWD'] ?? 0;

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    "branches" => $branches,
    "customers" => $customers,
    "totalDiscounted" => $counters['totalDiscounted'],
    "totalSenior" => $counters['totalSenior'],
    "totalPWD" => $counters['totalPWD']
]);
?>