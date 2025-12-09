<?php
session_start();
include(__DIR__ . '/../db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

// Redirect non-admin users
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Fetch pets with type
$pets = $conn->query("
SELECT p.pet_id, p.name AS pet_name, p.age, p.gender, p.status, 
       pt.species, pt.breed, p.adoption_fee
FROM pet p
LEFT JOIN pet_type pt ON p.type_id = pt.type_id
ORDER BY p.pet_id DESC
");

// Shop name
$shop_name = "Buddy Pet Shop";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Pets</title>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background-color: #f5f6fa; color: #333; }

    .dashboard-container { display: flex; min-height: 100vh; }

    /* Sidebar */
    .sidebar { 
        width: 220px; 
        background-color: #2f3640; 
        color: #fff; 
        padding: 20px; 
        position: fixed; 
        height: 100vh; 
    }
    .sidebar h2 { margin-bottom: 30px; text-align: center; }
    .sidebar ul { list-style: none; }
    .sidebar ul li { margin: 15px 0; }
    .sidebar ul li a { 
        color: #fff; 
        text-decoration: none; 
        padding: 8px 10px; 
        display: block; 
        border-radius: 5px; 
    }
    .sidebar ul li a:hover, .active {
        background-color: #00a8ff;
    }
    .logout-btn {
        display: block;
        margin-top: 20px;
        padding: 10px 20px;
        background: #e84118;
        text-align: center;
        color: #fff;
        border-radius: 5px;
        text-decoration: none;
    }
    .logout-btn:hover { background: #c23616; }

    /* Main Content */
    .main-content { 
        flex: 1; 
        padding: 40px; 
        margin-left: 220px; 
    }

    h1 { margin-bottom: 20px; }

    .add-btn { 
        padding: 8px 15px; 
        background: #00a8ff; 
        color: white; 
        text-decoration: none; 
        border-radius: 5px; 
        font-size: 14px;
    }
    .add-btn:hover { background: #0097e6; }

    /* Table */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        border-radius: 10px; 
        overflow: hidden; 
        margin-top: 20px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
    }
    th, td { padding: 12px; border: 1px solid #ccc; }
    th { background:#2f3640; color:#fff; }
    tr:nth-child(even) { background:#f7f7f7; }

    a.action { color:#00a8ff; text-decoration:none; }
    a.action:hover { text-decoration: underline; }
    .delete-link { color:#e84118; }
    .delete-link:hover { color:#c23616; }
</style>

</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Buddy Admin</h2>

        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a class="active" href="manage_pets.php">Manage Pets</a></li>
            <li><a href="adoption_requests.php">Adoption Requests</a></li>
            <li><a href="appointments.php">Appointments</a></li>
        </ul>

        <a href="../logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <h1>Manage Pets 
            <a href="add_pet.php" class="add-btn">+ Add New Pet</a>
        </h1>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Age</th>
                <th>Gender</th>
                <th>Status</th>
                <th>Species</th>
                <th>Breed</th>
                <th>Adoption Fee (NPR)</th>
                <th>Pet Shop</th>
                <th>Action</th>
            </tr>

            <?php while($pet = $pets->fetch_assoc()): ?>
            <tr>
                <td><?= $pet['pet_id'] ?></td>
                <td><?= htmlspecialchars($pet['pet_name']) ?></td>
                <td><?= $pet['age'] ?></td>
                <td><?= htmlspecialchars($pet['gender']) ?></td>
                <td><?= htmlspecialchars($pet['status']) ?></td>
                <td><?= htmlspecialchars($pet['species']) ?></td>
                <td><?= htmlspecialchars($pet['breed']) ?></td>
                <td>रु <?= number_format($pet['adoption_fee'], 2) ?></td>
                <td><?= htmlspecialchars($shop_name) ?></td>
                <td>
                    <a class="action" href="edit_pet.php?id=<?= $pet['pet_id'] ?>">Edit</a> | 
                    <a class="delete-link" href="delete_pet.php?id=<?= $pet['pet_id'] ?>" 
                       onclick="return confirm('Delete this pet?\nThis cannot be undone!')">
                       Delete
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>

        </table>

    </div>

</div>

</body>
</html>
