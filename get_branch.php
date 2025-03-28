<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['branch'])) {
    echo json_encode(["branch" => "Unknown"]);
    exit();
}

// Return the branch name
echo json_encode(["branch" => $_SESSION['branch']]);
?>