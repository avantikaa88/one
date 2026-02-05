<?php
session_start();
include(__DIR__ . '/../db.php');

// Redirect non-admin users
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $vet_id = $_POST['vet_id'] ?? null;

    if (($action === 'assign' || $action === 'change') && $vet_id) {
        $vet_id = intval($vet_id);
        $stmt = $conn->prepare("UPDATE vet_appointments SET vet_id=?, status='Approved' WHERE id=?");
        $stmt->bind_param("ii", $vet_id, $appointment_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'cancel') {
        $stmt = $conn->prepare("UPDATE vet_appointments SET status='Cancelled' WHERE id=?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_appointments.php");
    exit;
}

// Fetch appointments
$vetAppointments = $conn->query("
    SELECT va.*, u.name AS user_name, p.name AS pet_name
    FROM vet_appointments va
    JOIN users u ON va.user_id = u.user_id
    JOIN pet p ON va.pet_id = p.pet_id
    ORDER BY va.appointment_date DESC, va.appointment_time ASC
");

// Fetch vets
$vets_arr = [];
$vets = $conn->query("SELECT vet_id, username FROM vet");
while ($v = $vets->fetch_assoc()) {
    $vets_arr[$v['vet_id']] = $v['username'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Vet Appointments</title>
<link rel="stylesheet" href="adminn.css">

</head>
<body>
<div class="dashboard-container">

<div class="sidebar">
    <h2>Buddy Admin</h2>
    <ul>
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="manage_vet.php">Manage Vet</a></li>
        <li><a href="manage_pets.php">Manage Pets</a></li>
        <li><a href="adoption_request.php">Adoption Requests</a></li>
        <li><a class="active" href="admin_appointments.php">Appointments</a></li>
    </ul>
    <a href="../logout.php" class="logout-button">Logout</a>
</div>

<div class="main-content">
    <h2>Vet Appointments</h2>
    <table>
        <tr>
            <th>ID</th><th>User</th><th>Pet</th><th>Status</th><th>Assigned Vet</th><th>Actions</th>
        </tr>
        <?php if($vetAppointments->num_rows > 0): ?>
            <?php while($row = $vetAppointments->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['user_name']) ?></td>
                <td><?= htmlspecialchars($row['pet_name']) ?></td>
                <td class="status-<?= $row['status'] ?>"><?= $row['status'] ?></td>
                <td><?= ($row['vet_id'] && isset($vets_arr[$row['vet_id']])) ? htmlspecialchars($vets_arr[$row['vet_id']]) : 'Not Assigned' ?></td>
                <td class="action-buttons">
                    <?php if($row['status']!='Cancelled'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="action" value="<?= $row['vet_id'] ? 'change' : 'assign' ?>">
                            <select name="vet_id" required>
                                <option value="">-- Select Vet --</option>
                                <?php foreach($vets_arr as $vet_id => $vet_name): ?>
                                    <option value="<?= $vet_id ?>" <?= ($row['vet_id']==$vet_id)?'selected':'' ?>><?= htmlspecialchars($vet_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="button button-approve"><?= $row['vet_id'] ? 'Change Vet' : 'Assign Vet' ?></button>
                        </form>
                        <?php if($row['status']=='Pending'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button class="button button-reject">Cancel</button>
                        </form>
                        <?php endif; ?>
                    <?php else: ?> — <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No appointments found</td></tr>
        <?php endif; ?>
    </table>
</div>
</div>
</body>
</html>
