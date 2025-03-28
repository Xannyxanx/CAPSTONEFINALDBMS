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

if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$branch = $_SESSION['branch'];

$sql = "SELECT ID, name, username, pin FROM cashier_requests where branch = '$branch'";

$result = $conn->query($sql);
$request = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $request[] = $row;
    }
}

$conn->close();


header('Content-Type: application/json');
echo json_encode($request);
?>