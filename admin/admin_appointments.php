<?php
session_start();
include(__DIR__ . '/../db.php');

/* ---------- AUTH CHECK ---------- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

/* ---------- HANDLE ACTIONS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $appointment_id = intval($_POST['appointment_id']);
    $action = $_POST['action'];

    /* ASSIGN OR CHANGE VET */
    if (($action === 'assign' || $action === 'change') && !empty($_POST['vet_id'])) {
        $vet_id = intval($_POST['vet_id']);

        $stmt = $conn->prepare("
            UPDATE vet_appointments 
            SET vet_id = ?, status = 'Confirmed'
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $vet_id, $appointment_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_appointments.php");
        exit;
    }

    /* CANCEL APPOINTMENT */
    if ($action === 'cancel') {
        $stmt = $conn->prepare("
            UPDATE vet_appointments 
            SET status = 'Cancelled'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_appointments.php");
        exit;
    }
}

/* ---------- FETCH VET APPOINTMENTS (LAST 7 DAYS) ---------- */
$vetAppointments = $conn->query("
    SELECT 
        va.id,
        u.name AS user_name,
        p.name AS pet_name,
        va.vet_id,
        va.service_type,
        va.reason,
        va.status
    FROM vet_appointments va
    JOIN users u ON va.user_id = u.user_id
    JOIN pet p ON va.pet_id = p.pet_id
    WHERE va.created_at >= NOW() - INTERVAL 7 DAY
    ORDER BY va.created_at DESC
");

/* ---------- FETCH VETS ---------- */
$vets = $conn->query("
    SELECT vet_id, username 
    FROM vet
");
$vets_arr = [];
while ($vet = $vets->fetch_assoc()) {
    $vets_arr[$vet['vet_id']] = $vet['username'];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Appointments</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="admin.css">

<style>
button, select { padding: 5px; margin: 2px 0; }
form { display:inline-block; margin: 0; }
.assign-btn { background:#28a745; color:#fff; border:none; }
.change-btn { background:#007bff; color:#fff; border:none; }
.cancel-btn { background:#dc3545; color:#fff; border:none; }
button:hover { opacity:0.9; cursor:pointer; }
</style>
</head>

<body>
<div class="dashboard-container">
    <div class="sidebar">
        <h2>Buddy Admin</h2>
    <ul>
        <li><a class="active" href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="manage_vet.php">Manage Vet</a></li>
        <li><a href="manage_pets.php">Manage Pets</a></li>
        <li><a href="adoption_request.php">Adoption Requests</a></li>
        <li><a href="admin_appointments.php">Appointments</a></li>
    </ul>
        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

    <div class="main-content">
        <h2>Vet Appointments</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Pet</th>
                <th>Service</th>
                <th>Reason</th>
                <th>Assigned Vet</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

            <?php if ($vetAppointments->num_rows > 0): ?>
                <?php while ($row = $vetAppointments->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['pet_name']) ?></td>
                    <td><?= htmlspecialchars($row['service_type']) ?></td>
                    <td><?= htmlspecialchars($row['reason']) ?></td>

                  <td>
    <?= $row['vet_id'] && isset($vets_arr[$row['vet_id']])
        ? htmlspecialchars($vets_arr[$row['vet_id']])
        : 'Not Assigned' ?>
</td>


                    <td><?= $row['status'] ?></td>

                    <td>
                        <?php if ($row['status'] !== 'Cancelled'): ?>

                            <!-- ASSIGN / CHANGE VET -->
                            <form method="post">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="action" value="<?= $row['vet_id'] ? 'change' : 'assign' ?>">

                                <select name="vet_id" required>
                                <option value="">-- Select Vet --</option>
                                <?php foreach ($vets_arr as $vet_id => $vet_name): ?>
                                    <option value="<?= $vet_id ?>" <?= ($row['vet_id'] == $vet_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vet_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>


                                <button type="submit" class="<?= $row['vet_id'] ? 'change-btn' : 'assign-btn' ?>">
                                    <?= $row['vet_id'] ? 'Change Vet' : 'Assign Vet' ?>
                                </button>
                            </form>

                            <!-- CANCEL (ONLY WHEN PENDING) -->
                            <?php if ($row['status'] === 'Pending'): ?>
                                <form method="post">
                                    <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="cancel-btn">Cancel</button>
                                </form>
                            <?php endif; ?>

                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No appointments found</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
