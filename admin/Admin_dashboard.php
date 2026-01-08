<?php
session_start();
include(__DIR__ . '/../db.php');

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login/login.php");
    exit;
}

/* ================= SIMPLE COUNTS ================= */
function getCount($conn, $table) {
    $result = $conn->query("SELECT COUNT(*) AS total FROM $table");
    $row = $result->fetch_assoc();
    return $row['total'];
}

$total_users = getCount($conn, "users");
$total_pets = getCount($conn, "pet");
$total_shops = 1;
$total_applications = getCount($conn, "adoption_application");

/* ================= UPDATE ADOPTION + PAYMENT + PET STATUS ================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_adoption'])) {

    $adoption_id    = $_POST['adoption_id'];
    $status         = $_POST['status'];          // Pending / Approved / Rejected
    $payment_status = $_POST['payment_status'];  // Paid / Unpaid

    /* 1️ Update adoption_application */
    $stmt = $conn->prepare("
        UPDATE adoption_application
        SET status = ?, payment_status = ?
        WHERE adoption_id = ?
    ");
    $stmt->bind_param("ssi", $status, $payment_status, $adoption_id);
    $stmt->execute();
    $stmt->close();

    /* 2️ Get pet_id for this adoption */
    $stmt = $conn->prepare("
        SELECT pet_id FROM adoption_application WHERE adoption_id = ?
    ");
    $stmt->bind_param("i", $adoption_id);
    $stmt->execute();
    $stmt->bind_result($pet_id);
    $stmt->fetch();
    $stmt->close();

    /* 3️ Decide pet status (BEGINNER LOGIC) */
    if ($status === 'Approved' && $payment_status === 'Paid') {
        // Adoption completed
        $stmt = $conn->prepare("
            UPDATE pet SET status = 'Adopted' WHERE pet_id = ?
        ");
    } else {
        // Not fully completed
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

/* ================= FETCH ADOPTION APPLICATIONS (LAST 7 DAYS) ================= */
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

/* ================= HANDLE VET APPOINTMENTS ================= */
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

/* ================= FETCH VET APPOINTMENTS (LATEST 10) ================= */
$vetAppointments = $conn->query("
    SELECT va.*, u.name AS user_name, p.name AS pet_name, v.name AS vet_name
    FROM vet_appointments va
    JOIN users u ON va.user_id = u.user_id
    JOIN pet p ON va.pet_id = p.pet_id
    LEFT JOIN vet v ON va.vet_id = v.vet_id
    ORDER BY va.created_at DESC
    LIMIT 10
");

/* ================= FETCH VETS ================= */
$vets = $conn->query("SELECT vet_id, name FROM vet");
$vets_arr = [];
while ($v = $vets->fetch_assoc()) {
    $vets_arr[$v['vet_id']] = $v['name'];
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

<!-- ========== SIDEBAR ========== -->
<div class="sidebar">
    <h2>Buddy Admin</h2>
    <ul>
        <li><a class="active" href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="manage_pets.php">Manage Pets</a></li>
        <li><a href="adoption_requests.php">Adoption Requests</a></li>
        <li><a href="admin_appointments.php">Appointments</a></li>
    </ul>
    <a href="../logout.php" class="logout-button">Logout</a>
</div>

<!-- ========== MAIN CONTENT ========== -->
<div class="main-content">

<!-- DASHBOARD CARDS -->
<div class="cards-grid">
    <div class="card"><h3>Total Users</h3><p><?= $total_users ?></p></div>
    <div class="card"><h3>Total Pets</h3><p><?= $total_pets ?></p></div>
    <div class="card"><h3>Total Shops</h3><p><?= $total_shops ?></p></div>
    <div class="card"><h3>Total Applications</h3><p><?= $total_applications ?></p></div>
</div>

<!-- ========== ADOPTION APPLICATIONS ========== -->
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

<!-- ========== VET APPOINTMENTS ========== -->
<h2>Recent Vet Appointments (Last 10)</h2>
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

<?php while ($v = $vetAppointments->fetch_assoc()): ?>
<tr>
    <td><?= $v['id'] ?></td>
    <td><?= $v['user_name'] ?></td>
    <td><?= $v['pet_name'] ?></td>
    <td><?= $v['service_type'] ?></td>
    <td><?= $v['reason'] ?></td>
    <td><?= $v['vet_name'] ?? 'Not assigned' ?></td>
    <td><?= $v['status'] ?></td>
    <td>
        <form method="post">
            <input type="hidden" name="appointment_id" value="<?= $v['id'] ?>">
            <select name="vet_id">
                <?php foreach ($vets_arr as $id => $name): ?>
                    <option value="<?= $id ?>" <?= ($v['vet_id']==$id)?'selected':'' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="action" value="change">Change</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>

</div>
</div>
</body>
</html>
