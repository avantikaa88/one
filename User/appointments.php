<?php
session_start();
include(__DIR__ . '/../db.php');

/* ---------------- AUTH CHECK ---------------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

if ($_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ---------------- FETCH APPOINTMENTS ---------------- */
$appointments = [];

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
    ORDER BY FIELD(va.status,'Pending','Confirmed','Completed','Cancelled'), va.appointment_date ASC, va.appointment_time ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // If vet is not assigned yet
    if (empty($row['vet_name'])) $row['vet_name'] = 'Not Assigned';
    $appointments[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Appointments</title>
<link rel="stylesheet" href="User.css">

<style>


.main-content { flex:1; }

.appointment-card {
    background: #ffffff;
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}
.status {
    font-weight: bold;
    padding: 5px 12px;
    border-radius: 6px;
    display: inline-block;
    margin-top: 5px;
    text-transform: uppercase;
    font-size: 12px;
}
.pending { background: #fff3cd; color: #856404; }
.confirmed { background: #d4edda; color: #155724; }
.completed { background: #cce5ff; color: #004085; }
.cancelled { background: #f8d7da; color: #721c24; }

.empty {
    text-align: center;
    margin-top: 60px;
}
.empty button {
    padding: 10px 15px;
    border:none;
    border-radius:5px;
    background:#2ecc71;
    color:#fff;
    cursor:pointer;
}
.empty button:hover {
    background:#27ae60;
}
</style>
</head>

<body>

<header>
  <div class="logo">
    <img src="../picture/Group.png" alt="Buddy Logo" width="45" height="40">
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
    <ul class="sidebar-nav">
      <li><a href="User_dashboard.php">Dashboard</a></li>
      <li><a href="my_pets.php">My Pets</a></li>
      <li><a href="appointments.php" class="active">Appointments</a></li>
      <li><a href="adoption_applications.php">Adoption Applications</a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <h2>My Vet Appointments</h2>

    <?php if (!empty($appointments)): ?>
        <?php foreach ($appointments as $a): ?>
            <div class="appointment-card">
                <h3><?= htmlspecialchars($a['pet_name']) ?></h3>
                <?php
                $status = $a['status'];
                switch ($status) {
                    case 'Pending':
                        echo '<p><strong>Status:</strong> <span class="status pending">Waiting for vet confirmation</span></p>';
                        echo '<p>The vet has not assigned a date and time yet.</p>';
                        break;

                    case 'Confirmed':
                        echo '<p><strong>Date:</strong> '.htmlspecialchars($a['appointment_date']).'</p>';
                        echo '<p><strong>Time:</strong> '.htmlspecialchars($a['appointment_time']).'</p>';
                        echo '<p><strong>Vet:</strong> '.htmlspecialchars($a['vet_name']).'</p>';
                        echo '<p><strong>Status:</strong> <span class="status confirmed">Confirmed</span></p>';
                        break;

                    case 'Completed':
                        echo '<p><strong>Date:</strong> '.htmlspecialchars($a['appointment_date']).'</p>';
                        echo '<p><strong>Time:</strong> '.htmlspecialchars($a['appointment_time']).'</p>';
                        echo '<p><strong>Vet:</strong> '.htmlspecialchars($a['vet_name']).'</p>';
                        echo '<p><strong>Status:</strong> <span class="status completed">Completed</span></p>';
                        break;

                    case 'Cancelled':
                        echo '<p><strong>Date:</strong> '.htmlspecialchars($a['appointment_date'] ?? 'N/A').'</p>';
                        echo '<p><strong>Time:</strong> '.htmlspecialchars($a['appointment_time'] ?? 'N/A').'</p>';
                        echo '<p><strong>Vet:</strong> '.htmlspecialchars($a['vet_name']).'</p>';
                        echo '<p><strong>Status:</strong> <span class="status cancelled">Cancelled</span></p>';
                        break;
                }
                ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty">
            <h3>No Appointments Found</h3>
            <p>You have not booked any vet appointments yet.</p>
            <button onclick="window.location.href='../vet/vet_booking.php'">Book Vet Appointment</button>
        </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
