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
    die(json_encode(["status" => "error", "message" => "Connection to archived_dapitan failed: " . $connArchive->connect_error]));
}

if (!isset($_SESSION['user_id'])) {
    header("Location: 0.login.html"); 
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$branch = $_SESSION['branch'];


$tableName = $date;  


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
        price varchar(10) NOT NULL,
        discounted_price varchar(10) NOT NULL,
        control_number varchar(30) NOT NULL
    )
";

if (!$connArchive->query($sqlCreateTable)) {
    die(json_encode(["status" => "error", "message" => "Error creating table: " . $connArchive->error]));
}

// Add Debugging Statements: Log the SQL queries and any errors
error_log("SQL Create Table: " . $sqlCreateTable);


    $sqlTransferData = "
        INSERT INTO `$archiveDb`.`$tableName` (ID, name, city, citizen, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number)
        SELECT ID, name, citizen, city, food, date, time, cashier, branch, discount_percentage, price, discounted_price, control_number FROM `$customersDb`.`dapitancustomers`
        WHERE branch = '$branch'
    ";


if (!$connArchive->query($sqlTransferData)) {
    die(json_encode(["status" => "error", "message" => "Error transferring data: " . $connArchive->error]));
}

$sqlDeleteData = "DELETE FROM `$customersDb`.`dapitancustomers` where branch = '$branch'";

if (!$connCustomers->query($sqlDeleteData)) {
    die(json_encode(["status" => "error", "message" => "Error deleting data: " . $connCustomers->error]));
}


$connCustomers->close();
$connArchive->close();

echo json_encode(["status" => "success", "message" => "Data archived successfully."]);
?>
