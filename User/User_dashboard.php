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

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM adoption_application WHERE user_id = ? AND status='Approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pets_adopted = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();


$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM adoption_application WHERE user_id = ? AND status='Pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_applications = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();


$stmt = $conn->prepare("SELECT SUM(adoption_fee) AS due FROM adoption_application WHERE user_id = ? AND payment_status='Pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments_due = $stmt->get_result()->fetch_assoc()['due'];
$stmt->close();
if (!$payments_due) $payments_due = 0;


$hasAppointments = false;
$vet_appointments = 0;
$vet_list = [];
if ($conn->query("SHOW TABLES LIKE 'vet_appointments'")->num_rows > 0) {
    $stmt = $conn->prepare("SELECT * FROM vet_appointments WHERE user_id = ? AND appointment_date >= CURDATE() ORDER BY appointment_date ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $vet_result = $stmt->get_result();
    $vet_appointments = $vet_result->num_rows;
    if ($vet_appointments > 0) {
        $hasAppointments = true;
        while ($row = $vet_result->fetch_assoc()) {
            $vet_list[] = $row;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Buddy User Dashboard</title>
<link rel="stylesheet" href="User.css">
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
      <li><a href="../vetf/VET_FORM.html">Vet Services</a></li>
      <li><a href="#">Contact</a></li>
    </ul>
  </nav>
  <a href="../logout.php" class="login">Logout</a>
</header>

<div class="dashboard-container">

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-logo"><h2>Buddy</h2></div>
    <ul class="sidebar-nav">
      <li><a href="User_dashboard.php" class="active">Dashboard</a></li>
      <li><a href="my_pets.php">My Pets</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="adoption_applications.php">Adoption</a></li>
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
    <h2>Welcome <?= htmlspecialchars($user['user_name']); ?></h2>

    <section class="stats">
      <div class="card"><h3>Pets Adopted</h3><p><?= $pets_adopted; ?></p></div>
      <div class="card"><h3>Pending Applications</h3><p><?= $pending_applications; ?></p></div>
      <div class="card"><h3>Payments Due</h3><p>Rs <?= number_format($payments_due, 2); ?></p></div>
      <div class="card"><h3>Vet Appointments</h3><p><?= $vet_appointments; ?></p></div>
    </section>

    <section class="upcoming">
      <div class="upcoming-header">
        <h2>Upcoming Appointments</h2>
        <button class="view-all-button" onclick="window.location.href='appointments.php'">View All</button>
      </div>

      <?php if ($hasAppointments): ?>
        <ul>
          <?php foreach($vet_list as $v): ?>
            <li><?= htmlspecialchars($v['appointment_date']) ?> at <?= htmlspecialchars($v['appointment_time']) ?> with <?= htmlspecialchars($v['vet_name']) ?> (<?= htmlspecialchars($v['status']) ?>)</li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="empty-appointments">
          <h3>No Appointments Scheduled</h3>
          <p>You don't have any upcoming appointments.</p>
          <div class="empty-actions">
            <button class="browse button"  onclick="window.location.href='../pet/pet.php'">Browse Pets</button>
            <button class="vet button" onclick="window.location.href='../vetf/VET_FORM.html'">Book Vet Visit</button>
          </div>
        </div>
      <?php endif; ?>
    </section>

  </div>
</div>
</body>
</html>
