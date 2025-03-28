<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Security: Only allow specific endpoints
$allowed_endpoints = [
    'get_branch_user.php',
    'registeredAccounts.php'
];

$endpoint = $_GET['endpoint'];
if (!in_array($endpoint, $allowed_endpoints)) {
    echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
    exit;
}

$target_url = 'https://cafedbms.free.nf/mobile/' . $endpoint;

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for testing!

// Forward GET parameters
if (!empty($_GET)) {
    $target_url .= '?' . http_build_query($_GET);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
}

// Forward headers (optional)
$headers = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) !== 'host') {
        $headers[] = "$name: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Handle errors
if ($response === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Proxy error: ' . curl_error($ch)
    ]);
    exit;
}

// Return the response
http_response_code($http_code);
echo $response;

curl_close($ch);