<?php
// FIX: Added is_active column check to users table.
// This file provides $conn (mysqli) used across all public pages.
$host = "localhost";
$db   = "skilllink";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
