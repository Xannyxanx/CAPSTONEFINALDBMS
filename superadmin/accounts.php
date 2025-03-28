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

    file_put_contents('post_data.log', print_r($_POST, true));
    // Retrieve the values from the POST request
    $cashierName = $_POST['cashierName'];
    $username = $_POST['username'];
    $pin = $_POST['pin'];
    $branch = $_POST['branch'];
    
    // Check if any of the fields are empty
    if (empty($cashierName)) {
        echo json_encode(["success" => false, "message" => "Cashier name is required."]);
        exit();
    }

    // Determine the table based on the branch
    if ($branch == "Dapitan") {
        $table = "dapitan_users";
    } else {
        $table = "espana_users";
    }

    // Build the query based on the fields to update
    $query = "UPDATE $table SET ";
    $params = [];
    $types = "";

    if (!empty($pin)) {
        $query .= "pin = ?, ";
        $params[] = $pin;
        $types .= "s";
    }

    if (!empty($username)) {
        $query .= "username = ?, ";
        $params[] = $username;
        $types .= "s";
    }

    // Remove the trailing comma and space
    $query = rtrim($query, ", ");
    $query .= " WHERE name = ?";
    $params[] = $cashierName;
    $types .= "s";

    $stmt = $conn->prepare($query);

    // Bind parameters to the query
    $stmt->bind_param($types, ...$params);

    // Execute the query and check if it was successful
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Data updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update data."]);
    }

    // Close the statement and the database connection
    $stmt->close();
    $conn->close();

} else {
    // If the request is not POST, return an error
    echo json_encode(["error" => "Invalid request method. Only POST is allowed."]);
}
?>