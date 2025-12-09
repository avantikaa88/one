<?php
session_start();
include(__DIR__ . '/../db.php');

// Initialize filter array
$where = ["p.status = 'Available'"];
$params = [];
$types = '';

// Handle filters
if (!empty($_GET['name'])) {
    $where[] = "p.name LIKE ?";
    $params[] = '%' . $_GET['name'] . '%';
    $types .= 's';
}

if (!empty($_GET['breed'])) {
    $where[] = "pt.breed = ?";
    $params[] = $_GET['breed'];
    $types .= 's';
}

if (!empty($_GET['species'])) {
    $where[] = "pt.species = ?";
    $params[] = $_GET['species'];
    $types .= 's';
}

if (!empty($_GET['size'])) {
    $where[] = "pt.size = ?";
    $params[] = $_GET['size'];
    $types .= 's';
}

// Build SQL query (REMOVED pet_shop)
$sql = "
SELECT 
    p.pet_id, 
    p.name, 
    p.status,
    pt.species, 
    pt.breed, 
    pt.size, 
    pt.life_span
FROM pet p
JOIN pet_type pt ON p.type_id = pt.type_id
WHERE " . implode(" AND ", $where);

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buddy | Browse Pets</title>
<link rel="stylesheet" href="pet.css">
<link rel="stylesheet" href="/index/stylee.css">
<style>
.pet-card-link { text-decoration: none; color: inherit; display: block; transition: transform 0.2s; }
.pet-card-link:hover .pet-card { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
.pet-card { cursor: pointer; transition: all 0.3s ease; padding: 10px; border: 1px solid #ccc; border-radius: 10px; text-align: center; margin-bottom: 15px; }
.pet-card img { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; }
.sidebar form select, .sidebar form input { width: 100%; margin-bottom: 10px; padding: 5px; }
.sidebar form button { width: 100%; padding: 10px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
.sidebar form button:hover { background-color: #45a049; }
.pet-grid {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  gap: 25px;
  flex-wrap: wrap;
}
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
        <li><a href="../user/User_dashboard.php">Dashboard</a></li>
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

<div class="container">
  <div class="sidebar">
    <div class="filter-header"><h2>Find Your Bestfriend</h2></div>

    <form class="filters" method="GET" action="">

        <label>Animal</label>
        <select name="species">
            <option value="">Any</option>
            <option value="Cat" <?= (!empty($_GET['species']) && $_GET['species']=='Cat')?'selected':'' ?>>Cat</option>
            <option value="Dog" <?= (!empty($_GET['species']) && $_GET['species']=='Dog')?'selected':'' ?>>Dog</option>
        </select>

        <label>Size</label>
        <select name="size">
            <option value="">Any</option>
            <option value="Small" <?= (!empty($_GET['size']) && $_GET['size']=='Small')?'selected':'' ?>>Small</option>
            <option value="Medium" <?= (!empty($_GET['size']) && $_GET['size']=='Medium')?'selected':'' ?>>Medium</option>
            <option value="Large" <?= (!empty($_GET['size']) && $_GET['size']=='Large')?'selected':'' ?>>Large</option>
        </select>

        <label>Breed</label>
        <input list="breed-list" name="breed" placeholder="Type or select breed" 
               value="<?= !empty($_GET['breed']) ? htmlspecialchars($_GET['breed']) : '' ?>">
        <datalist id="breed-list">
        <?php
        $breed_query = $conn->query("SELECT DISTINCT breed FROM pet_type ORDER BY breed");
        while ($row = $breed_query->fetch_assoc()) {
            echo "<option value='{$row['breed']}'>";
        }
        ?>
        </datalist>

        <label>Pet Name</label>
        <input type="text" name="name" placeholder="Search Name..." 
               value="<?= !empty($_GET['name'])?htmlspecialchars($_GET['name']):'' ?>">

        <button type="submit">Filter</button>
        <a href="pet.php" style="display: block; margin-top: 10px; text-align: center;">Clear Filters</a>
    </form>
  </div>

<section class="pet-grid">
<?php
if ($result->num_rows > 0) {
    while ($pet = $result->fetch_assoc()) {
        echo '<a href="pet_details.php?id='.$pet['pet_id'].'" class="pet-card-link">';
        echo '<div class="pet-card">';
        echo '<img src="../picture/happy.png" alt="'.htmlspecialchars($pet['name']).'">';
        echo '<h3>'.htmlspecialchars($pet['name']).'</h3>';
        echo '<p>'.htmlspecialchars($pet['species']).' - '.htmlspecialchars($pet['breed']).'</p>';
        echo '<p>Size: '.htmlspecialchars($pet['size']).'</p>';
        echo '<p>Life Span: '.htmlspecialchars($pet['life_span']).'</p>';
        echo '<p>Status: '.htmlspecialchars($pet['status']).'</p>';
        echo '</div>';
        echo '</a>';
    }
} else {
    echo '<p style="width:100%; text-align:center; padding:20px;">No pets found matching your criteria.</p>';
}
?>
</section>

</div>
</body>
</html>

<?php $conn->close(); ?>
