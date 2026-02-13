<?php
session_start();
include(__DIR__ . '/../db.php');

/* -------------------------------
   AUTH CHECK
-------------------------------- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_pets.php");
    exit;
}

/* -------------------------------
   INPUTS
-------------------------------- */
$user_id = $_SESSION['user_id'];
$pet_id  = (int)($_POST['pet_id'] ?? 0);
$reason  = trim($_POST['reason'] ?? '');

if ($pet_id <= 0) {
    die("Invalid submission.");
}

/* -------------------------------
   CHECK PET EXISTS
-------------------------------- */
$petStmt = $conn->prepare("
    SELECT adoption_fee, status 
    FROM pet 
    WHERE pet_id = ?
");
$petStmt->bind_param("i", $pet_id);
$petStmt->execute();
$result = $petStmt->get_result();
$pet = $result->fetch_assoc();
$petStmt->close();

if (!$pet) {
    die("Pet not found.");
}

if ($pet['status'] !== 'Available') {
    die("This pet is not available.");
}

$adoption_fee = $pet['adoption_fee'];

/* -------------------------------
   DUPLICATE CHECK
-------------------------------- */
$check = $conn->prepare("
    SELECT adoption_id 
    FROM adoption_application
    WHERE user_id = ? AND pet_id = ?
");
$check->bind_param("ii", $user_id, $pet_id);
$check->execute();
$exists = $check->get_result()->num_rows;
$check->close();

if ($exists > 0) {
    $_SESSION['error'] = "You already applied for this pet.";
    header("Location: ../User/User_dashboard.php");
    exit;
}

/* -------------------------------
   FILE UPLOAD
-------------------------------- */
if (!isset($_FILES['legal_id_image']) || $_FILES['legal_id_image']['error'] !== 0) {
    die("Legal ID image is required.");
}

$upload_dir = "../uploads/legal_ids/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$ext = strtolower(pathinfo($_FILES['legal_id_image']['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowed)) {
    die("Only JPG, JPEG, PNG allowed.");
}

$file_name = "legal_id_" . time() . "_" . rand(1000,9999) . "." . $ext;
$file_path = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['legal_id_image']['tmp_name'], $file_path)) {
    die("Failed to upload image.");
}

/* -------------------------------
   INSERT APPLICATION
-------------------------------- */
$stmt = $conn->prepare("
    INSERT INTO adoption_application
    (
        user_id,
        pet_id,
        status,
        payment_status,
        payment_amount,
        adoption_fee,
        reason,
        legal_id_image
    )
    VALUES (?, ?, 'Pending', 'Unpaid', 0, ?, ?, ?)
");

$reason_param = ($reason !== '') ? $reason : '';

$stmt->bind_param(
    "iidss",
    $user_id,
    $pet_id,
    $adoption_fee,
    $reason_param,
    $file_name
);

if (!$stmt->execute()) {
    die("Insert failed: " . $stmt->error);
}

$stmt->close();

/* -------------------------------
   SUCCESS
-------------------------------- */
$_SESSION['success'] = "Adoption request submitted successfully.";
header("Location: ../User/User_dashboard.php");
exit;
?>
