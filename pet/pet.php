<?php
session_start();
include(__DIR__ . '/../db.php');

// Ensure user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: ../login/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* ---------------- FILTER LOGIC ---------------- */
$where = ["p.status = 'Available'"];
$params = [];
$types  = '';

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

if (!empty($_GET['age']) && $_GET['age'] > 0) {
    $age = (int)$_GET['age'];
    $dob_from = date('Y-m-d', strtotime("-".($age+1)." years +1 day"));
    $dob_to   = date('Y-m-d', strtotime("-".$age." years"));
    $where[] = "p.dob BETWEEN ? AND ?";
    $params[] = $dob_from;
    $params[] = $dob_to;
    $types .= 'ss';
}

/* ---------------- SQL: Exclude pets with pending/approved adoption ---------------- */
$sql = "
SELECT DISTINCT
    p.pet_id,
    p.name,
    p.gender,
    p.dob,
    p.status,
    p.adoption_fee,
    p.image,
    pt.species
FROM pet p
JOIN pet_type pt ON p.type_id = pt.type_id
LEFT JOIN adoption_application aa
    ON p.pet_id = aa.pet_id
    AND aa.status IN ('Pending','Approved')
WHERE " . implode(" AND ", $where) . "
AND aa.adoption_id IS NULL
";

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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
body{background:#f8f3ef;color:#3e2f24;min-height:100vh;}
header{display:flex;justify-content:space-between;align-items:center;padding:15px 70px;background:#efe2d1;position:sticky;top:0;z-index:1000;}
.logo{display:flex;align-items:center;gap:10px;}
.logo img{width:45px;height:40px;}
.logo h2{font-weight:700;font-size:22px;}
.navbar ul{display:flex;list-style:none;gap:35px;}
.navbar a{text-decoration:none;color:#3e2f24;font-weight:600;}
.navbar a:hover{color:#ce9a16;}
.auth-btn{padding:6px 16px;border-radius:5px;background:#3e2f24;color:#fff;text-decoration:none;font-weight:600;}
.auth-btn:hover{color:#ce9a16;}
.container{display:flex;gap:20px;padding:30px 70px;max-width:1400px;margin:auto;}
.sidebar{width:250px;background:#fff;padding:20px;border-radius:10px;position:sticky;top:100px;height:fit-content;box-shadow:0 2px 10px rgba(0,0,0,0.08);}
.filters label{display:block;margin-top:15px;font-weight:500;}
.filters select,.filters input{width:100%;padding:7px;border-radius:6px;border:1px solid #ccc;margin-top:8px;}
.filters button{width:100%;padding:10px;margin-top:15px;background:#3e2f24;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;}
.filters button:hover{color:#ce9a16;}
.filters a{display:block;margin-top:10px;text-align:center;text-decoration:none;color:#666;}
.filters a:hover{color:#ce9a16;}
.pet-grid{display:flex;flex-wrap:wrap;gap:25px;justify-content:center;flex:1;}
.pet-card-link{text-decoration:none;color:inherit;}
.pet-card{width:240px;background:#fff;border-radius:16px;padding:15px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.08);transition:0.3s;}
.pet-card:hover{transform:translateY(-5px);box-shadow:0 10px 20px rgba(0,0,0,0.15);}
.pet-card img{width:100%;height:180px;object-fit:cover;border-radius:12px;}
.pet-name{font-size:1.2rem;font-weight:700;margin:10px 0 5px;}
.pet-details p{font-size:0.95rem;color:#555;}
.pet-status{display:inline-block;margin-top:8px;padding:4px 10px;border-radius:12px;font-size:0.85rem;font-weight:600;background:#e8f5e9;color:#2e7d32;}
.pet-price{margin-top:8px;font-weight:600;color:#2e7d32;}
</style>
</head>
<body>

<header>
  <div class="logo">
    <img src="../picture/Group.png" alt="Buddy">
    <h2>Buddy</h2>
  </div>
  <nav class="navbar">
    <ul>
      <li><a href="../user/User_dashboard.php">Dashboard</a></li>
      <li><a href="pet.php">Browse Pets</a></li>
      <li><a href="../contact/contact.html">Contact</a></li>
    </ul>
  </nav>
  <div>
    <a href="../logout.php" class="auth-btn">Logout</a>
  </div>
</header>

<div class="container">
  <div class="sidebar">
    <h3>Find Your Bestfriend</h3>
    <form class="filters" method="GET">
      <label>Species</label>
      <select name="species">
        <option value="">Any</option>
        <option value="Dog" <?= ($_GET['species'] ?? '')=='Dog'?'selected':'' ?>>Dog</option>
        <option value="Cat" <?= ($_GET['species'] ?? '')=='Cat'?'selected':'' ?>>Cat</option>
      </select>

      <label>Gender</label>
      <select name="gender">
        <option value="">Any</option>
        <option value="Male" <?= ($_GET['gender'] ?? '')=='Male'?'selected':'' ?>>Male</option>
        <option value="Female" <?= ($_GET['gender'] ?? '')=='Female'?'selected':'' ?>>Female</option>
      </select>

      <label>Age</label>
      <input type="number" name="age" min="1" step="1" value="<?= htmlspecialchars($_GET['age'] ?? '') ?>">

      <label>Pet Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">

      <button type="submit">Filter</button>
      <a href="pet.php">Clear Filters</a>
    </form>
  </div>

  <div class="pet-grid">
    <?php if($result->num_rows > 0): ?>
      <?php while($pet = $result->fetch_assoc()):
        // Correct image path
        $defaultImg = '../picture/happy.png';
        $imageSrc = (!empty($pet['image']) && file_exists(__DIR__ . '/../' . $pet['image'])) 
                     ? '../' . ltrim($pet['image'], '/') 
                     : $defaultImg;

        // Calculate age
        $dob = new DateTime($pet['dob']);
        $age = (new DateTime())->diff($dob)->y;
      ?>
      <a href="pet_details.php?id=<?= $pet['pet_id'] ?>" class="pet-card-link">
        <div class="pet-card">
          <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($pet['name']) ?>">
          <div class="pet-name"><?= htmlspecialchars($pet['name']) ?></div>
          <div class="pet-details">
            <p>Species: <?= htmlspecialchars($pet['species']) ?></p>
            <p>Gender: <?= htmlspecialchars($pet['gender']) ?></p>
            <p>Age: <?= $age ?> years</p>
          </div>
          <div class="pet-status">Available</div>
          <?php if($pet['adoption_fee'] > 0): ?>
            <div class="pet-price">Fee: NRP <?= number_format($pet['adoption_fee'],2) ?></div>
          <?php endif; ?>
        </div>
      </a>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="width:100%;text-align:center;">No pets available.</p>
    <?php endif; ?>
  </div>
</div>

<?php
$stmt->close();
$conn->close();
?>
</body>
</html>
