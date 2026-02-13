<?php
session_start();
include(__DIR__ . '/../db.php');


if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

$available_pets = $conn->query("
    SELECT p.pet_id, p.name, p.dob, p.gender, p.status, p.adoption_fee, p.image,
           p.created_at,
           pt.species, pt.breed,
           aa.status AS adoption_request_status
    FROM pet p
    LEFT JOIN pet_type pt ON p.type_id = pt.type_id
    LEFT JOIN (
        SELECT pet_id, status
        FROM adoption_application
        WHERE status = 'Pending'
    ) aa ON p.pet_id = aa.pet_id
    WHERE p.status != 'Adopted'
    ORDER BY p.pet_id DESC
");


$adopted_pets = $conn->query("
    SELECT p.pet_id, p.name, p.dob, p.gender, p.status, p.adoption_fee, p.image,
           p.created_at,
           pt.species, pt.breed
    FROM pet p
    LEFT JOIN pet_type pt ON p.type_id = pt.type_id
    WHERE p.status = 'Adopted'
    ORDER BY p.pet_id DESC
");


$shop_name = "Buddy Pet Shop";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Pets</title>
<link rel="stylesheet" href="adminn.css">
</head>
<body>

<div class="dashboard-container">

  
    <div class="sidebar">
        <h2>Buddy Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_vet.php">Manage Vet</a></li>
            <li><a class="active" href="manage_pets.php">Manage Pets</a></li>
            <li><a href="adoption_request.php">Adoption Requests</a></li>
            <li><a href="admin_appointments.php">Appointments</a></li>
        </ul>
        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

    <div class="main-content">
        <h1>Manage Pets <a href="add_pet.php" class="add-button">+ Add New Pet</a></h1>

        
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
                    <td>
                        <?php
                        
                        if (!empty($pet['adoption_request_status']) && $pet['adoption_request_status'] === 'Pending') {
                            echo 'Pending';
                        } else {
                            echo htmlspecialchars($pet['status']); 
                        }
                        ?>
                    </td>
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
