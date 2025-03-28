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

// Check if this is a discount update request
if (isset($data['action']) && $data['action'] === 'updateDiscounts') {
    if (!isset($data['discounts']) || !is_array($data['discounts']) || empty($data['discounts'])) {
        die(json_encode(["success" => false, "error" => "No discount data provided."]));
    }

    $successCount = 0;
    $errors = [];

    foreach ($data['discounts'] as $discount) {
        $category = $discount['category'];
        $percentage = $discount['percentage'];

        // Validate percentage
        if (!is_numeric($percentage) || $percentage < 0 || $percentage > 100) {
            $errors[] = "Invalid percentage for {$category}: {$percentage}";
            continue;
        }

        // Update the discount in the database
        $stmt = $conn->prepare("UPDATE discounts SET percentage = ? WHERE category = ?");
        if (!$stmt) {
            $errors[] = "Prepare failed for {$category}: " . $conn->error;
            continue;
        }

        $stmt->bind_param("ds", $percentage, $category);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = "Update failed for {$category}: " . $stmt->error;
        }

        $stmt->close();
    }

    if ($successCount > 0) {
        echo json_encode([
            "success" => true, 
            "message" => "Successfully updated {$successCount} discount(s)." . 
                         (count($errors) > 0 ? " Errors: " . implode(", ", $errors) : "")
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update discounts. " . implode(", ", $errors)]);
    }

    $conn->close();
    exit;
}

// Original branch/user update code continues here...
$branch = isset($data['branch']) ? strtolower(trim($data['branch'])) : null;
$userId = isset($data['userId']) ? intval($data['userId']) : null;
$newBranch = isset($data['newBranch']) ? trim($data['newBranch']) : null;
$name = isset($data['name']) ? trim($data['name']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;
$new_password = isset($data['password']) ? trim($data['password']) : null;
$confirm_password = isset($data['confirmPassword']) ? trim($data['confirmPassword']) : null;

if (empty($branch) || empty($userId)) {
    die(json_encode(["success" => false, "error" => "No branch or user selected."]));
}

// Prepare updates and parameters
$updates = [];
$params = [];
$types = "";

if (!empty($name)) {
    $updates[] = "name = ?";
    $params[] = $name;
    $types .= "s";
}

if (!empty($email)) {
    $updates[] = "email = ?";
    $params[] = $email;
    $types .= "s";
}

if (!empty($new_password)) {
    if ($new_password !== $confirm_password) {
        die(json_encode(["success" => false, "error" => "Passwords do not match."]));
    }
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $updates[] = "password = ?";
    $params[] = $hashed_password;
    $types .= "s";
}

if (!empty($newBranch)) {
    $updates[] = "branch = ?";
    $params[] = $newBranch;
    $types .= "s";
}

if (empty($updates)) {
    die(json_encode(["success" => false, "error" => "No fields to update."]));
}

// Add the user ID to the parameters
$params[] = $userId;
$types .= "i";

// Build the query
$query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die(json_encode(["success" => false, "error" => "Prepare failed: " . $conn->error]));
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "User updated successfully."]);
} else {
    echo json_encode(["success" => false, "error" => "Update failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>