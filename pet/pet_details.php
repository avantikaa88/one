<?php
session_start();
include(__DIR__ . '/../db.php');

// Check if pet ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Pet ID is required');
}

$pet_id = intval($_GET['id']); // force integer

// Fetch pet details securely
$stmt = $conn->prepare("
    SELECT p.*, pt.species, pt.breed AS type_breed, pt.size, pt.life_span
    FROM pet p
    LEFT JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE p.pet_id = ?
");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Pet not found');
}

$pet = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buddy | <?= htmlspecialchars($pet['name']); ?></title>
<link rel="stylesheet" href="../index/stylee.css">
<style>
.pet-details-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.pet-details { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
.pet-image img { width: 100%; height: 400px; object-fit: cover; border-radius: 10px; }
.pet-info h1 { font-size: 2.5rem; margin-bottom: 20px; }
.pet-info table { width: 100%; border-collapse: collapse; }
.pet-info td { padding: 10px 0; border-bottom: 1px solid #eee; }
.pet-info td:first-child { width: 150px; font-weight: 600; color: #555; }
.status-available { color: #4CAF50; font-weight: 600; }
.status-adopted { color: #ff9800; font-weight: 600; }
.action-buttons { margin-top: 25px; display: flex; gap: 15px; flex-wrap: wrap; }
.button { padding: 12px 30px; border: none; border-radius: 7px; cursor: pointer; font-size: 1rem; font-weight: 600; transition: 0.3s; text-decoration: none; display: inline-block; }
.button-adopt { background-color: #4CAF50; color: white; }
.button-adopt:hover { background-color: #45a049; }
.button-contact { background-color: #2196F3; color: white; }
.button-contact:hover { background-color: #0b7dda; }

</style>
</head>
<body>

<header>
    <div class="logo">
        <img src="/picture/Group.png" alt="Buddy Logo">
        <h2>Buddy</h2>
    </div>
    <nav class="navbar">
        <ul>
            <li><a href="pet.php">Browse Pets</a></li>
            <li><a href="../vetf/VET_FORM.html">Vet Services</a></li>
            <li><a href="#">Contact</a></li>
        </ul>
    </nav>
    <div class="nav-buttons">
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="../logout.php" class="login">Logout</a>
        <?php else: ?>
            <a href="../login/login.php" class="login">Login</a>
        <?php endif; ?>
    </div>
</header>

<div class="pet-details-container">
    <div class="pet-details">
        <div class="pet-image">
            <img src="../picture/happy.png" alt="<?= htmlspecialchars($pet['name']); ?>">
        </div>

        <div class="pet-info">
            <h1><?= htmlspecialchars($pet['name']); ?></h1>
            <table>
                <tr><td>Pet ID:</td><td><?= htmlspecialchars($pet['pet_id']); ?></td></tr>
                <tr><td>Species:</td><td><?= htmlspecialchars($pet['species'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Breed:</td><td><?= htmlspecialchars($pet['type_breed'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Age:</td><td><?= htmlspecialchars($pet['age'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Gender:</td><td><?= htmlspecialchars($pet['gender'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Size:</td><td><?= htmlspecialchars($pet['size'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Life Span:</td><td><?= htmlspecialchars($pet['life_span'] ?? 'Unknown'); ?></td></tr>
                <tr>
                    <td>Status:</td>
                    <td class="status-<?= strtolower($pet['status']); ?>">
                        <?= htmlspecialchars($pet['status']); ?>
                    </td>
                </tr>
                <tr><td>Description:</td><td><?= htmlspecialchars($pet['description'] ?? 'No description available'); ?></td></tr>
                <tr><td>Added on:</td><td><?= date('F j, Y', strtotime($pet['created_at'] ?? date('Y-m-d'))); ?></td></tr>
            </table>

            <?php if (strtolower($pet['status']) === 'available'): ?>
            <div class="action-buttons">
                <a href="adopt_form.php?pet_id=<?= $pet['pet_id']; ?>" class="button button-adopt">
                    Adopt <?= htmlspecialchars($pet['name']); ?>
                </a>
                <a href="contact.php?pet_id=<?= $pet['pet_id']; ?>" class="button button-contact">
                    Contact Shop
                </a>
            </div>
            <?php else: ?>
            <p style="margin-top: 20px; font-weight: 600; color: #ff9800;">
                This pet is currently not available for adoption.
            </p>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>