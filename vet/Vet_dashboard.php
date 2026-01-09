<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vet') {
    header("Location: ../login/login.php");
    exit;
}

$vet_id = $_SESSION['user_id'];
$today = date('Y-m-d'); // For appointment date min

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
$vet_experience = htmlspecialchars($vet_info['experience'] ?? "N/A");
$vet_availability = htmlspecialchars($vet_info['availability'] ?? "N/A");
$vet_clinic = htmlspecialchars($vet_info['clinic_location'] ?? "N/A");
$vet_licence = htmlspecialchars($vet_info['licence_no'] ?? "N/A");
$vet_contact = htmlspecialchars($vet_info['contact_info'] ?? "N/A");

// ---------------- HANDLE APPOINTMENT ACTIONS ----------------
if (isset($_POST['approve'], $_POST['appointment_id']) || 
    isset($_POST['mark_paid'], $_POST['appointment_id']) || 
    isset($_POST['mark_completed'], $_POST['appointment_id']) || 
    isset($_POST['delete'], $_POST['appointment_id'])) {

    $appointment_id = intval($_POST['appointment_id']);

    if (isset($_POST['approve'])) {
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

// ---------------- FETCH RECENT 5 APPOINTMENTS ----------------
$stmt = $conn->prepare("
    SELECT va.id, va.appointment_date, va.appointment_time, va.status, va.payment_status, va.service_status,
           u.name AS user_name, p.name AS pet_name
    FROM vet_appointments va
    JOIN users u ON va.user_id = u.user_id
    JOIN pet p ON va.pet_id = p.pet_id
    WHERE va.vet_id=?
    ORDER BY va.appointment_date DESC, va.appointment_time DESC
    LIMIT 5
");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$recent_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vet Dashboard | Buddy</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { background:#f5f6fa; }

/* Sidebar */
.sidebar {
    width:240px; height:100vh; background:#2f3640; color:#fff;
    position:fixed; padding:20px; display:flex; flex-direction:column; justify-content:space-between;
}
.sidebar h2 { text-align:center; margin-bottom:20px; }
.sidebar a { display:block; color:#fff; text-decoration:none; padding:12px; border-radius:6px; margin-bottom:5px; }
.sidebar a:hover { background:#353b48; }

/* Main Content */
.main-content { margin-left:260px; padding:30px; }
.card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:20px; }

/* Appointment Cards */
.appointment-card {
    display:flex; justify-content:space-between; background:#fff; padding:15px;
    border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:15px;
}

/* Status badges */
.status-badge { padding:4px 10px; border-radius:20px; font-size:12px; font-weight:bold; margin-right:5px; }
.status-Pending { background:#ffe58f; }
.status-Confirmed { background:#b8f2b8; }
.status-Unpaid { background:#fab1a0; }
.status-Paid { background:#81ecec; }
.status-Pending-Service { background:#ffeaa7; }
.status-Completed-Service { background:#55efc4; }

/* Buttons */
button.approve { background:#2ecc71; color:#fff; border:none; padding:6px 10px; border-radius:5px; margin-right:5px; }
button.delete { background:#e74c3c; color:#fff; border:none; padding:6px 10px; border-radius:5px; margin-right:5px; }
button.pay { background:#3498db; color:#fff; border:none; padding:6px 10px; border-radius:5px; margin-right:5px; }
button.complete { background:#1abc9c; color:#fff; border:none; padding:6px 10px; border-radius:5px; margin-right:5px; }

/* Inputs */
input[type=date], input[type=time] { padding:5px; border-radius:5px; border:1px solid #ccc; margin-right:5px; }
</style>
</head>
<body>

<div class="sidebar">
    <div>
        <h2>Buddy Vet</h2>
        <a href="vet_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="vet_appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
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
    <h3>Recent Appointments</h3>

    <?php while ($row = $recent_result->fetch_assoc()): ?>
    <div class="appointment-card">
        <div>
            <strong><?= htmlspecialchars($row['pet_name']) ?></strong><br>
            Owner: <?= htmlspecialchars($row['user_name']) ?><br>

            <?php if ($row['status'] === 'Confirmed' && !empty($row['appointment_date']) && !empty($row['appointment_time'])): ?>
                Date: <?= htmlspecialchars($row['appointment_date']) ?><br>
                Time: <?= htmlspecialchars($row['appointment_time']) ?><br>
            <?php endif; ?>

            Status:
            <span class="status-badge status-<?= $row['status'] ?>"><?= $row['status'] ?></span>
            Payment:
            <span class="status-badge status-<?= $row['payment_status'] ?>"><?= $row['payment_status'] ?></span>
            Service:
            <span class="status-badge status-<?= $row['service_status'] === 'Completed' ? 'Completed-Service' : 'Pending-Service' ?>"><?= $row['service_status'] ?></span>
        </div>

        <form method="POST" style="margin-top:10px;">
            <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">

            <?php if ($row['status'] === 'Pending'): ?>
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
