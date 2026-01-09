<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login/login.php");
    exit;
}

function getCount($conn, $table) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM $table");
    $row = $result->fetch_assoc();
    return $row['total'];
}

$total_users = getCount($conn, "users");
$total_pets = getCount($conn, "pet");
$total_shops = 1;
$total_applications = getCount($conn, "adoption_application");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_adoption'])) {

    $adoption_id    = $_POST['adoption_id'];
    $status         = $_POST['status'];        
    $payment_status = $_POST['payment_status'];  

    $stmt = $conn->prepare("
        UPDATE adoption_application
        SET status = ?, payment_status = ?
        WHERE adoption_id = ?
    ");
    $stmt->bind_param("ssi", $status, $payment_status, $adoption_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT pet_id FROM adoption_application WHERE adoption_id = ?
    ");
    $stmt->bind_param("i", $adoption_id);
    $stmt->execute();
    $stmt->bind_result($pet_id);
    $stmt->fetch();
    $stmt->close();

    if ($status === 'Approved' && $payment_status === 'Paid') {

        $stmt = $conn->prepare("
            UPDATE pet SET status = 'Adopted' WHERE pet_id = ?
        ");
    } else {
      
        $stmt = $conn->prepare("
            UPDATE pet SET status = 'Available' WHERE pet_id = ?
        ");
    }

    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_dashboard.php");
    exit;
}

$applications = $conn->query("
    SELECT aa.adoption_id, aa.status, aa.payment_status, aa.adoption_date,
           u.name AS user_name,
           p.name AS pet_name
    FROM adoption_application aa
    JOIN users u ON aa.user_id = u.user_id
    JOIN pet p ON aa.pet_id = p.pet_id
    WHERE aa.adoption_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY aa.adoption_date DESC
");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];

    if ($_POST['action'] == 'assign' || $_POST['action'] == 'change') {
        $vet_id = $_POST['vet_id'];
        $stmt = $conn->prepare("UPDATE vet_appointments SET vet_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $vet_id, $appointment_id);
        $stmt->execute();
        $stmt->close();
    }

    if ($_POST['action'] == 'cancel') {
        $stmt = $conn->prepare("UPDATE vet_appointments SET status = 'Cancelled' WHERE id = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin_dashboard.php");
    exit;
}

$vetAppointments = $conn->query("
    SELECT va.*, u.name AS user_name, p.name AS pet_name, v.username AS vet_name
    FROM vet_appointments va
    JOIN users u ON va.user_id = u.user_id
    JOIN pet p ON va.pet_id = p.pet_id
    LEFT JOIN vet v ON va.vet_id = v.vet_id
    ORDER BY va.created_at DESC
    LIMIT 10
");

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
    <link rel="stylesheet" href="admin.css">
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
</div>

<h2>Recent Adoption Applications (Last 7 Days)</h2>
<table>
<tr>
    <th>ID</th>
    <th>User</th>
    <th>Pet</th>
    <th>Date</th>
    <th>Status</th>
    <th>Payment</th>
    <th>Actions</th>
</tr>

<?php while ($row = $applications->fetch_assoc()): ?>
<tr>
    <td><?= $row['adoption_id'] ?></td>
    <td><?= $row['user_name'] ?></td>
    <td><?= $row['pet_name'] ?></td>
    <td><?= date('M d, Y', strtotime($row['adoption_date'])) ?></td>
    <td><?= $row['status'] ?></td>
    <td><?= $row['payment_status'] ?></td>
    <td>
        <form method="post">
            <input type="hidden" name="adoption_id" value="<?= $row['adoption_id'] ?>">
            <select name="status">
                <option value="Pending" <?= $row['status']=='Pending'?'selected':'' ?>>Pending</option>
                <option value="Approved" <?= $row['status']=='Approved'?'selected':'' ?>>Approved</option>
                <option value="Rejected" <?= $row['status']=='Rejected'?'selected':'' ?>>Rejected</option>
            </select>
            <select name="payment_status">
                <option value="Unpaid" <?= $row['payment_status']=='Unpaid'?'selected':'' ?>>Unpaid</option>
                <option value="Paid" <?= $row['payment_status']=='Paid'?'selected':'' ?>>Paid</option>
            </select>
            <button type="submit" name="update_adoption">Update</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>


<h2>Recent Vet Appointments </h2>


<table style="width:100%; border-collapse: collapse;">
    <tr style="background:#8B4513; color:#fff;">
        <th>ID</th>
        <th>User</th>
        <th>Pet</th>
        <th>Service</th>
        <th>Reason</th>
        <th>Assigned Vet</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $vetAppointments->fetch_assoc()): ?>
    <tr style="border-bottom:1px solid #ccc;">
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
                <form method="post" style="display:inline-block;">
                    <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="action" value="<?= $row['vet_id'] ? 'change' : 'assign' ?>">

                    <select name="vet_id" required style="padding:5px; margin-right:5px;">
                        <option value="">-- Select Vet --</option>
                        <?php foreach ($vets_arr as $vet_id => $vet_name): ?>
                            <option value="<?= $vet_id ?>" <?= ($row['vet_id'] == $vet_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vet_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" style="background:#28a745;color:#fff;padding:5px 8px;border:none;">
                        <?= $row['vet_id'] ? 'Change Vet' : 'Assign Vet' ?>
                    </button>
                </form>

                <?php if ($row['status'] === 'Pending'): ?>
                <form method="post" style="display:inline-block;">
                    <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" style="background:#dc3545;color:#fff;padding:5px 8px;border:none;">Cancel</button>
                </form>
                <?php endif; ?>

            <?php else: ?>
                —
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>


</div>
</div>
</body>
</html>
