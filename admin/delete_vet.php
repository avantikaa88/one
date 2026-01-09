<?php
session_start();
include(__DIR__ . '/../db.php');

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Check if vet_id is provided
if (!isset($_GET['id'])) {
    header("Location: manage_vet.php");
    exit;
}

$vet_id = (int)$_GET['id'];

// Delete the vet from the database
$stmt = $conn->prepare("DELETE FROM vet WHERE vet_id = ?");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$stmt->close();

// Redirect back to the vet list with a success message
header("Location: manage_vet.php?msg=deleted");
exit;
?>
