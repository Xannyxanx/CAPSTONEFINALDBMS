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
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die(json_encode(["error" => "Invalid request method."]));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(["error" => "User not logged in."]));
}

$user_id = $_SESSION['user_id'];


$data = json_decode(file_get_contents("php://input"), true);
$name = isset($data['name']) ? trim($data['name']) : null;
$email = isset($data['email']) ? trim($data['email']) : null;
$new_password = isset($data['password']) ? trim($data['password']) : null;
$confirm_password = isset($data['confirmPassword']) ? trim($data['confirmPassword']) : null;


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
        die(json_encode(["error" => "Passwords do not match."]));
    }
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $updates[] = "password = ?";
    $params[] = $hashed_password;
    $types .= "s";
}


if (empty($updates)) {
    die(json_encode(["error" => "No fields to update."]));
}


$query = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
$params[] = $user_id;
$types .= "i";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["success" => "User updated successfully."]);
} else {
    echo json_encode(["error" => "Update failed: " . $stmt->error]);
}


$stmt->close();
$conn->close();

?>
