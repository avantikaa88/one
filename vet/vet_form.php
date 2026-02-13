<?php
session_start();
include(__DIR__ . '/../db.php');

/* ---------------- AUTH CHECK ---------------- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$pet_id  = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;

/* ---------------- INITIAL ERROR CHECK ---------------- */
$success = '';
$error = '';

if ($pet_id <= 0) {
    $error = "Please select a pet before booking an appointment.";
}

/* ---------------- FETCH PET INFO ---------------- */
$pet_name = '';
if ($pet_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM pet WHERE pet_id=?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $pet = $stmt->get_result()->fetch_assoc();
    if ($pet) $pet_name = $pet['name'];
    $stmt->close();
}

/* ---------------- HANDLE FORM ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pet_id > 0) {

    $service_type = $_POST['service_type'] ?? '';
    $reason = trim($_POST['reason'] ?? '');

    $appointment_date = date('Y-m-d');
    $appointment_time = date('H:i:s');

    // check existing upcoming appointment for this pet
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS cnt
        FROM vet_appointments
        WHERE user_id=? AND pet_id=? AND appointment_date >= CURDATE()
    ");
    $stmt->bind_param("ii", $user_id, $pet_id);
    $stmt->execute();
    $check = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($check['cnt'] > 0) {
        $error = "You already have an upcoming appointment for this pet.";
    } else {

        // vet_id is NULL (admin assigns later)
        $stmt = $conn->prepare("
            INSERT INTO vet_appointments
            (user_id, pet_id, vet_id, service_type, reason, appointment_date, appointment_time, status)
            VALUES (?, ?, NULL, ?, ?, ?, ?, 'Pending')
        ");

        $stmt->bind_param(
            "iissss",
            $user_id,
            $pet_id,
            $service_type,
            $reason,
            $appointment_date,
            $appointment_time
        );

        if ($stmt->execute()) {
            $success = "Appointment request sent! Admin will assign a vet soon.";
        } else {
            $error = "Failed to request appointment.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Request Vet Appointment</title>

<style>
body { font-family: Arial; background: #f4f6f8; margin: 0; padding: 0; }
.container { max-width: 520px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
h1 { text-align: center; color: #388E3C; margin-bottom: 20px; }
label { display: block; margin: 10px 0 5px; font-weight: bold; }
input, select, textarea { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; margin-bottom: 15px; }
textarea { resize: vertical; }
button { width: 100%; padding: 10px; background: #4CAF50; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; }
button:hover { background: #2E7D32; }
.success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
.error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
.back-link { display: inline-block; margin-top: 10px; color: #4CAF50; text-decoration: none; }
.back-link:hover { text-decoration: underline; }
</style>
</head>

<body>
<div class="container">
<h1>Veterinary Appointment Request</h1>

<?php if (!empty($success)) echo "<div class='success'>$success</div>"; ?>
<?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

<?php if ($pet_id > 0): ?>
<form method="post">

    <label>Pet</label>
    <input type="text" value="<?= htmlspecialchars($pet_name) ?>" readonly>

    <label>User ID</label>
    <input type="text" value="<?= $user_id ?>" readonly>

    <label>Service Type</label>
    <select name="service_type" required>
        <option value="">Select Service</option>
        <option value="General Check-up">General Check-up</option>
        <option value="Vaccination">Vaccination</option>
        <option value="Surgery Consultation">Surgery Consultation</option>
        <option value="Emergency">Emergency</option>
    </select>

    <label>Reason / Details</label>
    <textarea name="reason" required></textarea>

    <button type="submit">Request Appointment</button>
</form>
<?php endif; ?>

<a href="../user/vet_booking.php" class="back-link">← Back to My Pets</a>
</div>
</body>
</html>
