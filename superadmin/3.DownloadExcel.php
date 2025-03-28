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
$branch = isset($_GET['branch']) ? strtolower(trim($_GET['branch'])) : 'all'; // Get the selected branch
$query = isset($_GET['query']) ? trim($_GET['query']) : ''; // Get the search query (optional)

if (empty($date)) {
    die("No date provided.");
}

// Ensure the table name exists in the database (basic sanitization)
$table_check_query = "SHOW TABLES LIKE '$date'";
$table_check_result = $conn->query($table_check_query);

if ($table_check_result->num_rows == 0) {
    die("Invalid table name.");
}

// Build the query to fetch data
$sql = "SELECT * FROM `$date`";
$conditions = [];

// Add branch filter if a specific branch is selected
if ($branch !== 'all') {
    $conditions[] = "LOWER(branch) = '$branch'";
}

// Add search query filter if provided
if (!empty($query)) {
    $query = $conn->real_escape_string($query);
    $conditions[] = "(ID LIKE '%$query%' OR name LIKE '%$query%' OR citizen LIKE '%$query%' OR food LIKE '%$query%' OR cashier LIKE '%$query%')";
}

// Append conditions to the SQL query
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("No records found for the selected criteria.");
}

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set column headers
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Name');
$sheet->setCellValue('C1', 'Citizen Type');
$sheet->setCellValue('D1', 'Food');
$sheet->setCellValue('E1', 'Scan Date');
$sheet->setCellValue('F1', 'Time');
$sheet->setCellValue('G1', 'Cashier');
$sheet->setCellValue('H1', 'Branch');
$sheet->setCellValue('I1', 'Discount Percentage');
$sheet->setCellValue('J1', 'Price');
$sheet->setCellValue('K1', 'Discounted Price');
$sheet->setCellValue('L1', 'Control Number');

// Apply header styles
$headerStyle = [
    'font' => [
        'bold' => true, // Set font to bold
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center the text horizontally
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center the text vertically
    ]
];
$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

// Adjust column widths
$columns = range('A', 'L');
foreach ($columns as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true); // Automatically adjust column width
}

// Center all content in the columns
$contentStyle = [
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, // Center the text horizontally
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, // Center the text vertically
    ]
];
$sheet->getStyle('A:L')->applyFromArray($contentStyle);

// Populate data rows
$row = 2;
while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue("A$row", $data['ID']);
    $sheet->setCellValue("B$row", $data['name']);
    $sheet->setCellValue("C$row", $data['citizen']);
    $sheet->setCellValue("D$row", $data['food']);
    $sheet->setCellValue("E$row", $data['date']);
    $sheet->setCellValue("F$row", $data['time']);
    $sheet->setCellValue("G$row", $data['cashier']);
    $sheet->setCellValue("H$row", $data['branch']);
    $sheet->setCellValue("I$row", $data['discount_percentage']);
    $sheet->setCellValue("J$row", $data['price']);
    $sheet->setCellValue("K$row", $data['discounted_price']);
    $sheet->setCellValue("L$row", $data['control_number']);
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
