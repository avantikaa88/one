<?php
session_start();
include(__DIR__ . '/../db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

// Get pet ID from URL
$pet_id = intval($_GET['pet_id'] ?? 0);
if ($pet_id <= 0) die("Pet ID is required.");

// Fetch pet details
$stmt = $conn->prepare("
    SELECT p.pet_id, p.name, p.status, p.adoption_fee, pt.breed, pt.species
    FROM pet p
    JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE p.pet_id = ?
");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Pet not found.");
$pet = $result->fetch_assoc();
$stmt->close();

// Fetch logged-in user details
$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name, gender, address FROM users WHERE user_id = $user_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adopt <?= htmlspecialchars($pet['name']); ?></title>
<style>
body { font-family: Arial; padding:20px; background:#f5f5f5; }
form { max-width:600px; background:#fff; padding:20px; border-radius:10px; margin:auto; box-shadow:0 0 10px rgba(0,0,0,0.1); }
input, textarea { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc; }
.submit { padding:10px 20px; background:#4CAF50; color:white; border:none; border-radius:5px; cursor:pointer; }
.submit:hover { background:#45a049; }
</style>
</head>
<body>

<h1>Adoption Form: <?= htmlspecialchars($pet['name']); ?></h1>

<form action="submit_adoption.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="pet_id" value="<?= $pet['pet_id']; ?>">

    <label>Your Full Name:</label>
    <input type="text" value="<?= htmlspecialchars($user['name']); ?>" disabled>

    <label>Gender:</label>
    <input type="text" value="<?= htmlspecialchars($user['gender']); ?>" disabled>

    <label>Address:</label>
    <textarea disabled><?= htmlspecialchars($user['address']); ?></textarea>

    <label>Adoption Fee (USD):</label>
    <input type="text" value="<?= number_format($pet['adoption_fee'], 2); ?>" disabled>

    <label>Upload Your Legal ID (Image):</label>
    <input type="file" name="legal_id_image" accept="image/*" required>

    <label>Why do you want to adopt this pet?</label>
    <textarea name="reason" required></textarea>

    <button type="submit">Submit Adoption Request</button>
</form>

</body>
</html>
