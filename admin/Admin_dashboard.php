<?php
session_start();
include(__DIR__ . '/../db.php');

// Redirect non-admin users
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Simple function to get total count
function getCount($conn, $table) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM $table");
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// Dashboard counts
$total_users = getCount($conn, "users");
$total_pets = getCount($conn, "pet");
$total_shops = 1;
$total_applications = getCount($conn, "adoption_application");
$total_appointments = getCount($conn, "vet_appointments");

/* ================= HANDLE POST ACTIONS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle Adoption Requests
    if (isset($_POST['adoption_id'])) {
        $id = intval($_POST['adoption_id']);

        if (isset($_POST['adoption_action'])) {
            if ($_POST['adoption_action'] === 'approve') {
                $stmt = $conn->prepare("UPDATE adoption_application SET status='Approved' WHERE adoption_id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            } elseif ($_POST['adoption_action'] === 'reject') {
                $reason = 'Rejected by admin';
                $stmt = $conn->prepare("UPDATE adoption_application SET status='Cancelled', cancel_reason=?, cancelled_at=NOW() WHERE adoption_id=?");
                $stmt->bind_param("si", $reason, $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        if (isset($_POST['payment_status'])) {
            $payment = $_POST['payment_status'];
            $stmt = $conn->prepare("UPDATE adoption_application SET payment_status=? WHERE adoption_id=?");
            $stmt->bind_param("si", $payment, $id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: admin_dashboard.php");
        exit;
    }

    // Handle Vet Appointments
    if (isset($_POST['appointment_id'])) {
        $id = intval($_POST['appointment_id']);
        $action = $_POST['action'] ?? '';
        $vet_id = $_POST['vet_id'] ?? null;

        if (($action === 'assign' || $action === 'change') && $vet_id) {
            $stmt = $conn->prepare("UPDATE vet_appointments SET vet_id=? WHERE id=?");
            $stmt->bind_param("ii", $vet_id, $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'cancel') {
            $stmt = $conn->prepare("UPDATE vet_appointments SET status='Cancelled' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: admin_dashboard.php");
        exit;
    }
}

/* ================= FETCH DATA ================= */

// Recent Adoption Requests (Last 7 Days Only)
$applications = $conn->query("
    SELECT aa.*, u.user_name, u.email, p.name AS pet_name, pt.species, pt.breed
    FROM adoption_application aa
    JOIN users u ON aa.user_id = u.user_id
    JOIN pet p ON aa.pet_id = p.pet_id
    JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE aa.adoption_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY aa.adoption_date DESC
    LIMIT 10
");

// Recent Vet Appointments (Last 7 Days Only)
$vetAppointments = $conn->query("
    SELECT va.*, u.name AS user_name, p.name AS pet_name
    FROM vet_appointments va
    JOIN users u ON va.user_id = u.user_id
    JOIN pet p ON va.pet_id = p.pet_id
    WHERE va.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY va.appointment_date DESC
    LIMIT 10
");

// Available vets for dropdown
$vets = $conn->query("SELECT vet_id, username FROM vet");
$vets_arr = [];
while ($v = $vets->fetch_assoc()) {
    $vets_arr[$v['vet_id']] = $v['username'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<link rel="stylesheet" href="adminn.css">
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

<div class="cards-grid">
    <div class="card"><h3>Total Users</h3><p><?= $total_users ?></p></div>
    <div class="card"><h3>Total Pets</h3><p><?= $total_pets ?></p></div>
    <div class="card"><h3>Total Shops</h3><p><?= $total_shops ?></p></div>
    <div class="card"><h3>Total Applications</h3><p><?= $total_applications ?></p></div>
    <div class="card"><h3>Total Appointments</h3><p><?= $total_appointments ?></p></div>
</div>

<h2>Recent Adoption Requests</h2>
<table>
<tr>
    <th>ID</th><th>User</th><th>Email</th><th>Pet</th><th>Species</th><th>Breed</th>
    <th>Status</th><th>Payment</th><th>Date</th><th>Actions</th>
</tr>
<?php while($row = $applications->fetch_assoc()): ?>
<tr>
    <td><?= $row['adoption_id'] ?></td>
    <td><?= htmlspecialchars($row['user_name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['pet_name']) ?></td>
    <td><?= htmlspecialchars($row['species']) ?></td>
    <td><?= htmlspecialchars($row['breed']) ?></td>
    <td class="status-<?= $row['status'] ?>"><?= $row['status'] ?></td>
    <td><?= $row['payment_status'] ?></td>
    <td><?= date('d M Y', strtotime($row['adoption_date'])) ?></td>
    <td>
        <?php if($row['status']=='Pending'): ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="adoption_id" value="<?= $row['adoption_id'] ?>">
                <input type="hidden" name="adoption_action" value="approve">
                <button class="button button-approve">Approve</button>
            </form>
            <form method="post" style="display:inline;">
                <input type="hidden" name="adoption_id" value="<?= $row['adoption_id'] ?>">
                <input type="hidden" name="adoption_action" value="reject">
                <button class="button button-reject">Reject</button>
            </form>
        <?php elseif($row['status']=='Approved'): ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="adoption_id" value="<?= $row['adoption_id'] ?>">
                <select name="payment_status">
                    <option value="Unpaid" <?= $row['payment_status']=='Unpaid'?'selected':'' ?>>Unpaid</option>
                    <option value="Paid" <?= $row['payment_status']=='Paid'?'selected':'' ?>>Paid</option>
                </select>
                <button class="button button-update">Update</button>
            </form>
        <?php else: ?> — <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>

<h2>Recent Vet Appointments</h2>
<table>
<tr>
    <th>ID</th><th>User</th><th>Pet</th><th>Status</th><th>Assigned Vet</th><th>Actions</th>
</tr>
<?php while($row = $vetAppointments->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['user_name']) ?></td>
    <td><?= htmlspecialchars($row['pet_name']) ?></td>
    <td class="status-<?= $row['status'] ?>"><?= $row['status'] ?></td>
    <td><?= $row['vet_id'] && isset($vets_arr[$row['vet_id']]) ? htmlspecialchars($vets_arr[$row['vet_id']]) : 'Not Assigned' ?></td>
    <td>
        <?php if($row['status']!='Cancelled'): ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="action" value="<?= $row['vet_id'] ? 'change' : 'assign' ?>">
                <select name="vet_id" required>
                    <option value="">-- Select Vet --</option>
                    <?php foreach($vets_arr as $vet_id => $vet_name): ?>
                        <option value="<?= $vet_id ?>" <?= ($row['vet_id']==$vet_id)?'selected':'' ?>>
                            <?= htmlspecialchars($vet_name) ?>
                        </option>
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
</table>

</div>
</div>

</body>
</html>
