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

$foodCategories = ['Drinks', 'Pasta', 'Pastry'];

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Retrieve the values from the POST request
    $idNumber = $_POST['idNumber'] ?? null;
    $name = $_POST['name'] ?? null;
    $city = $_POST['city'] ?? null;
    $citizenType = $_POST['citizenType'] ?? null;
    $food = $_POST['food'] ?? null;
    $date = $_POST['date'] ?? null;
    $time = $_POST['time'] ?? null;
    $cashierName = $_POST['cashierName'] ?? null;
    $branch = $_POST['branch'] ?? null;
    $discountPercentage = $_POST['discountPercentage'] ?? null;
    $customerID = $_POST['customerID'] ?? null;
    $control_no = $_POST['control_no'];
    $original_price = $_POST['original_price'];
    $total_price = $_POST['total_price'];
    // Check if any of the fields are empty
    $missingFields = [];
    if (empty($idNumber)) $missingFields[] = 'idNumber';
    if (empty($name)) $missingFields[] = 'name';
    if (empty($city)) $missingFields[] = 'city';
    if (empty($citizenType)) $missingFields[] = 'citizenType';
    if (empty($food)) $missingFields[] = 'food';
    if (empty($date)) $missingFields[] = 'date';
    if (empty($time)) $missingFields[] = 'time';
    if (empty($cashierName)) $missingFields[] = 'cashierName';
    if (empty($branch)) $missingFields[] = 'branch';
    if (empty($discountPercentage)) $missingFields[] = 'discountPercentage';

    if (!empty($missingFields)) {
        error_log("Missing fields: " . implode(', ', $missingFields));
        echo json_encode(["success" => false, "message" => "All fields are required. Missing: " . implode(', ', $missingFields)]);
        exit();
    }

    // Normalize the food field by sorting the items alphabetically
    $foodItems = array_unique(explode(',', $food));
    sort($foodItems);
    $normalizedFood = implode(',', $foodItems);

    // Track order history per category
    $categoryHistory = array_fill_keys($foodCategories, []);
    
    // Get all orders for this customer
    $historyQuery = "SELECT food, time FROM dapitancustomers WHERE ID = ? AND name = ? AND city = ?";
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->bind_param("sss", $idNumber, $name, $city);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    
    while ($row = $historyResult->fetch_assoc()) {
        $items = explode(',', $row['food']);
        foreach ($items as $item) {
            $item = trim($item);
            if (in_array($item, $foodCategories)) {
                $categoryHistory[$item][] = date("g:i A", strtotime($row['time']));
            }
        }
    }
    $historyStmt->close();
    
    // Check for exceeded categories
    $blockedCategories = [];
    foreach ($foodItems as $item) {
        $item = trim($item);
        if (in_array($item, $foodCategories) && count($categoryHistory[$item]) >= 3) {
            $blockedCategories[$item] = $categoryHistory[$item];
        }
    }
    
    if (!empty($blockedCategories)) {
        // Get sample order info
        $sampleQuery = "SELECT ID, name, citizen, date, time, branch 
                       FROM dapitancustomers 
                       WHERE ID = ? AND name = ? AND city = ? LIMIT 1";
        $sampleStmt = $conn->prepare($sampleQuery);
        $sampleStmt->bind_param("sss", $idNumber, $name, $city);
        $sampleStmt->execute();
        $sampleResult = $sampleStmt->get_result();
        $orderInfo = $sampleResult->fetch_assoc();
        $sampleStmt->close();
        
        // Format the blocked items message
        $blockedItemsMessage = [];
        foreach ($blockedCategories as $category => $times) {
            $lastTime = array_pop($times);
            $timeList = !empty($times) ? implode(', ', $times).' and '.$lastTime : $lastTime;
            $blockedItemsMessage[] = "$category at $timeList";
        }
        
        // CRITICAL: Use the EXACT format Android expects
        $response = [
            "success" => false,
            "message" => "The following food items have already been availed:\n• " . 
                         implode("\n• ", $blockedItemsMessage) . 
                         "\n\nComplete order information:\nID: {$orderInfo['ID']}\nName: {$orderInfo['name']}\n" .
                         "Citizen Type: {$orderInfo['citizen']}\nDate: {$orderInfo['date']}\n" .
                         "Branch: {$orderInfo['branch']}"
        ];
        echo json_encode($response);
        exit();
    }


    // Insert the new record
    $query = "INSERT INTO dapitancustomers (
        ID, name, citizen, city, food, date, time, 
        cashier, branch, discount_percentage, price,
        discounted_price, control_number
    ) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    // Bind parameters to the query
    $stmt->bind_param("sssssssssssss", $idNumber, $name, $citizenType, $city, $normalizedFood, $date, $time, 
    $cashierName, $branch, $discountPercentage, $original_price, $total_price, $control_no);

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