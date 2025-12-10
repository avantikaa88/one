<?php
session_start();
include(__DIR__ . '/../db.php');

// Default pets
$where = ["p.status IN ('Available','Adopted')"];
$params = [];
$types = '';

// Filters
if (!empty($_GET['name'])) {
    $where[] = "p.name LIKE ?";
    $params[] = '%' . $_GET['name'] . '%';
    $types .= 's';
}
if (!empty($_GET['species'])) {
    $where[] = "pt.species = ?";
    $params[] = $_GET['species'];
    $types .= 's';
}
if (!empty($_GET['gender'])) {
    $where[] = "p.gender = ?";
    $params[] = $_GET['gender'];
    $types .= 's';
}
if (!empty($_GET['age'])) {
    $where[] = "p.age = ?";
    $params[] = $_GET['age'];
    $types .= 'i';
}

// Query pets (join with pet_type for species)
$sql = "SELECT p.pet_id, p.name, p.gender, p.age, p.status, p.adoption_fee, p.image, pt.species
        FROM pet p
        JOIN pet_type pt ON p.type_id = pt.type_id
        WHERE " . implode(" AND ", $where);

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buddy | Browse Pets</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
/* Global Styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
}

body {
  background-color: #f8f3ef;
  color: #3e2f24;
  min-height: 100vh;
}

/* Header */
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 70px;
  background-color: #efe2d1;
  position: sticky;
  top: 0;
  z-index: 1000;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo img {
  width: 45px;
  height: 40px;
}

.logo h2 {
  font-weight: 700;
  font-size: 22px;
  color: #3e2f24;
}

.navbar ul {
  display: flex;
  list-style: none;
  gap: 35px;
}

.navbar ul li a {
  text-decoration: none;
  color: #3e2f24;
  font-weight: 600;
  transition: color 0.3s;
}

.navbar ul li a:hover {
  color: #ce9a16;
}

.auth-btn {
  padding: 6px 16px;
  border-radius: 5px;
  font-weight: 600;
  text-decoration: none;
  background-color: #3e2f24;
  color: white;
  transition: 0.3s;
}

.auth-btn:hover {
  color: #ce9a16;
}

/* Container and Sidebar */
.container {
  display: flex;
  gap: 20px;
  padding: 30px 70px;
  max-width: 1400px;
  margin: auto;
}

.sidebar {
  width: 250px;
  background: #fff;
  border-radius: 10px;
  padding: 20px;
  position: sticky;
  top: 100px; /* distance from top below header */
  height: fit-content;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.filters label {
  font-weight: 500;
  display: block;
  margin-top: 15px;
}

.filters select,
.filters input {
  width: 100%;
  padding: 7px;
  border-radius: 6px;
  border: 1px solid #ccc;
  margin-top: 8px;
}

.filters button {
  width: 100%;
  padding: 10px;
  margin-top: 15px;
  background-color: #3e2f24;
  color: #fff;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: 0.3s;
}

.filters button:hover {
  color: #ce9a16;
}

.filters a {
  display: block;
  margin-top: 10px;
  text-align: center;
  color: #666;
  text-decoration: none;
}

.filters a:hover {
  color: #ce9a16;
}

/* Pet Grid */
.pet-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 25px;
  justify-content: center;
  flex: 1;
}

/* Pet Cards */
.pet-card-link {
  text-decoration: none;
  color: inherit;
  display: block;
}

.pet-card {
  background-color: #fff;
  border-radius: 16px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  width: 240px;
  text-align: center;
  padding: 15px;
  transition: transform 0.3s, box-shadow 0.3s;
}

.pet-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}

.pet-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  border-radius: 12px;
}

.pet-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #3e2f24;
  margin: 10px 0 5px;
}

.pet-details p {
  color: #555;
  font-size: 0.95rem;
  margin: 4px 0;
}

.pet-status {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-top: 8px;
}

.status-Available { background:#e8f5e9; color:#2e7d32; }
.status-Adopted { background:#ffebee; color:#c62828; }

.pet-price {
  font-weight: 600;
  color: #2e7d32;
  margin-top: 8px;
  font-size: 1rem;
}


</style>
</head>
<body>

<header>
  <div class="logo">
    <img src="../picture/Group.png" width="40" alt="Buddy Logo">
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
  <div>
    <?php if(isset($_SESSION['user_id'])): ?>
      <a href="../logout.php" class="auth-btn">Logout</a>
    <?php else: ?>
      <a href="../login/login.php" class="auth-btn">Login</a>
    <?php endif; ?>
  </div>
</header>

<div class="container">
  <div class="sidebar">
    <h3>Find Your Bestfriend</h3>
    <form class="filters" method="GET">
      <label>Species</label>
      <select name="species">
        <option value="">Any</option>
        <option value="Dog" <?= (!empty($_GET['species']) && $_GET['species']=='Dog')?'selected':'' ?>>Dog</option>
        <option value="Cat" <?= (!empty($_GET['species']) && $_GET['species']=='Cat')?'selected':'' ?>>Cat</option>
      </select>

      <label>Gender</label>
      <select name="gender">
        <option value="">Any</option>
        <option value="Male" <?= (!empty($_GET['gender']) && $_GET['gender']=='Male')?'selected':'' ?>>Male</option>
        <option value="Female" <?= (!empty($_GET['gender']) && $_GET['gender']=='Female')?'selected':'' ?>>Female</option>
      </select>

      <label>Age</label>
      <input type="number" name="age" value="<?= htmlspecialchars($_GET['age'] ?? '') ?>">

      <label>Pet Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">

      <button type="submit">Filter</button>
      <a href="pet.php">Clear Filters</a>
    </form>
  </div>

  <div class="pet-grid">
    <?php
    if($result->num_rows>0){
      while($pet=$result->fetch_assoc()):
        $imageSrc='../picture/happy.png';
        if(!empty($pet['image'])){
          $img=trim($pet['image']);
          if(filter_var($img, FILTER_VALIDATE_URL)) $imageSrc=$img;
          elseif(strpos($img,'uploads/')===0) $imageSrc='../admin/'.$img;
          else $imageSrc='../admin/uploads/'.ltrim($img,'./\\');
        }
    ?>
    <a href="pet_details.php?id=<?= $pet['pet_id'] ?>" class="pet-card-link">
      <div class="pet-card">
        <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
        <div class="pet-name"><?= htmlspecialchars($pet['name']) ?></div>
        <div class="pet-details">
          <p>Species: <?= htmlspecialchars($pet['species']) ?></p>
          <p>Gender: <?= htmlspecialchars($pet['gender']) ?></p>
          <p>Age: <?= htmlspecialchars($pet['age']) ?> years</p>
        </div>
        <div class="pet-status status-<?= $pet['status'] ?>"><?= htmlspecialchars($pet['status']) ?></div>
        <?php if($pet['adoption_fee']>0): ?>
        <div class="pet-price">Fee: NRP<?= number_format($pet['adoption_fee'],2) ?></div>
        <?php endif; ?>
      </div>
    </a>
    <?php endwhile; } else { ?>
      <p style="width:100%; text-align:center; padding:20px;">No pets found matching your criteria.</p>
    <?php } ?>
  </div>
</div>

<?php $stmt->close(); $conn->close(); ?>
</body>
</html>
