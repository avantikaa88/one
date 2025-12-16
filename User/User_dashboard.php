<?php
session_start();
include(__DIR__ . '/../db.php'); 

/* ---------------- AUTH CHECK ---------------- */
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

/* ---------------- USER INFO ---------------- */
$stmt = $conn->prepare("SELECT user_name, email FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ---------------- DASHBOARD STATS ---------------- */
// Count adopted pets
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM adoption_application WHERE user_id = ? AND status='Approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pets_adopted = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Count pending applications
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM adoption_application WHERE user_id = ? AND status='Pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_applications = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

// Total payments due (based on payment_status='Unpaid')
$stmt = $conn->prepare("
    SELECT SUM(adoption_fee) AS due 
    FROM adoption_application 
    WHERE user_id = ? AND payment_status='Unpaid'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments_due = $stmt->get_result()->fetch_assoc()['due'] ?? 0;
$stmt->close();

/* ---------------- CONFIRMED VET APPOINTMENTS ONLY ---------------- */
$hasAppointments = false;
$vet_appointments = 0;
$vet_list = [];

$stmt = $conn->prepare("
    SELECT 
        va.appointment_date,
        va.appointment_time,
        p.name AS pet_name,
        v.name AS vet_name
    FROM vet_appointments va
    JOIN pet p ON va.pet_id = p.pet_id
    LEFT JOIN vet v ON va.vet_id = v.vet_id
    WHERE va.user_id = ?
      AND va.status = 'Confirmed'
      AND va.appointment_date IS NOT NULL
      AND va.appointment_time IS NOT NULL
    ORDER BY va.appointment_date ASC, va.appointment_time ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$vet_appointments = $result->num_rows;

if ($vet_appointments > 0) {
    $hasAppointments = true;
    while ($row = $result->fetch_assoc()) {
        // Format date and time
        $row['appointment_date'] = date("Y-m-d", strtotime($row['appointment_date']));
        $row['appointment_time'] = date("H:i", strtotime($row['appointment_time']));
        $vet_list[] = $row;
    }
}
$stmt->close();
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
      <li><a href="vet_booking.php">Vet Services</a></li>
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
      <li><a href="adoption_applications.php">Adoption Application</a></li>
    </ul>

    <div class="user-profile" onclick="window.location.href='user_profile.php'">
      <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="User">
      <div class="user-info">
        <h4><?= htmlspecialchars($user['user_name']); ?></h4>
        <p><?= htmlspecialchars($user['email']); ?></p>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h2>Welcome <?= htmlspecialchars($user['user_name']); ?></h2>

    <!-- Stats -->
    <section class="stats">
      <div class="card"><h3>Pets Adopted</h3><p><?= $pets_adopted; ?></p></div>
      <div class="card"><h3>Pending Applications</h3><p><?= $pending_applications; ?></p></div>
      <div class="card"><h3>Payments Due</h3><p>Rs <?= number_format($payments_due, 2); ?></p></div>
      <div class="card"><h3>Confirmed Vet Appointments</h3><p><?= $vet_appointments; ?></p></div>
    </section>

    <!-- Upcoming Confirmed Appointments -->
    <section class="upcoming">
      <h2>Confirmed Vet Appointments</h2>

      <?php if ($hasAppointments): ?>
        <ul>
          <?php foreach ($vet_list as $v): ?>
            <li>
              <strong><?= htmlspecialchars($v['pet_name']); ?></strong><br>
              Date: <?= htmlspecialchars($v['appointment_date']); ?> |
              Time: <?= htmlspecialchars($v['appointment_time']); ?><br>
              Vet: <?= htmlspecialchars($v['vet_name'] ?? 'Vet'); ?><br>
              <span style="color:green;font-weight:bold;">Confirmed</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="empty-appointments">
          <h3>No Confirmed Vet Appointments</h3>
          <p>You don't have any confirmed appointments yet.</p>
          <button class="browse" onclick="window.location.href='../vetf/VET_FORM.html'">Book Vet Visit</button>
        </div>
      <?php endif; ?>
    </section>

  </div>
</div>
</body>
</html>
