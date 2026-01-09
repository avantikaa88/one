<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'vet') {
    header("Location: ../login/login.php");
    exit;
}

$vet_id = $_SESSION['user_id'];

// ---------------- HANDLE APPROVE ----------------
if (isset($_POST['approve'])) {
    $id = intval($_POST['appointment_id']);
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];

    if ($date < date('Y-m-d')) {
        $_SESSION['error'] = "You cannot select a past date.";
    } else {
        $stmt = $conn->prepare("UPDATE vet_appointments SET status='Confirmed', appointment_date=?, appointment_time=? WHERE id=? AND vet_id=?");
        $stmt->bind_param("ssii", $date, $time, $id, $vet_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: vet_appointment.php");
    exit;
}

// ---------------- HANDLE CANCEL ----------------
if (isset($_POST['cancel'])) {
    $id = intval($_POST['appointment_id']);
    $stmt = $conn->prepare("UPDATE vet_appointments SET status='Cancelled' WHERE id=? AND vet_id=?");
    $stmt->bind_param("ii", $id, $vet_id);
    $stmt->execute();
    $stmt->close();
    header("Location: vet_appointment.php");
    exit;
}

// ---------------- FETCH APPOINTMENTS ----------------
$stmt = $conn->prepare("SELECT va.id, va.appointment_date, va.appointment_time, va.status, va.reason, va.service_type, u.name AS user_name, p.name AS pet_name
                        FROM vet_appointments va
                        JOIN users u ON va.user_id = u.user_id
                        JOIN pet p ON va.pet_id = p.pet_id
                        WHERE va.vet_id=?
                        ORDER BY FIELD(va.status,'Pending','Confirmed','Completed','Cancelled'), va.appointment_date ASC");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vet Appointments | Buddy Vet</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { background:#f5f6fa; }

.sidebar {
    width:240px; 
    height:100vh; 
    background:#2f3640; 
    color:#fff; 
    position:fixed; 
    padding:20px;
}
.sidebar h2 { text-align:center; margin-bottom:20px; }
.sidebar a { display:block; color:#fff; text-decoration:none; padding:12px; border-radius:6px; margin-bottom:5px; }
.sidebar a:hover { background:#353b48; }

.main { margin-left:260px; padding:30px; }

.status { padding:4px 8px; border-radius:12px; font-weight:bold; font-size:12px; text-transform:uppercase; }
.status-Pending { background:#ffe58f; color:#ad8b00; }
.status-Confirmed { background:#b8f2b8; color:#2d7a2d; }
.status-Completed { background:#d0e0ff; color:#004085; }
.status-Cancelled { background:#ffcccc; color:#a80000; }

button { padding:4px 8px; margin:2px; border:none; border-radius:4px; cursor:pointer; }
button.approve { background:#2ecc71; color:#fff; }
button.cancel { background:#f39c12; color:#fff; }

table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; }
table th, table td { border:1px solid #ccc; padding:8px; text-align:left; }
table th { background:#ddd; }
.error { color:red; padding:10px; background:#ffe6e6; margin-bottom:15px; border-radius:5px; }

form { display:inline-block; }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Buddy Vet</h2>
    <a href="vet_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="vet_appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
    <a href="vet_pets.php"><i class="fas fa-paw"></i> Pets</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h2>Manage Appointments</h2>

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
                    <td><span class="status status-<?= $row['status']; ?>"><?= $row['status']; ?></span></td>
                    <td>
                        <?php if($row['status'] === 'Pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="appointment_id" value="<?= $row['id']; ?>">
                                <input type="date" name="appointment_date" required min="<?= date('Y-m-d'); ?>">
                                <input type="time" name="appointment_time" required>
                                <button type="submit" name="approve" class="approve">Approve</button>
                            </form>
                        <?php endif; ?>
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
