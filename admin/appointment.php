<?php
session_start();
include(__DIR__ . '/../db.php');

/* ---------- AUTH CHECK ---------- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

/* ---------- HANDLE FORM SUBMISSION ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id']);

    // Assign vet (when no vet assigned yet)
    if (isset($_POST['vet_id']) && $_POST['action'] === 'assign') {
        $vet_id = intval($_POST['vet_id']);
        $stmt = $conn->prepare("UPDATE vet_appointments SET vet_id=?, status='Confirmed' WHERE id=?");
        $stmt->bind_param("ii", $vet_id, $appointment_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_appointments.php");
        exit;
    }

    // Change vet (when vet already assigned)
    if (isset($_POST['vet_id']) && $_POST['action'] === 'change') {
        $vet_id = intval($_POST['vet_id']);
        $stmt = $conn->prepare("UPDATE vet_appointments SET vet_id=? WHERE id=?");
        $stmt->bind_param("ii", $vet_id, $appointment_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_appointments.php");
        exit;
    }

    // Cancel appointment
    if ($_POST['action'] === 'cancel') {
        $stmt = $conn->prepare("UPDATE vet_appointments SET status='Cancelled' WHERE id=?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_appointments.php");
        exit;
    }
}

/* ---------- FETCH VET APPOINTMENTS ---------- */
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
    ORDER BY va.created_at DESC
");

/* ---------- FETCH ALL VETS ---------- */
$vets = $conn->query("SELECT vet_id, name FROM vet");
$vets_arr = [];
while ($vet = $vets->fetch_assoc()) {
    $vets_arr[$vet['vet_id']] = $vet['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Appointments</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="admin.css" />
<style>
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
th { background-color: #f2f2f2; }
button, select { padding: 5px; margin: 2px 0; }
form { margin: 0; display:inline-block; }
.assign-btn { background-color: #28a745; color: white; border: none; cursor: pointer; }
.change-btn { background-color: #007bff; color: white; border: none; cursor: pointer; }
.cancel-btn { background-color: #dc3545; color: white; border: none; cursor: pointer; }
button:hover { opacity: 0.9; }
</style>
</head>

<body>
<div class="dashboard-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>Buddy Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_pets.php">Manage Pets</a></li>
            <li><a href="adoption_requests.php">Adoption Requests</a></li>
            <li><a class="active" href="admin_appointments.php">Appointments</a></li>
        </ul>
        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

    <!-- CONTENT -->
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
                        <?php if ($row['status'] !== 'Cancelled'): ?>
                            <?php 
                            $current_vet_name = isset($vets_arr[$row['vet_id']]) ? 
                                htmlspecialchars($vets_arr[$row['vet_id']]) : 
                                'Not Assigned';
                            ?>
                            <?= $current_vet_name ?>
                        <?php else: ?>
                            — 
                        <?php endif; ?>
                    </td>
                    <td><?= $row['status'] ?></td>
                    <td>
                        <?php if ($row['status'] !== 'Cancelled'): ?>
                            <!-- Form for assigning/changing vet -->
                            <form method="post" style="display: inline-block; margin-right: 5px;">
                                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="action" value="<?= $row['vet_id'] ? 'change' : 'assign' ?>">
                                <select name="vet_id" required>
                                    <option value="">--Select Vet--</option>
                                    <?php foreach($vets_arr as $vet_id => $vet_name): ?>
                                        <option value="<?= $vet_id ?>" <?= ($row['vet_id'] == $vet_id)?'selected':'' ?>>
                                            <?= htmlspecialchars($vet_name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="<?= $row['vet_id'] ? 'change-btn' : 'assign-btn' ?>">
                                    <?= $row['vet_id'] ? 'Change Vet' : 'Assign Vet' ?>
                                </button>
                            </form>
                            
                            <!-- Form for cancelling appointment -->
                            <?php if ($row['status'] === 'Pending'): ?>
                                <form method="post" style="display: inline-block;">
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
                <tr><td colspan="8">No vet appointments found</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>