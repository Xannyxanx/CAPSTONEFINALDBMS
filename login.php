<?php
header('Content-Type: application/json');
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;

// Database connection parameters
$host = "localhost"; // or your server's IP address if not using localhost
$db = "callecafe";
$dbUsername = "root"; // your MySQL username
$dbPassword = ""; // your MySQL password

$secretKey = "SK7BC9BgCm"; // Replace with your secret key

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $dbUsername, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch input data (e.g., from an HTTP POST request)
$inputPin = $_POST['pin'] ?? '';
$username = $_POST['username'] ?? '';

if (empty($inputPin)) {
    echo json_encode(['success' => false, 'message' => 'PIN is required']);
    exit;
}

// Prepare and execute the query
try {
        $stmt = $pdo->prepare("SELECT * FROM cashier_users WHERE pin = :pin && username = :username && status = 'active'");
        $stmt->execute(['pin' => $inputPin, 'username' => $username]);

        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($account) {
            // Successful sign-in from espana_users
            $payload = [
                'user_id' => $account['ID'],
                'branch' => $account['branch'],
                'name' => $account['name'],
                'exp' => time() + 120
            ];
            $jwt = JWT::encode($payload, $secretKey, 'HS256');

            echo json_encode([
                'success' => true,
                'message' => 'Sign-in successful',
                'token' => $jwt,
                'user' => [
                    'username' => $account['username'],
                    'branch' => $account['branch'],
                    'name' => $account['name']
                ]
            ]);
        } else {
            // Invalid PIN
            echo json_encode([
                'success' => false,
                'message' => 'Invalid PIN'
            ]);
        }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>