<?php
header("Content-Type: application/json");
$host = "localhost";
$db_name = "callecafe";
$username = "root";
$password = "";

try {
    // Create connection
    $conn = new mysqli($host, $username, $password, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("SELECT category, percentage FROM discounts");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $discounts = [];
    
    while ($row = $result->fetch_assoc()) {
        $discounts[$row['category']] = $row['percentage'];
    }
    
    echo json_encode([
        'status' => 'success',
        'discounts' => $discounts
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch discounts: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>