<?php
header('Content-Type: application/json'); // Ensure JSON response
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

// Log received POST data for debugging
file_put_contents('post_data.log', print_r($_POST, true));

// Check if POST data is present
if (empty($_POST)) {
    echo json_encode(["success" => false, "message" => "No POST data received"]);
    exit();
}

if (!isset($_POST['pin']) || !isset($_POST['cashierName']) || !isset($_POST['branch'])) {
    echo json_encode(["success" => false, "message" => "Missing POST data: pin, cashierName, or branch"]);
    exit();
}

// Get POST data
$pin = $_POST['pin'];
$cashierName = $_POST['cashierName'];
$branch = $_POST['branch'];

$stmt = $conn->prepare("SELECT * FROM cashier_users WHERE pin = ? AND name = ?");


// Check if statement preparation succeeded
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Failed to prepare statement"]);
    $conn->close();
    exit();
}

// Bind parameters and execute
if (!$stmt->bind_param("ss", $pin, $cashierName) || !$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to bind or execute statement"]);
    $stmt->close();
    $conn->close();
    exit();
}

// Fetch result
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid PIN"]);
}

$stmt->close();
$conn->close();
?>