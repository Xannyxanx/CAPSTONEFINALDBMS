<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "callecafe";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get users with non-hashed passwords
$result = $conn->query("SELECT id, password FROM users");
while ($row = $result->fetch_assoc()) {
    if (!password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
        continue; // Skip if already hashed
    }
    
    $hashed_password = password_hash($row['password'], PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $row['id']);
    $update_stmt->execute();
}
echo "All passwords updated!";
?>
