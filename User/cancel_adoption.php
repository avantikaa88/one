<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

$adoption_id = (int)($_POST['adoption_id'] ?? 0);
$reason = trim($_POST['cancel_reason'] ?? '');
$user_id = $_SESSION['user_id'];

if ($adoption_id <= 0 || $reason === '') {
    die("Invalid request.");
}

$stmt = $conn->prepare("
    UPDATE adoption_application
    SET status = 'Cancelled',
        cancel_reason = ?,
        cancelled_at = NOW()
    WHERE adoption_id = ?
      AND user_id = ?
      AND status = 'Pending'
");
$stmt->bind_param("sii", $reason, $adoption_id, $user_id);
$stmt->execute();

header("Location: adoption_applications.php?cancel=success");
exit;
