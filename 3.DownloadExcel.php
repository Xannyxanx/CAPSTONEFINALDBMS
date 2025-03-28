<?php
require 'vendor/autoload.php'; // Load PhpSpreadsheet library

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$host = "localhost";
$user = "root";  
$pass = "";
$db = "archived";  

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the date parameter is valid
$date = isset($_GET['date']) ? $_GET['date'] : '';
if (empty($date)) {
    die("No date provided.");
}

// Ensure the table name exists in the database (basic sanitization)
$query = "SHOW TABLES LIKE '$date'";
$table_check_result = $conn->query($query);

if ($table_check_result->num_rows == 0) {
    die("Invalid table name.");
}

// Query the selected table
$query = "SELECT * FROM `$date`";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    die("No records found for the selected date.");
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Name');
$sheet->setCellValue('C1', 'Citizen Type');
$sheet->setCellValue('D1', 'Food');
$sheet->setCellValue('E1', 'Scan Date');
$sheet->setCellValue('F1', 'Time');
$sheet->setCellValue('G1', 'Cashier');

$headerStyle = [
    'font' => [
        'bold' => true, // Set font to bold
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center the text horizontally
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center the text vertically
    ]
];

// Apply the style to header cells
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

$row = 2;
while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue("A$row", $data['ID']);
    $sheet->setCellValue("B$row", $data['name']);
    $sheet->setCellValue("C$row", $data['citizen']);
    $sheet->setCellValue("D$row", $data['food']);
    $sheet->setCellValue("E$row", $data['date']);
    $sheet->setCellValue("F$row", $data['time']);
    $sheet->setCellValue("G$row", $data['cashier']);
    $row++;
}

// Create writer and output
$writer = new Xlsx($spreadsheet);
$filename = "$date.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$writer->save("php://output");

$conn->close();
?>
