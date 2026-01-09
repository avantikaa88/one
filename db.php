<?php
$servername = "localhost";
$username = "root";      // default XAMPP username
$password = "1234";          // default XAMPP password is empty
$dbname = "fakebuddy_db"; // your database

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
