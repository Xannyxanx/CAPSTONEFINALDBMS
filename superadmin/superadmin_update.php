<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$servername = "localhost";
$username = "root";
$password = "";
$customersDb = "callecafe";

$conn = new mysqli($servername, $username, $password, $customersDb);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

error_log("Received POST data: " . print_r($_POST, true));

if (!isset($_POST['id']) || empty($_POST['id']) || !isset($_POST['pin']) || empty($_POST['pin']) || !isset($_POST['username']) || empty($_POST['username']) || !isset($_POST['branch']) || empty($_POST['branch'])) {
    error_log("Missing required fields: " . print_r($_POST, true));
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit();
}

$userID = intval($_POST['id']);
$newPin = $_POST['pin'];
$newUsername = $_POST['username'];
$newBranch = strtolower(trim($_POST['branch']));

// Determine the current branch table
$currentBranch = $_SESSION['branch'];
$currentTable = ($currentBranch === "Dapitan") ? "dapitan_users" : "espana_users";

// Determine the target branch table
$targetTable = ($newBranch === "dapitan") ? "dapitan_users" : "espana_users";

if ($currentTable === $targetTable) {
    // Update the user in the same table
    $sqlUpdate = "UPDATE `$currentTable` SET pin = ?, username = ? WHERE ID = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("ssi", $newPin, $newUsername, $userID);

    if (!$stmt->execute()) {
        error_log("SQL Error (Update): " . $stmt->error);
        echo json_encode(["status" => "error", "message" => "Error updating user: " . $stmt->error]);
        exit();
    }
} else {
    // Move the user to the target table
    // Fetch the user's current data
    $sqlFetch = "SELECT * FROM `$currentTable` WHERE ID = ?";
    $stmtFetch = $conn->prepare($sqlFetch);
    $stmtFetch->bind_param("i", $userID);
    $stmtFetch->execute();
    $result = $stmtFetch->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "User not found in the current branch"]);
        exit();
    }

    $userData = $result->fetch_assoc();

    // Insert the user into the target table
    $sqlInsert = "INSERT INTO `$targetTable` (name, username, pin) VALUES (?, ?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("sss", $userData['name'], $newUsername, $newPin);

    if (!$stmtInsert->execute()) {
        error_log("SQL Error (Insert): " . $stmtInsert->error);
        echo json_encode(["status" => "error", "message" => "Error moving user to the new branch: " . $stmtInsert->error]);
        exit();
    }

    // Delete the user from the current table
    $sqlDelete = "DELETE FROM `$currentTable` WHERE ID = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $userID);

    if (!$stmtDelete->execute()) {
        error_log("SQL Error (Delete): " . $stmtDelete->error);
        echo json_encode(["status" => "error", "message" => "Error deleting user from the current branch: " . $stmtDelete->error]);
        exit();
    }
}

$response = ["status" => "success", "message" => "User updated successfully"];
error_log("Response: " . json_encode($response));

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>