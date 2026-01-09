<?php
session_start();
include(__DIR__ . '/../db.php');


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("No ID provided.");
}

$adoption_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT legal_id_image FROM adoption_application WHERE adoption_id = ?");
$stmt->bind_param("i", $adoption_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($image);
$stmt->fetch();
$stmt->close();

if (!$image) {
    die("No image found.");
}

header("Content-Type: image/jpeg"); 
echo $image;
