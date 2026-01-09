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
        p.status,
        p.description,
        p.adoption_fee,
        p.image,
        pt.species,
        pt.breed AS type_breed,
        pt.size AS type_size,
        pt.life_span AS type_life_span,
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
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', sans-serif; }
body { background:#f5f6fa; }

/* Sidebar */
.sidebar {
    width:240px; height:100vh; background:#2f3640; color:#fff; position:fixed; padding:20px;
}
.sidebar h2 { text-align:center; margin-bottom:20px; }
.sidebar a { display:block; color:#fff; text-decoration:none; padding:12px; border-radius:6px; margin-bottom:5px; }
.sidebar a:hover { background:#353b48; }
.container { margin-left:260px; padding:30px; }

/* Pet Cards */
.card { background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:15px; }
.pet-title { font-size:18px; font-weight:bold; margin-bottom:8px; }
.owner { margin-top:8px; color:#555; }
img { border-radius:6px; max-width:120px; }

/* Table-like Flex */
.pet-card-flex { display:flex; align-items:flex-start; gap:20px; }
.pet-info { flex:1; }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Buddy Vet</h2>
    <a href="Vet_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="vet_appointment.php"><i class="fas fa-calendar"></i> Appointments</a>
    <a href="vet_pets.php"><i class="fas fa-paw"></i> Pets</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">
    <h2>My Patients</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card pet-card-flex">
                <?php if (!empty($row['image'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['pet_name']) ?>">
                <?php else: ?>
                    <img src="../uploads/default_pet.png" alt="No Image">
                <?php endif; ?>

                <div class="pet-info">
                    <div class="pet-title"><?= htmlspecialchars($row['pet_name']) ?></div>
                    <div>Species: <?= htmlspecialchars($row['species'] ?? 'N/A') ?></div>
                    <div>Breed: <?= htmlspecialchars($row['type_breed'] ?? 'N/A') ?></div>
                    <div>Gender: <?= htmlspecialchars($row['pet_gender']) ?></div>
                    <div>
                        Age: 
                        <?php 
                        if (!empty($row['dob'])) {
                            $dob = new DateTime($row['dob']);
                            $today = new DateTime();
                            $age = $today->diff($dob)->y;
                            echo $age . ' yrs';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div>Status: <?= htmlspecialchars($row['status']) ?></div>
                    <div>Description: <?= htmlspecialchars($row['description']) ?></div>
                    <div>Adoption Fee: Rs <?= number_format($row['adoption_fee'],2) ?></div>
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
