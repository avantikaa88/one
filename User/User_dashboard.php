<?php
session_start();
include(__DIR__ . '/../db.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

switch($_SESSION['user_type']) {
    case 'user': break;
    case 'admin': header("Location: ../Admin/Admin_dashboard.php"); exit;
    case 'vet': header("Location: ../Vet/Vet_dashboard.php"); exit;
    default: header("Location: ../login/login.php"); exit;
}

$user_id = $_SESSION['user_id'];

/* ---------------- USER INFO ---------------- */
$stmt = $conn->prepare("SELECT user_name, email FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc() ?? ['user_name'=>'User','email'=>''];
$stmt->close();

/* ---------------- STATS ---------------- */
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM adoption_application WHERE user_id = ? AND status='Approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pets_adopted = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM adoption_application WHERE user_id = ? AND status='Pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_applications = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

/* NOW INCLUDES PENDING + APPROVED UNPAID */
$stmt = $conn->prepare("
    SELECT SUM(adoption_fee) AS due 
    FROM adoption_application 
    WHERE user_id = ? 
      AND payment_status='Unpaid' 
      AND status IN ('Approved','Pending')
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments_due = $stmt->get_result()->fetch_assoc()['due'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("
    SELECT 
        va.appointment_date,
        va.appointment_time,
        va.status,
        va.payment_status,
        va.service_status,
        p.name AS pet_name,
        v.username AS vet_name
    FROM vet_appointments va
    JOIN pet p ON va.pet_id = p.pet_id
    LEFT JOIN vet v ON va.vet_id = v.vet_id
    WHERE va.user_id = ?
      AND va.status IN ('Confirmed','Completed')
    ORDER BY va.appointment_date ASC, va.appointment_time ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$vet_appointments = [];

while ($row = $result->fetch_assoc()) {

    $row['appointment_date'] = $row['appointment_date']
        ? date("Y-m-d", strtotime($row['appointment_date'])) : 'N/A';

    $row['appointment_time'] = $row['appointment_time']
        ? date("H:i", strtotime($row['appointment_time'])) : 'N/A';

    if (empty($row['vet_name'])) {
        $row['vet_name'] = 'Not Assigned';
    }

    $vet_appointments[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Buddy User Dashboard</title>
<link rel="stylesheet" href="User.css">

<style>
.status-badge { padding:2px 6px; border-radius:5px; font-weight:bold; text-transform:uppercase; font-size:12px; margin-right:5px; display:inline-block; }
.status-Confirmed { background:#b8f2b8; color:#2d7a2d; }
.status-Completed { background:#55efc4; color:#05664d; }
.status-Unpaid { background:#fab1a0; color:#7a1f00; }
.status-Paid { background:#81ecec; color:#065656; }
.status-Service-Pending { background:#ffeaa7; color:#665500; }
.status-Service-Completed { background:#55efc4; color:#05664d; }
.card { background:#fff; padding:20px; border-radius:10px; margin-bottom:15px; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
.upcoming ul { list-style:none; padding:0; }
.upcoming li { background:#fff; padding:15px; border-radius:10px; margin-bottom:10px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.empty-appointments { text-align:center; padding:30px; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.empty-appointments button { padding:10px 15px; border:none; border-radius:5px; background:#2ecc71; color:#fff; cursor:pointer; }
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
      <li><a href="vet_booking.php">Vet Services</a></li>
      <li><a href="../contact/contact.html">Contact</a></li>
    </ul>
  </nav>
  <a href="../logout.php" class="login">Logout</a>
</header>

<div class="dashboard-container">

  <div class="sidebar">
    <ul class="sidebar-nav">
      <li><a href="User_dashboard.php" class="active">Dashboard</a></li>
      <li><a href="my_pets.php">My Pets</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="adoption_applications.php">Adoption Applications</a></li>
    </ul>

  <div class="user-profile">
  <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="User">
  <div class="user-info">
    <h4><?= htmlspecialchars($user['user_name']); ?></h4>
    <p><?= htmlspecialchars($user['email']); ?></p>
  </div>
</div>

  </div>

  <div class="main-content">
   
    <section class="stats">
      <div class="card"><h3>Pets Adopted</h3><p><?= $pets_adopted; ?></p></div>
      <div class="card"><h3>Pending Applications</h3><p><?= $pending_applications; ?></p></div>
      <div class="card"><h3>Payments Due</h3><p>Rs <?= number_format($payments_due,2); ?></p></div>
      <div class="card"><h3>Vet Appointments</h3><p><?= count($vet_appointments); ?></p></div>
    </section>

    <section class="upcoming">
      <h2>Your Vet Appointments</h2>

      <?php if (!empty($vet_appointments)): ?>
        <ul>
          <?php foreach ($vet_appointments as $v): ?>
            <li>
              <strong><?= htmlspecialchars($v['pet_name']); ?></strong><br>
              Date: <?= htmlspecialchars($v['appointment_date']); ?> |
              Time: <?= htmlspecialchars($v['appointment_time']); ?><br>
              Vet: <?= htmlspecialchars($v['vet_name']); ?><br>

              <span class="status-badge status-<?= $v['status']; ?>"><?= htmlspecialchars($v['status']); ?></span>
              <span class="status-badge status-<?= $v['payment_status']; ?>"><?= htmlspecialchars($v['payment_status']); ?></span>
              <span class="status-badge status-Service-<?= $v['service_status']; ?>"><?= htmlspecialchars($v['service_status']); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="empty-appointments">
          <h3>No Vet Appointments</h3>
          <p>You don’t have any confirmed appointments yet.</p>
          <button onclick="window.location.href='vet_booking.php'">Book Vet Visit</button>
        </div>
      <?php endif; ?>
    </section>

  </div>
</div>

</body>
</html>
