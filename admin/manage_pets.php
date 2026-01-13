<?php
session_start();
include(__DIR__ . '/../db.php');

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// ---------------- FETCH PETS ----------------
// Available pets
$available_pets = $conn->query("
    SELECT p.pet_id, p.name, p.dob, p.gender, p.status, p.adoption_fee, p.image,
           p.created_at,
           pt.species, pt.breed
    FROM pet p
    LEFT JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE p.status != 'Adopted'
    ORDER BY p.pet_id DESC
");

// Adopted pets
$adopted_pets = $conn->query("
    SELECT p.pet_id, p.name, p.dob, p.gender, p.status, p.adoption_fee, p.image,
           p.created_at,
           pt.species, pt.breed
    FROM pet p
    LEFT JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE p.status = 'Adopted'
    ORDER BY p.pet_id DESC
");

// Pet shop name
$shop_name = "Buddy Pet Shop";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Pets</title>
<link rel="stylesheet" href="admin.css">
<style>
.add-btn { padding: 5px 15px; background: #00a8ff; color: #fff; border-radius: 5px; text-decoration: none; float: right; }
.add-btn:hover { background: #0097e6; }

table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 40px; }
th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
th { background: #8a5826; color: #fff; }
tr:nth-child(even) { background: #f7f7f7; }

a.action { color:#00a8ff; text-decoration:none; margin-right: 5px; }
a.action:hover { text-decoration: underline; }
a.delete-link { color: #e84118; text-decoration: none; }
a.delete-link:hover { color: #c23616; }
</style>
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
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

    <!-- Main Content -->
    <div class="main-content">
        <h1>Manage Pets <a href="add_pet.php" class="add-btn">+ Add New Pet</a></h1>

        <!-- Available Pets -->
        <h2>Available Pets</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Age</th><th>Species</th><th>Breed</th>
                    <th>Gender</th><th>Status</th><th>Adoption Fee</th>
                    <th>Pet Shop</th><th>Added Date</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($pet = $available_pets->fetch_assoc()): ?>
                <tr>
                    <td><?= $pet['pet_id'] ?></td>
                    <td><?= htmlspecialchars($pet['name']) ?></td>
                    <td>
                        <?php
                        if (!empty($pet['dob'])) {
                            $age = floor((time() - strtotime($pet['dob'])) / (365*24*60*60));
                            echo $age . ' yrs';
                        } else { echo 'N/A'; }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($pet['species'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($pet['breed'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($pet['gender']) ?></td>
                    <td><?= htmlspecialchars($pet['status']) ?></td>
                    <td>Rs <?= number_format($pet['adoption_fee'], 2) ?></td>
                    <td><?= htmlspecialchars($shop_name) ?></td>
                    <td>
                        <?= !empty($pet['created_at']) ? date("M d, Y", strtotime($pet['created_at'])) : 'N/A'; ?>
                    </td>
                    <td>
                        <a class="action" href="edit_pet.php?id=<?= $pet['pet_id'] ?>">Edit</a>
                        <a class="delete-link" href="delete_pet.php?id=<?= $pet['pet_id'] ?>" onclick="return confirm('Delete this pet?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Adopted Pets -->
        <h2>Adopted Pets</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Age</th><th>Species</th><th>Breed</th>
                    <th>Gender</th><th>Status</th><th>Adoption Fee</th>
                    <th>Pet Shop</th><th>Added Date</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($pet = $adopted_pets->fetch_assoc()): ?>
                <tr>
                    <td><?= $pet['pet_id'] ?></td>
                    <td><?= htmlspecialchars($pet['name']) ?></td>
                    <td>
                        <?php
                        if (!empty($pet['dob'])) {
                            $age = floor((time() - strtotime($pet['dob'])) / (365*24*60*60));
                            echo $age . ' yrs';
                        } else { echo 'N/A'; }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($pet['species'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($pet['breed'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($pet['gender']) ?></td>
                    <td><?= htmlspecialchars($pet['status']) ?></td>
                    <td>Rs <?= number_format($pet['adoption_fee'], 2) ?></td>
                    <td><?= htmlspecialchars($shop_name) ?></td>
                    <td>
                        <?= !empty($pet['created_at']) ? date("M d, Y", strtotime($pet['created_at'])) : 'N/A'; ?>
                    </td>
                    <td>
                        <a class="action" href="edit_pet.php?id=<?= $pet['pet_id'] ?>">Edit</a>
                        <a class="delete-link" href="delete_pet.php?id=<?= $pet['pet_id'] ?>" onclick="return confirm('Delete this pet?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>
