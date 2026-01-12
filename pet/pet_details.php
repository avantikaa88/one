<?php
session_start();
include(__DIR__ . '/../db.php');

// Validate Pet ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Pet ID is required');
}

$pet_id = intval($_GET['id']); 

// Fetch pet details
$stmt = $conn->prepare("
    SELECT p.*, pt.species, pt.breed AS type_breed, pt.size, pt.life_span
    FROM pet p
    LEFT JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE p.pet_id = ? AND LOWER(p.status) = 'available'
");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) die('Pet not found');

$pet = $result->fetch_assoc();

// Handle image
$defaultImg = '../picture/happy.png';
$imageSrc = $defaultImg; // default
if (!empty($pet['image'])) {
    $imgPath = trim($pet['image']);
    // If it's a URL
    if (filter_var($imgPath, FILTER_VALIDATE_URL)) {
        $imageSrc = $imgPath;
    } else {
        // Check if file exists
        $fullPath = __DIR__ . '/../' . $imgPath;
        if (file_exists($fullPath)) {
            $imageSrc = '../' . ltrim($imgPath, '/');
        }
    }
}

// Calculate age
$age = 'Unknown';
if (!empty($pet['dob']) && $pet['dob'] !== '0000-00-00') {
    $dob = new DateTime($pet['dob']);
    $today = new DateTime();
    $age = $today->diff($dob)->y . ' years';
}

// Added date
$addedDate = !empty($pet['created_at']) && $pet['created_at'] !== '0000-00-00 00:00:00'
             ? date('F j, Y', strtotime($pet['created_at']))
             : 'Unknown';
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
.pet-image img { width: 100%; max-height: 500px; object-fit: contain; border-radius: 10px; }
.info h1 { font-size: 2.5rem; margin-bottom: 20px; }
.info table { width: 100%; border-collapse: collapse; }
.info td { padding: 10px 0; border-bottom: 1px solid #eee; }
.info td:first-child { width: 150px; font-weight: 600; color: #555; }
.status-available { color: #4CAF50; font-weight: 600; }
.buttons { margin-top: 25px; display: flex; gap: 15px; flex-wrap: wrap; }
.button { padding: 12px 30px; border: none; border-radius: 7px; cursor: pointer; font-size: 1rem; font-weight: 600; transition: 0.3s; text-decoration: none; display: inline-block; }
.button-adopt { background-color: #4CAF50; color: white; }
.button-adopt:hover { background-color: #45a049; }
</style>
</head>
<body>

<header>
    <div class="logo">
        <img src="../picture/Group.png" alt="Buddy Logo">
        <h2>Buddy</h2>
    </div>
    <nav class="navbar">
        <ul>
            <li><a href="../user/User_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="pet.php">Browse Pets</a></li>
            <li><a href="#">Contact</a></li>
        </ul>
    </nav>
    <div class="log">
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
            <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($pet['name']); ?>">
        </div>

        <div class="info">
            <h1><?= htmlspecialchars($pet['name']); ?></h1>
            <table>
                <tr><td>Pet ID:</td><td><?= htmlspecialchars($pet['pet_id']); ?></td></tr>
                <tr><td>Species:</td><td><?= htmlspecialchars($pet['species'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Breed:</td><td><?= htmlspecialchars($pet['type_breed'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Age:</td><td><?= $age ?></td></tr>
                <tr><td>Gender:</td><td><?= htmlspecialchars($pet['gender'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Size:</td><td><?= htmlspecialchars($pet['size'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Life Span:</td><td><?= htmlspecialchars($pet['life_span'] ?? 'Unknown'); ?></td></tr>
                <tr><td>Status:</td><td class="status-available"><?= htmlspecialchars($pet['status']); ?></td></tr>
                <tr><td>Description:</td><td><?= htmlspecialchars($pet['description'] ?? 'No description available'); ?></td></tr>
                <tr><td>Added on:</td><td><?= $addedDate ?></td></tr>
            </table>

            <div class="buttons">
                <a href="adopt_form.php?pet_id=<?= $pet['pet_id']; ?>" class="button button-adopt">
                    Adopt <?= htmlspecialchars($pet['name']); ?>
                </a>
            </div>

        </div>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
