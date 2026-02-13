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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username']);
    $email           = trim($_POST['email']);
    $specialization  = trim($_POST['specialization']);
    $licence_no      = trim($_POST['licence_no']);
    $clinic_location = trim($_POST['clinic_location']);
    $availability    = trim($_POST['availability']);
    $experience      = (int)$_POST['experience'];
    $contact_info    = trim($_POST['contact_info']);

    $stmt = $conn->prepare("
        UPDATE vet SET 
            username = ?, email = ?, specialization = ?, licence_no = ?, 
            clinic_location = ?, availability = ?, experience = ?, contact_info = ?
        WHERE vet_id = ?
    ");
    $stmt->bind_param(
        "ssssssisi",
        $username, $email, $specialization, $licence_no,
        $clinic_location, $availability, $experience, $contact_info,
        $vet_id
    );
    $stmt->execute();
    $stmt->close();

    header("Location: manage_vet.php");
    exit;
}

// Fetch current vet data
$stmt = $conn->prepare("SELECT * FROM vet WHERE vet_id = ?");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$result = $stmt->get_result();
$vet = $result->fetch_assoc();
$stmt->close();

if (!$vet) {
    die("Vet not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Vet</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .form-box { max-width: 500px; margin: 0 auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-box input, .form-box button { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        .form-box button { background: #8B4513; color: white; border: none; font-weight: bold; cursor: pointer; }
        .form-box button:hover { background: #a0522d; }
        a.back-link { display: inline-block; margin-top: 10px; color: #8B4513; text-decoration: none; }
    </style>
</head>
<body>

<div class="main-content">
<h2>Edit Vet</h2>

<form method="post" class="form-box">
    <input type="text" name="username" value="<?= htmlspecialchars($vet['username']) ?>" placeholder="Vet Name" required>
    <input type="email" name="email" value="<?= htmlspecialchars($vet['email']) ?>" placeholder="Email" required>
    <input type="text" name="specialization" value="<?= htmlspecialchars($vet['specialization']) ?>" placeholder="Specialization" required>
    <input type="text" name="licence_no" value="<?= htmlspecialchars($vet['licence_no']) ?>" placeholder="Licence No" required>
    <input type="text" name="clinic_location" value="<?= htmlspecialchars($vet['clinic_location']) ?>" placeholder="Clinic Location" required>
    <input type="text" name="availability" value="<?= htmlspecialchars($vet['availability']) ?>" placeholder="Availability (e.g. Mon–Fri)">
    <input type="number" name="experience" value="<?= $vet['experience'] ?>" placeholder="Experience (years)" min="0">
    <input type="text" name="contact_info" value="<?= htmlspecialchars($vet['contact_info']) ?>" placeholder="Contact Info" required>

    <button type="submit">Update Vet</button>
</form>

<a href="manage_vet.php" class="back-link">← Back to Vet List</a>
</div>
</body>
</html>
