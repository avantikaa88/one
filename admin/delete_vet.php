<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}


if (!isset($_GET['id'])) {
    header("Location: manage_vet.php");
    exit;
}

$vet_id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM vet WHERE vet_id = ?");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$stmt->close();

header("Location: manage_vet.php?msg=deleted");
exit;
?>
