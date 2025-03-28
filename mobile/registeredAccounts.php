<?php
header('Content-Type: application/json');

// Database connection parameters
$host = "localhost"; // or your server's IP address if not using localhost
$db_name = "callecafe";
$username = "root"; // your MySQL username
$password = ""; // your MySQL password

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $db_name);

// Check for a connection error
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Retrieve the values from the POST request
    $branch = $_POST['branch'];
    $name = $_POST['name'];
    $pin = $_POST['pin'];
    $username = $_POST['username'];
    // Check if any of the fields are empty
    if (empty($branch) || empty($name) || empty($pin) || empty($username)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit();
    }

    $query = "INSERT INTO cashier_requests (name, username, pin, branch) VALUES (?, ?, ?, ?)";
 
    
    $stmt = $conn->prepare($query);

    // Bind parameters to the query
    $stmt->bind_param("ssss", $name, $username, $pin, $branch);

    // Execute the query and check if it was successful
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data inserted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to insert data."]);
    }

    // Close the statement and the database connection
    $stmt->close();
    $conn->close();

} else {
    // If the request is not POST, return an error
    echo json_encode(["error" => "Invalid request method. Only POST is allowed."]);
}
?>
