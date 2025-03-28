<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
require 'vendor/autoload.php'; // Make sure this path is correct

// Database connection
$host = "localhost";
$user = "root";  
$pass = "";
$db = "archived";  

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get session variables
$branch = $_SESSION['branch'];

// Get parameters with validation
$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Validate required parameters
if (empty($fromDate) || empty($toDate)) {
    die("Both fromDate and toDate must be provided.");
}

// Create date range
try {
    $startDate = new DateTime($fromDate);
    $endDate = new DateTime($toDate);
    $endDate->modify('+1 day'); // Include the end date
    $dateInterval = new DateInterval('P1D');
    $dateRange = new DatePeriod($startDate, $dateInterval, $endDate);
} catch (Exception $e) {
    die("Invalid date format: " . $e->getMessage());
}

// Collect all records
$allRecords = [];
foreach ($dateRange as $date) {
    $currentDate = $date->format('Y-m-d');
    
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE '$currentDate'")->num_rows > 0;
    if (!$tableExists) continue;
    
    // Build query
    $sql = "SELECT * FROM `$currentDate` WHERE branch = ? AND date = ?";
    $params = [$branch, $currentDate];
    $types = "ss";
    
    if (!empty($query)) {
        $sql .= " AND (ID LIKE ? OR name LIKE ? OR citizen LIKE ? OR food LIKE ? OR cashier LIKE ?)";
        $searchTerm = "%$query%";
        $params = array_merge($params, array_fill(0, 5, $searchTerm));
        $types .= str_repeat("s", 5);
    }
    
    // Use prepared statement
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $allRecords[] = $row;
        }
        $stmt->close();
    }
}

if (empty($allRecords)) {
    die("No records found for the selected criteria.");
}

// Create PDF
try {
    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Calle Cafe');
    $pdf->SetTitle('Archived Records - ' . $fromDate . ' to ' . $toDate);
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins - slightly reduced to accommodate more columns
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    
    // Add a page
    $pdf->AddPage();

    $pdf->Image('images/cafecallelogo.png', 10, 10, 40, 0, 'PNG');

    $pdf->Ln(22);

    // Set font
    $pdf->SetFont('helvetica', 'B', 14);
    
    // Title
    $pdf->Cell(0, 10, 'Calle Cafe Discounted Customers Records from ' . $fromDate . ' to ' . $toDate, 0, 1, 'C');
    $pdf->Ln(5);
    
    // Adjusted column headers and widths to include City
    $pdf->SetFont('helvetica', '', 8);
    $header = ['ID', 'Name', 'Citizen', 'City', 'Food', 'Date', 'Time', 'Cashier', 'Branch', 'Disc%', 'Price', 'Total', 'Control#'];
    
    // Adjusted column widths - reduced some widths to fit all columns
    $w = [
        35,  // ID (reduced from 30)
        50,  // Name 
        20,  // Citizen
        15,  // City
        30,  // Food (reduced from 35)
        15,  // Date
        12,  // Time
        22,  // Cashier
        15,  // Branch
        10,  // Disc%
        12,  // Price
        12,  // Total
        20   // Control# (reduced from 25)
    ];
    
    // Calculate total table width
    $tableWidth = array_sum($w);
    
    // Center the table by setting appropriate X position
    $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
    $startX = ($pageWidth - $tableWidth) / 2 + 12; 
    $pdf->SetX($startX);
    
    // Header with background color
    $pdf->SetFillColor(220, 220, 220);
    $pdf->SetFont('helvetica', 'B', 8); // Explicitly set bold font for the headers
    for ($i = 0; $i < count($header); ++$i) {
        $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
    }
    $pdf->Ln();

    // Reset font for table rows
    $pdf->SetFont('helvetica', '', 8); // Regular font for table rows

    // Data rows
    $pdf->SetFillColor(255, 255, 255);
    $fill = false;
    $rowCount = 0; // Tracks the number of rows on the current page

    foreach ($allRecords as $row) {
        // Check if the row count has reached 18
        if ($rowCount == 18) {
            // Add the closing line for the current page
            $pdf->SetX($startX);
            $pdf->Cell($tableWidth, 0, '', 'T');
            $pdf->Ln(); // Add a line break

            // Add a new page
            $pdf->AddPage();

            // Add the logo again
            $pdf->Image('admin_images/cafecallelogo.png', 10, 10, 40, 0, 'PNG');
            $pdf->Ln(35); // Add space below the logo

            // Add the title again
            $pdf->SetFont('helvetica', 'B', 14); // Reset font size and style for the title
            $pdf->Cell(0, 10, 'Calle Cafe Discounted Customers Records from ' . $fromDate . ' to ' . $toDate, 0, 1, 'C');
            $pdf->Ln(10); // Add space below the title

            // Reset the row count for the new page
            $rowCount = 0;

            // Add the table header again
            $pdf->SetX($startX);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetFont('helvetica', 'B', 8); // Bold font for table headers
            for ($i = 0; $i < count($header); ++$i) {
                $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
            }
            $pdf->Ln();

            // Reset font for table rows
            $pdf->SetFont('helvetica', '', 8); // Regular font for table rows
        }

        // Set X position for each row to maintain centering
        $pdf->SetX($startX);

        // Alternate row colors
        $fill = !$fill;
        $pdf->SetFillColor($fill ? 240 : 255, $fill ? 240 : 255, $fill ? 240 : 255);

        // Format cells with proper alignment
        $pdf->Cell($w[0], 6, $row['ID'], 'LR', 0, 'C', true);
        $pdf->Cell($w[1], 6, $row['name'], 'LR', 0, 'L', true);
        $pdf->Cell($w[2], 6, $row['citizen'], 'LR', 0, 'C', true);
        $pdf->Cell($w[3], 6, $row['city'], 'LR', 0, 'C', true); // City column
        $pdf->Cell($w[4], 6, $row['food'], 'LR', 0, 'L', true); // Reduced character limit
        $pdf->Cell($w[5], 6, $row['date'], 'LR', 0, 'C', true);
        $pdf->Cell($w[6], 6, $row['time'], 'LR', 0, 'C', true);
        $pdf->Cell($w[7], 6, substr($row['cashier'], 0, 12), 'LR', 0, 'L', true); // Reduced character limit
        $pdf->Cell($w[8], 6, $row['branch'], 'LR', 0, 'C', true);
        $pdf->Cell($w[9], 6, $row['discount_percentage'], 'LR', 0, 'C', true);
        $pdf->Cell($w[10], 6, $row['price'], 'LR', 0, 'C', true);
        $pdf->Cell($w[11], 6, $row['discounted_price'], 'LR', 0, 'C', true);
        $pdf->Cell($w[12], 6, $row['control_number'], 'LR', 0, 'C', true);
        $pdf->Ln();

        $rowCount++; // Increment the row count
    }

    // Add the closing line for the last page
    $pdf->SetX($startX);
    $pdf->Cell($tableWidth, 0, '', 'T');
    
    // Output PDF
    $pdfFilename = "Archived_Records_{$fromDate}_to_{$toDate}.pdf";
    $pdf->Output($pdfFilename, 'D');
    
} catch (Exception $e) {
    die("Error generating PDF: " . $e->getMessage());
}

$conn->close();
exit();
?>