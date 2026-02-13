<?php
session_start();
include(__DIR__ . '/../db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

// Get pet ID
$pet_id = (int)($_GET['pet_id'] ?? 0);
if ($pet_id <= 0) {
    die("Pet ID is required.");
}

// Fetch pet info
$stmt = $conn->prepare("
    SELECT pet_id, name, adoption_fee
    FROM pet
    WHERE pet_id = ?
");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pet) die("Pet not found.");

// Fetch user info
$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name, gender, address FROM users WHERE user_id = $user_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Adopt <?= htmlspecialchars($pet['name']) ?></title>
<style>
body { font-family: Arial, sans-serif; background: #f2f2f2; padding: 20px; }
form { background: #fff; padding: 20px; max-width: 500px; margin: auto; border-radius: 5px; }
label { font-weight: bold; display: block; margin-top: 10px; }
input, textarea { width: 100%; padding: 8px; margin-top: 5px; }
textarea { resize: vertical; }
.notice { margin-top: 8px; padding: 10px; background: #fff3cd; color: #856404; font-size: 14px; }
button { margin-top: 15px; padding: 10px; width: 100%; background: #4CAF50; color: white; border: none; cursor: pointer; }
button:hover { background: #45a049; }
</style>
</head>
<body>

<h2 style="text-align:center;">Adopt <?= htmlspecialchars($pet['name']) ?></h2>

<form method="POST" action="submit_adoption.php" enctype="multipart/form-data">
    <input type="hidden" name="pet_id" value="<?= $pet['pet_id'] ?>">

    <label>Name</label>
    <input type="text" value="<?= htmlspecialchars($user['name']) ?>" disabled>

    <label>Gender</label>
    <input type="text" value="<?= htmlspecialchars($user['gender']) ?>" disabled>

    <label>Address</label>
    <textarea disabled><?= htmlspecialchars($user['address']) ?></textarea>

    <label>Adoption Fee</label>
    <input type="text" value="<?= number_format($pet['adoption_fee'], 2) ?>" disabled>

    <label>Upload Legal ID (Image)</label>
    <input type="file" name="legal_id_image" accept="image/*" required>

    <div class="notice">
        Users are required to bring their legal ID with them when they come physically to adopt the pet.
    </div>

    <!-- Reason optional -->
    <label>Reason for Adoption (Optional)</label>
    <textarea name="reason" placeholder="You can leave this empty if you want"></textarea>

    <button type="submit">Submit Adoption Request</button>
</form>

</body>
</html>
