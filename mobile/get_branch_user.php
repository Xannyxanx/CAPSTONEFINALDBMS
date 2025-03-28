<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Get all branches except SUPERADMIN
$sqlBranches = "SELECT DISTINCT branch_name FROM branches WHERE branch_name IS NOT NULL AND branch_name != '' AND LOWER(branch_name) != 'superadmin'";
$resultBranches = $conn->query($sqlBranches);

$branches = [];
if ($resultBranches->num_rows > 0) {
    while ($row = $resultBranches->fetch_assoc()) {
        $branches[] = $row['branch_name'];
    }
}

// Check if branch parameter is provided
$branch = isset($_GET['branch_name']) ? strtolower(trim($_GET['branch_name'])) : '';

if (empty($branch)) {
    // Return only branches if no branch is selected, without pre-selecting any branch
    echo json_encode([
        "success" => true, 
        "branches" => $branches,
        "selectedBranch" => "" // Explicitly set selected branch to empty
    ]);
    exit();
}

// Fetch all users in the selected branch
$sql = "SELECT id, name, email FROM users WHERE LOWER(branch) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $branch);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row; // Add each user to the users array
    }
}

if (!empty($users)) {
    echo json_encode([
        "success" => true,
        "users" => $users, // Return all users
        "branches" => $branches,
        "selectedBranch" => $branch
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "No users found for the selected branch.",
        "branches" => $branches,
        "selectedBranch" => $branch
    ]);
}

$stmt->close();
$conn->close();
?>