<?php
$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "buddy_db";

// Use the correct variable names here
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?>