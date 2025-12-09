<?php
session_start();
include(__DIR__ . '/../db.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}


if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}


$users = $conn->query("SELECT * FROM users ORDER BY register_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
    body { background-color: #f5f6fa; color: #333; }

    .dashboard-container { display: flex; min-height: 100vh; }

    
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
    .logout-button {
        display: block;
        margin-top: 20px;
        padding: 10px 20px;
        background: #e84118;
        text-align: center;
        color: #fff;
        border-radius: 5px;
        text-decoration: none;
    }
    .logout-button:hover { background: #c23616; }

    
    .main-content { 
        flex: 1; 
        padding: 40px; 
        margin-left: 220px; 
    }

    h1 { margin-bottom: 20px; }

  
    .alert {
        padding: 12px 15px;
        border-radius: 5px;
        margin: 15px 0;
    }
    .alert-success {
        background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;
    }
    .alert-error {
        background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
    }

   
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

    .delete-link { color:#e84118; }
    .delete-link:hover { color:#c23616; }
</style>

</head>
<body>

<div class="dashboard-container">

 
    <div class="sidebar">
        <h2>Buddy Admin</h2>

        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a class="active" href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_pets.php">Manage Pets</a></li>
            <li><a href="adoption_requests.php">Adoption Requests</a></li>
            <li><a href="appointments.php">Appointments</a></li>
        </ul>

        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

   
    <div class="main-content">
        <h1>Manage Users</h1>

        <?php
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
            unset($_SESSION['success']);
        }

        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">'.$_SESSION['error'].'</div>';
            unset($_SESSION['error']);
        }
        ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Gender</th>
                <th>Address</th>
                <th>Type</th>
                <th>Registered</th>
                <th>Action</th>
            </tr>

            <?php while($user = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $user['user_id'] ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['user_name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= htmlspecialchars($user['gender']) ?></td>
                <td><?= htmlspecialchars($user['address']) ?></td>

                <td>
                    <?php 
                    $type_color = [
                        'admin' => 'color:#e84118;font-weight:bold;',
                        'vet' => 'color:#0097e6;',
                        'pet_shop' => 'color:#44bd32;',
                        'user' => 'color:#666;'
                    ];
                    ?>
                    <span style="<?= $type_color[$user['user_type']] ?? '' ?>">
                        <?= htmlspecialchars($user['user_type']) ?>
                    </span>
                </td>

                <td><?= date('M d, Y', strtotime($user['register_date'])) ?></td>

                <td>
                    <a href="edit_user.php?id=<?= $user['user_id'] ?>">Edit</a> | 
                    <a href="delete_user.php?id=<?= $user['user_id'] ?>" 
                       class="delete-link" 
                       onclick="return confirm('Are you sure you want to delete this user?\n\nUser: <?= addslashes($user['name']) ?>\nThis action cannot be undone!')">
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
