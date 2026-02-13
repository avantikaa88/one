<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {

    header("Location: ../login/login.php");
    exit;
}


$users = $conn->query("SELECT * FROM users WHERE roles != 'admin' ORDER BY register_date DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
<link rel="stylesheet" href="adminn.css" />

</head>
<body>

<div class="dashboard-container">


    <div class="sidebar">
        <h2>Buddy Admin</h2>
    <ul>
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a class="active" href="manage_users.php">Manage Users</a></li>
        <li><a href="manage_vet.php">Manage Vet</a></li>
        <li><a href="manage_pets.php">Manage Pets</a></li>
        <li><a href="adoption_request.php">Adoption Requests</a></li>
        <li><a href="admin_appointments.php">Appointments</a></li>
    </ul>
        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

    <div class="main-content">
        <h1>Manage Users</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <table>
            <thead>
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
            </thead>
            <tbody>
            <?php
            $type_color = [
                'user' => 'color:#666;',
                'vet' => 'color:#0097e6;',
                'shelter' => 'color:#44bd32;'
            ];
            ?>
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
                 <span style="<?= $type_color[$user['roles']] ?? '' ?>">
                    <?= htmlspecialchars($user['roles']) ?>
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
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
