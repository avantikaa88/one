<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vet') {
    header("Location: ../login/login.php");
    exit;
}

$vet_id = $_SESSION['user_id'];
$today = date('Y-m-d'); // for min date input

// ---------------- FETCH VET INFO ----------------
$stmt = $conn->prepare("
    SELECT username, specialization, experience, availability, clinic_location, licence_no, contact_info, email
    FROM vet
    WHERE vet_id = ?
");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$vet_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$vet_name = htmlspecialchars($vet_info['username'] ?? "N/A");
$vet_email = htmlspecialchars($vet_info['email'] ?? "N/A");
$vet_specialization = htmlspecialchars($vet_info['specialization'] ?? "N/A");

// ---------------- HANDLE APPOINTMENT ACTIONS ----------------
if (isset($_POST['approve'], $_POST['appointment_id']) || 
    isset($_POST['mark_paid'], $_POST['appointment_id']) || 
    isset($_POST['mark_completed'], $_POST['appointment_id']) || 
    isset($_POST['delete'], $_POST['appointment_id'])) {

    $appointment_id = intval($_POST['appointment_id']);

    if (isset($_POST['approve'])) {
        // Approve and set date/time
        $stmt = $conn->prepare("
            UPDATE vet_appointments
            SET status='Confirmed', appointment_date=?, appointment_time=?
            WHERE id=? AND vet_id=?
        ");
        $stmt->bind_param("ssii", $_POST['appointment_date'], $_POST['appointment_time'], $appointment_id, $vet_id);

    } elseif (isset($_POST['mark_paid'])) {
        $stmt = $conn->prepare("UPDATE vet_appointments SET payment_status='Paid' WHERE id=? AND vet_id=?");
        $stmt->bind_param("ii", $appointment_id, $vet_id);

    } elseif (isset($_POST['mark_completed'])) {
        $stmt = $conn->prepare("UPDATE vet_appointments SET service_status='Completed' WHERE id=? AND vet_id=?");
        $stmt->bind_param("ii", $appointment_id, $vet_id);

    } elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM vet_appointments WHERE id=? AND vet_id=?");
        $stmt->bind_param("ii", $appointment_id, $vet_id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: vet_dashboard.php");
    exit;
}

// ---------------- FETCH TOTAL APPOINTMENTS ----------------
$stmt = $conn->prepare("SELECT COUNT(*) AS total_appointments FROM vet_appointments WHERE vet_id=?");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total_appointments'] ?? 0;
$stmt->close();

// ---------------- FETCH ASSIGNED PETS & APPOINTMENTS ----------------
$stmt = $conn->prepare("
    SELECT va.id AS appointment_id, va.appointment_date, va.appointment_time, va.status, va.payment_status, va.service_status,
           p.pet_id, p.name AS pet_name, u.name AS owner_name
    FROM vet_appointments va
    JOIN pet p ON va.pet_id = p.pet_id
    JOIN users u ON va.user_id = u.user_id
    WHERE va.vet_id = ?
    ORDER BY va.status ASC, va.appointment_date ASC, va.appointment_time ASC
");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$assigned_pets = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vet Dashboard | Buddy</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="vet.css">
<style>

.status-badge { padding: 3px 8px; border-radius: 5px; color: white; font-size: 0.85em; margin-right: 5px; }
.status-Pending { background-color: orange; }
.status-Assigned { background-color: teal; }
.status-Confirmed { background-color: green; }
.status-Cancelled { background-color: red; }
.status-Unpaid { background-color: red; }
.status-Paid { background-color: blue; }
.status-Pending-Service { background-color: orange; }
.status-Completed-Service { background-color: green; }

.appointment-card { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 5px; }
button { margin-right: 5px; padding: 5px 10px; cursor: pointer; }
.approve { background-color: green; color: white; border: none; }
.pay { background-color: blue; color: white; border: none; }
.complete { background-color: purple; color: white; border: none; }
.delete { background-color: red; color: white; border: none; }
</style>
</head>
<body>

<div class="sidebar">
    <div>
        <h2>Buddy Vet</h2>
        <a class="active" href="vet_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="vet_appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
        <a href="vet_pets.php"><i class="fas fa-paw"></i> Pets</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div>
        <strong><?= $vet_name ?></strong><br>
        <?= $vet_email ?><br>
        <small><?= $vet_specialization ?></small>
    </div>
</div>

<div class="main-content">
<h2>Dashboard</h2>

<div class="card">
    <h3>Total Appointments</h3>
    <p><?= $total_appointments ?></p>
</div>

<div class="card">
    <h3>Assigned Pets & Appointments</h3>

    <?php while ($row = $assigned_pets->fetch_assoc()): ?>
    <div class="appointment-card">
        <div>
            <strong><?= htmlspecialchars($row['pet_name']) ?></strong><br>
            Owner: <?= htmlspecialchars($row['owner_name']) ?><br>

            <?php if (!empty($row['status']) && $row['status'] !== 'Pending'): ?>
                Date: <?= htmlspecialchars($row['appointment_date']) ?><br>
                Time: <?= htmlspecialchars($row['appointment_time']) ?><br>
            <?php endif; ?>

            Status: <span class="status-badge status-<?= $row['status'] ?>"><?= $row['status'] ?></span>
            Payment: <span class="status-badge status-<?= $row['payment_status'] ?>"><?= $row['payment_status'] ?></span>
            Service: <span class="status-badge status-<?= ($row['service_status'] === 'Completed') ? 'Completed-Service' : 'Pending-Service' ?>"><?= $row['service_status'] ?></span>
        </div>

        <form method="POST" style="margin-top:10px;">
            <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">

            <?php if ($row['status'] === 'Pending' || $row['status'] === 'Assigned'): ?>
                <input type="date" name="appointment_date" min="<?= $today ?>" required>
                <input type="time" name="appointment_time" required>
                <button type="submit" name="approve" class="approve">Approve</button>
            <?php endif; ?>

            <?php if ($row['status'] === 'Confirmed' && $row['payment_status'] === 'Unpaid'): ?>
                <button type="submit" name="mark_paid" class="pay">Mark Paid</button>
            <?php endif; ?>

            <?php if ($row['payment_status'] === 'Paid' && $row['service_status'] === 'Pending'): ?>
                <button type="submit" name="mark_completed" class="complete">Complete Service</button>
            <?php endif; ?>

            <button type="submit" name="delete" class="delete">Delete</button>
        </form>
    </div>
    <?php endwhile; ?>
</div>
</div>
</body>
</html>
