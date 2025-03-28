<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare the image file
$imagePath = 'CAT.jpg';
$imageData = file_get_contents($imagePath);
$imageData = mysqli_real_escape_string($conn, $imageData);

// Update the image in the table for a specific ID

$sql = "UPDATE `dapitancustomers` SET customer_ID = '$imageData'";

if ($conn->query($sql) === TRUE) {
    echo "Image updated successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>