<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$servername = "localhost";
$username = "root";
$password = "";

$date = date('Y-m-d');  
$archiveDb = "archived";
$customersDb = "callecafe";

$connCustomers = new mysqli($servername, $username, $password, $customersDb);
$connArchive = new mysqli($servername, $username, $password, $archiveDb);

if ($connCustomers->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection to callecafe failed: " . $connCustomers->connect_error]));
}
if ($connArchive->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection to archived failed: " . $connArchive->connect_error]));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get the selected branch from the POST request
$branch = isset($_POST['branch']) ? strtolower(trim($_POST['branch'])) : 'all';

// Log the selected branch for debugging
error_log("Selected Branch: " . $branch);

$tableName = $date;  

// Create the archive table if it doesn't exist
$sqlCreateTable = "
    CREATE TABLE IF NOT EXISTS `$tableName` (
        ID VARCHAR(30) NOT NULL,
        name VARCHAR(150) NOT NULL,
        citizen VARCHAR(15) NOT NULL,
        city VARCHAR(250) NOT NULL,
        food VARCHAR(80) NOT NULL,
        date DATE NOT NULL,
        time VARCHAR(15) NOT NULL,
        cashier VARCHAR(150) NOT NULL,
        branch VARCHAR(15) NOT NULL,
        discount_percentage VARCHAR(10) NOT NULL,
        price VARCHAR(10) NOT NULL,
        discounted_price VARCHAR(10) NOT NULL,
        control_number VARCHAR(30) NOT NULL
    )
";

if (!$connArchive->query($sqlCreateTable)) {
    die(json_encode(["status" => "error", "message" => "Error creating table: " . $connArchive->error]));
}

// Log the SQL create table query for debugging
error_log("SQL Create Table: " . $sqlCreateTable);

// Prepare the SQL query to transfer data based on the selected branch
if ($branch === 'all') {
    // Transfer all data
    $sqlTransferData = "
        INSERT INTO `$archiveDb`.`$tableName` (ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number)
        SELECT ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number FROM `$customersDb`.`dapitancustomers`
    ";
    $sqlDeleteData = "DELETE FROM `$customersDb`.`dapitancustomers`";
} else {
    // Transfer data for the selected branch
    $sqlTransferData = "
        INSERT INTO `$archiveDb`.`$tableName` (ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number)
        SELECT ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number FROM `$customersDb`.`dapitancustomers`
        WHERE LOWER(branch) = '$branch'
    ";
    $sqlDeleteData = "DELETE FROM `$customersDb`.`dapitancustomers` WHERE LOWER(branch) = '$branch'";
}

// Log the SQL transfer query for debugging
error_log("SQL Transfer Data: " . $sqlTransferData);

// Execute the transfer query
if (!$connArchive->query($sqlTransferData)) {
    die(json_encode(["status" => "error", "message" => "Error transferring data: " . $connArchive->error]));
}

// Execute the delete query
if (!$connCustomers->query($sqlDeleteData)) {
    die(json_encode(["status" => "error", "message" => "Error deleting data: " . $connCustomers->error]));
}

$connCustomers->close();
$connArchive->close();

echo json_encode(["status" => "success", "message" => "Data archived successfully."]);
?>
