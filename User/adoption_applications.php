<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT user_name, email FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT a.adoption_id, a.adoption_date, a.status, 
           a.payment_status, a.adoption_fee, a.payment_amount, 
           p.name AS pet_name
    FROM adoption_application a
    LEFT JOIN pet p ON a.pet_id = p.pet_id
    WHERE a.user_id = ?
    ORDER BY a.adoption_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Adoption Applications | Buddy</title>
<link rel="stylesheet" href="User.css">

<style>
.main-content { flex:1; padding:20px; }

.table-container {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    overflow-x:auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

th, td {
    padding: 12px;
    border: 1px solid #eee;
    text-align: left;
}

th {
    background: #2f3640;
    color: #fff;
}

tr:nth-child(even) {
    background: #f7f7f7;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
    color: #fff;
    text-transform: capitalize;
}

.approved { background: #44bd32; }
.pending { background: #fbc531; color:#222; }
.rejected { background: #e84118; }
.cancelled { background: #718093; }

.empty-appointments {
    text-align: center;
    margin-top: 50px;
}

.empty-appointments h3 {
    font-size: 22px;
    margin-bottom:10px;
}

.empty-appointments p {
    margin-bottom:20px;
}

.mainbutton {
    padding:10px 20px;
    border:none;
    border-radius:6px;
    background:#2ecc71;
    color:#fff;
    cursor:pointer;
}

.mainbutton:hover {
    background:#27ae60;
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
      <li><a href="../pet/pet.php">Browse Pets</a></li>
      <li><a href="../user/vet_booking.php">Vet Services</a></li>
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
      <li><a href="adoption_applications.php" class="active">Adoption Applications</a></li>
    </ul>

    <div class="user-profile">
      <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="User">
      <div class="user-info">
        <h4><?= htmlspecialchars($user['user_name'] ?? 'User'); ?></h4>
        <p><?= htmlspecialchars($user['email'] ?? 'email@example.com'); ?></p>
      </div>
    </div>
  </div>

  <div class="main-content">
    <h2>My Adoption Applications</h2>

    <?php if ($applications): ?>
    <div class="table-container">
      <table>
        <tr>
          <th>Pet Name</th>
          <th>Date</th>
          <th>Status</th>
          <th>Payment Status</th>
          <th>Adoption Fee</th>
        </tr>

        <?php foreach($applications as $app): ?>
        <tr>
          <td><?= htmlspecialchars($app['pet_name'] ?? 'Unknown') ?></td>
          <td><?= htmlspecialchars(date("Y-m-d", strtotime($app['adoption_date']))) ?></td>
          <td>
            <span class="status-badge <?= strtolower($app['status']) ?>">
              <?= htmlspecialchars($app['status']) ?>
            </span>
          </td>
          <td>
            <span class="status-badge <?= strtolower($app['payment_status'])==='paid'?'approved':'pending' ?>">
              <?= htmlspecialchars($app['payment_status']) ?>
            </span>
          </td>
          <td>Rs <?= number_format($app['adoption_fee'],2) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <?php else: ?>
      <div class="empty-appointments">
        <h3>No Adoption Applications</h3>
        <p>You have not submitted any adoption applications yet.</p>
        <button class="mainbutton" onclick="window.location.href='../pet/pet.php'">
          Browse Pets
        </button>
      </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
