<?php
// Start session and include database connection
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
// Only allow logged-in users with type 'vet'
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'vet') {
    header("Location: ../login/login.php");
    exit;
}

$vet_id = $_SESSION['user_id'];

// ---------------- HANDLE APPROVE APPOINTMENT ----------------
if (isset($_POST['approve'])) {
    $id = intval($_POST['appointment_id']);
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];

    // Check if date is in the past
    if ($date < date('Y-m-d')) {
        $_SESSION['error'] = "You cannot select a past date.";
    } else {
        // Update appointment status to Confirmed
        $stmt = $conn->prepare("UPDATE vet_appointments SET status='Confirmed', appointment_date=?, appointment_time=? WHERE id=? AND vet_id=?");
        $stmt->bind_param("ssii", $date, $time, $id, $vet_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: vet_appointment.php");
    exit;
}

// ---------------- HANDLE CANCEL APPOINTMENT ----------------
if (isset($_POST['cancel'])) {
    $id = intval($_POST['appointment_id']);
    $stmt = $conn->prepare("UPDATE vet_appointments SET status='Cancelled' WHERE id=? AND vet_id=?");
    $stmt->bind_param("ii", $id, $vet_id);
    $stmt->execute();
    $stmt->close();

    header("Location: vet_appointment.php");
    exit;
}

// ---------------- FETCH ALL APPOINTMENTS ----------------
$stmt = $conn->prepare("
    SELECT va.id, va.appointment_date, va.appointment_time, va.status, va.reason, va.service_type,
           u.name AS user_name, p.name AS pet_name
    FROM vet_appointments va
    JOIN users u ON va.user_id = u.user_id
    JOIN pet p ON va.pet_id = p.pet_id
    WHERE va.vet_id=?
    ORDER BY FIELD(va.status,'Pending','Confirmed','Completed','Cancelled'), va.appointment_date ASC
");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$appointments = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vet Appointments | Buddy Vet</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="vet.css">

</head>
<body>

<!-- ---------------- Sidebar ---------------- -->
<div class="sidebar">
    <div>
        <h2>Buddy Vet</h2>
        <a href="vet_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a class="active" href="vet_appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
        <a href="vet_pets.php"><i class="fas fa-paw"></i> Pets</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>


<!-- ---------------- Main Content ---------------- -->
<div class="main">
    <h2>Manage Appointments</h2>

    <!-- Show error messages -->
    <?php if(isset($_SESSION['error'])): ?>
        <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if($appointments->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Pet</th>
                    <th>User</th>
                    <th>Service</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['pet_name']); ?></td>
                    <td><?= htmlspecialchars($row['user_name']); ?></td>
                    <td><?= htmlspecialchars($row['service_type']); ?></td>
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= $row['appointment_date'] ?: '-'; ?></td>
                    <td><?= $row['appointment_time'] ?: '-'; ?></td>
                    <td>
                        <span class="status status-<?= $row['status']; ?>"><?= $row['status']; ?></span>
                    </td>
                    <td>
                        <!-- Approve button for Pending -->
                        <?php if($row['status'] === 'Pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="appointment_id" value="<?= $row['id']; ?>">
                                <input type="date" name="appointment_date" required min="<?= date('Y-m-d'); ?>">
                                <input type="time" name="appointment_time" required>
                                <button type="submit" name="approve" class="approve">Approve</button>
                            </form>
                        <?php endif; ?>

                        <!-- Cancel button for Pending or Confirmed -->
                        <?php if($row['status'] === 'Pending' || $row['status'] === 'Confirmed'): ?>
                            <form method="POST">
                                <input type="hidden" name="appointment_id" value="<?= $row['id']; ?>">
                                <button type="submit" name="cancel" class="cancel">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No appointments found.</p>
    <?php endif; ?>
</div>

</body>
</html>
