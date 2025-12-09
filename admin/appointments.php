<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Fetch appointments
$appointments = $conn->query("
SELECT a.appointment_id, u.name AS user_name, p.name AS pet_name, v.name AS vet_name, a.appointment_date, a.appointment_time, a.status
FROM appointment a
JOIN users u ON a.user_id = u.user_id
JOIN pet p ON a.pet_id = p.pet_id
JOIN veterinarian v ON a.vet_id = v.vet_id
ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Appointments</title>
</head>
<body>
<h2>Appointments</h2>
<table border="1" cellpadding="10">
<tr>
<th>ID</th>
<th>User</th>
<th>Pet</th>
<th>Vet</th>
<th>Date</th>
<th>Time</th>
<th>Status</th>
</tr>
<?php while($app = $appointments->fetch_assoc()): ?>
<tr>
<td><?= $app['appointment_id'] ?></td>
<td><?= htmlspecialchars($app['user_name']) ?></td>
<td><?= htmlspecialchars($app['pet_name']) ?></td>
<td><?= htmlspecialchars($app['vet_name']) ?></td>
<td><?= $app['appointment_date'] ?></td>
<td><?= $app['appointment_time'] ?></td>
<td><?= htmlspecialchars($app['status']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<a href="admin_dashboard.php">Back to Dashboard</a>
</body>
</html>
