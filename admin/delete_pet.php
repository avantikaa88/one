<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

$pet_id = (int)$_GET['id'];
$conn->query("DELETE FROM pet WHERE pet_id = $pet_id");
header("Location: manage_pets.php");
exit;
?>