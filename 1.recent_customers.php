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

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html");
    exit();
}

// Prevent returning to verify_otp.html if already logged in
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    header("Location: 1.home.html"); // Redirect to the home page or any other page
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$branch = $_SESSION['branch'];

$sqlTable = "SELECT ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number 
             FROM dapitancustomers
             WHERE branch = '$branch' 
             ORDER BY time DESC 
             LIMIT 5";
             
$resultTable = $conn->query($sqlTable);

$customers = [];
if ($resultTable->num_rows > 0) {
    while ($row = $resultTable->fetch_assoc()) {
        if (!empty($row['customer_ID'])) {
            $row['customer_ID'] = base64_encode($row['customer_ID']);
        }
        $customers[] = $row;
    }
}

$sqlCounters = "SELECT 
    COUNT(*) AS totalDiscounted,
    SUM(CASE WHEN LOWER(citizen) = 'senior citizen' THEN 1 ELSE 0 END) AS totalSenior,
    SUM(CASE WHEN LOWER(citizen) = 'pwd' THEN 1 ELSE 0 END) AS totalPWD
    FROM dapitancustomers
    WHERE branch = ?";

$stmt = $conn->prepare($sqlCounters);
$stmt->bind_param("s", $branch);
$stmt->execute();
$resultCounters = $stmt->get_result();
$counters = $resultCounters->fetch_assoc();

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    "branch" => $branch,
    "customers" => $customers,
    "totalDiscounted" => $counters['totalDiscounted'],
    "totalSenior" => $counters['totalSenior'],
    "totalPWD" => $counters['totalPWD']
]);
?>