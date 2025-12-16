<?php
session_start();
include(__DIR__ . '/../db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT user_name, email FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch adoption applications
$stmt = $conn->prepare("
    SELECT a.adoption_id, a.pet_id, a.adoption_date, a.status, 
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
.table-container {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 10px;
    overflow: hidden;
}

th, td {
    padding: 12px 14px;
    border: 1px solid #eee;
}

th {
    background: #2f3640;
    color: #fff;
    text-align: left;
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
}

.approved { background: #44bd32; }
.pending { background: #fbc531; color: #222; }
.rejected { background: #e84118; }
.cancelled { background: #718093; }

.cancel {
    padding: 6px;
    width: 100%;
    background: #e84118;
    color: #fff;
    border: none;
    cursor: pointer;
    border-radius: 6px;
}

.cancel:hover {
    background: #c23616;
}
</style>
</head>

<body>

<!-- HEADER -->
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

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="sidebar-logo"><h2>Buddy</h2></div>
    <ul class="sidebar-nav">
      <li><a href="User_dashboard.php">Dashboard</a></li>
      <li><a href="my_pets.php">My Pets</a></li>
      <li><a href="appointments.php">Appointments</a></li>
      <li><a href="adoption_applications.php" class="active">Adoption Applications</a></li>
    </ul>

    <div class="user-profile" onclick="window.location.href='user_profile.php'">
      <img src="https://cdn-icons-png.flaticon.com/512/847/847969.png" alt="User">
      <div class="user-info">
        <h4><?= htmlspecialchars($user['user_name']); ?></h4>
        <p><?= htmlspecialchars($user['email']); ?></p>
      </div>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <h2>My Adoption Applications</h2>

    <!-- SUCCESS MESSAGE -->
    <?php if (isset($_GET['cancel']) && $_GET['cancel'] === 'success'): ?>
      <div style="background:#e6ffe6;padding:10px;margin:15px 0;border-left:4px solid #44bd32;">
        Adoption application cancelled successfully.
      </div>
    <?php endif; ?>

    <?php if ($applications): ?>
    <div class="table-container">
      <table>
        <tr>
          <th>Pet Name</th>
          <th>Application Date</th>
          <th>Status</th>
          <th>Payment Status</th>
          <th>Adoption Fee</th>
          <th>Paid Amount</th>
          <th>Action</th>
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
            <span class="status-badge 
              <?= strtolower($app['payment_status']) === 'paid' ? 'approved' : 'pending' ?>">
              <?= htmlspecialchars($app['payment_status']) ?>
            </span>
          </td>

          <td>Rs <?= number_format($app['adoption_fee'], 2) ?></td>
          <td>Rs <?= number_format($app['payment_amount'] ?? 0, 2) ?></td>

          <!-- CANCEL OPTION -->
          <td>
          <?php if (strtolower($app['status']) === 'pending'): ?>
            <form method="POST" action="cancel_adoption.php">
              <input type="hidden" name="adoption_id" value="<?= $app['adoption_id'] ?>">

              <select name="cancel_reason" required style="margin-bottom:6px;">
                <option value="">Select reason</option>
                <option value="Changed mind">Changed mind</option>
                <option value="Not ready">Not ready</option>
                <option value="Family issue">Family issue</option>
                <option value="Other">Other</option>
              </select>

              <button type="submit" class="cancel">Cancel</button>
            </form>
          <?php else: ?>
            —
          <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <?php else: ?>
      <div class="empty-appointments" style="margin-top: 20px;">
        <h3>No Adoption Applications</h3>
        <p>You have not submitted any adoption applications yet.</p>
        <button class="mainbutton" onclick="window.location.href='../pet/pet.php'">Browse Pets</button>
      </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
