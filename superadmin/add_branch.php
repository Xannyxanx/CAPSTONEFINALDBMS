<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: application/json");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die(json_encode(["success" => false, "error" => "Invalid request method."]));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["success" => false, "error" => "User not logged in."]));
}

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);
$branchName = isset($data['branchName']) ? trim($data['branchName']) : null;
$name = isset($data['name']) ? trim($data['name']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;
$password = isset($data['password']) ? trim($data['password']) : null;

if (empty($branchName) || empty($name) || empty($email) || empty($password)) {
    die(json_encode(["success" => false, "error" => "All fields are required."]));
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert the branch into the branches table
$sqlBranch = "INSERT INTO branches (branch_name) VALUES (?)";
$stmtBranch = $conn->prepare($sqlBranch);

if (!$stmtBranch) {
    die(json_encode(["success" => false, "error" => "Prepare failed for branches table: " . $conn->error]));
}

$stmtBranch->bind_param("s", $branchName);

if (!$stmtBranch->execute()) {
    // Check if the error is due to a duplicate entry
    if ($conn->errno === 1062) { // Error code 1062 is for duplicate entry
        die(json_encode(["success" => false, "error" => "Branch already exists."]));
    } else {
        die(json_encode(["success" => false, "error" => "Failed to add a new branch" . $stmtBranch->error]));
    }
}

$stmtBranch->close();

// Insert the new user into the users table
$sqlUser = "INSERT INTO users (name, email, password, branch) VALUES (?, ?, ?, ?)";
$stmtUser = $conn->prepare($sqlUser);

if (!$stmtUser) {
    die(json_encode(["success" => false, "error" => "Prepare failed for users table: " . $conn->error]));
}

$stmtUser->bind_param("ssss", $name, $email, $hashedPassword, $branchName);

if ($stmtUser->execute()) {
    echo json_encode(["success" => true, "message" => "New branch and user added successfully."]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to add user to users table: " . $stmtUser->error]);
}

$stmtUser->close();
$conn->close();
?>