<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_type']) !== 'vet') {
    header("Location: ../login/login.php");
    exit;
}

$vet_id = $_SESSION['user_id'];

// ---------------- FETCH PETS ASSIGNED TO THIS VET ----------------
$stmt = $conn->prepare("
    SELECT DISTINCT
        p.pet_id,
        p.name AS pet_name,
        p.gender AS pet_gender,
        p.dob,
        pt.species,
        pt.breed AS type_breed,
        u.name AS owner_name,
        u.email AS owner_email,
        u.phone AS owner_phone
    FROM vet_appointments va
    JOIN pet p ON va.pet_id = p.pet_id
    JOIN pet_type pt ON p.type_id = pt.type_id
    JOIN users u ON va.user_id = u.user_id
    WHERE va.vet_id = ?
    ORDER BY p.name ASC
");
$stmt->bind_param("i", $vet_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vet Patients | Buddy</title>
<link rel="stylesheet" href="vet.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="sidebar">
    <div>
        <h2>Buddy Vet</h2>
        <a href="Vet_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="vet_appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
        <a class="active"  href="vet_pets.php"><i class="fas fa-paw"></i> Pets</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
 
</div>

<div class="main-content">
    <h2>My Patients</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card">
                <div class="pet-info">
                    <div class="pet-title"><?= htmlspecialchars($row['pet_name']) ?></div>
                    <div>Species: <?= htmlspecialchars($row['species'] ?? 'N/A') ?></div>
                    <div>Breed: <?= htmlspecialchars($row['type_breed'] ?? 'N/A') ?></div>
                    <div>Gender: <?= htmlspecialchars($row['pet_gender']) ?></div>
                    <div>
                        Age: 
                        <?php 
                        if (!empty($row['dob']) && $row['dob'] !== '0000-00-00') {
                            $dob = new DateTime($row['dob']);
                            $today = new DateTime();
                            echo $today->diff($dob)->y . ' yrs';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div class="owner">
                        Owner: <?= htmlspecialchars($row['owner_name']) ?><br>
                        Email: <?= htmlspecialchars($row['owner_email']) ?><br>
                        Phone: <?= htmlspecialchars($row['owner_phone']) ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No patients assigned yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
