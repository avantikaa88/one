<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'user') {
    switch ($_SESSION['user_type']) {
        case 'admin': header("Location: ../Admin/Admin_dashboard.php"); exit;
        case 'vet': header("Location: ../Vet/Vet_dashboard.php"); exit;
        case 'shelter': header("Location: ../Shelter/Shelter_dashboard.php"); exit;
        default: header("Location: ../login/login.php"); exit;
    }
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_name, email FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT p.pet_id, p.name, p.dob, p.gender
    FROM pet p
    JOIN adoption_application a ON p.pet_id = a.pet_id
    WHERE a.user_id = ? AND a.status = 'Approved'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$appointments = [];
$stmt = $conn->prepare("
    SELECT pet_id 
    FROM vet_appointments 
    WHERE user_id = ? AND appointment_date >= CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $appointments[$row['pet_id']] = true;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vet Booking | Buddy</title>
<link rel="stylesheet" href="User.css">
<style>

.pet-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
.pet-card { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.pet-card h3 { margin-top: 0; color: #4b2e05; }
.pet-info { margin: 10px 0; font-size: 14px; color: #3e2b1b; }
.pet-card form { text-align: center; }
.pet-card button { background: #7c5833; color: white; padding: 10px 20px; border: none; border-radius: 25px; cursor: pointer; transition: 0.3s; }
.pet-card button:hover { background: #5f4226; transform: translateY(-2px); }
.disabled-btn { background: #ccc; cursor: not-allowed; }
.no-pets { text-align:center; margin-top:40px; font-size:18px; }
.no-pets a { color: #7c5833; text-decoration: none; }
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
      <li><a href="../pet/pet.php">Browse Pets</a></li>
      <li><a href="vet_booking.php" class="active">Vet Services</a></li>
      <li><a href="#">Contact</a></li>
    </ul>
  </nav>
  <a href="../logout.php" class="login">Logout</a>
</header>

<div class="dashboard-container">

  <div class="sidebar">
    <ul class="sidebar-nav">
      <li><a href="User_dashboard.php">Dashboard</a></li>
      <li><a href="my_pets.php">My Pets</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="adoption_applications.php">Adoption Applications</a></li>
    </ul>

    <div class="user-profile" onclick="window.location.href='user_profile.php'">
      <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="User">
      <div class="user-info">
        <h4><?= htmlspecialchars($user['user_name']); ?></h4>
        <p><?= htmlspecialchars($user['email']); ?></p>
      </div>
    </div>
  </div>

  <div class="main-content">
    <h2>My Adopted Pets</h2>

    <?php if (count($pets) > 0): ?>
    <div class="pet-grid">
      <?php foreach ($pets as $pet): 
            $dob = new DateTime($pet['dob']);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
      ?>
      <div class="pet-card">
        <h3><?= htmlspecialchars($pet['name']); ?></h3>
        <div class="pet-info"><strong>Gender:</strong> <?= htmlspecialchars($pet['gender']); ?></div>
        <div class="pet-info"><strong>Age:</strong> <?= $age; ?> years</div>

        <?php if (isset($appointments[$pet['pet_id']])): ?>
          <button class="disabled-btn" disabled>Appointment Booked</button>
        <?php else: ?>
          <form action="../vet/vet_form.php" method="get">
            <input type="hidden" name="pet_id" value="<?= $pet['pet_id']; ?>">
            <button type="submit">Book Appointment</button>
          </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="no-pets">
        No adopted pets yet. <a href="../pet/pet.php">Adopt a pet first</a>.
      </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
