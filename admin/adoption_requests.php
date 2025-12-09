<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}


$apps = $conn->query("
    SELECT aa.adoption_id, aa.status, aa.payment_status, aa.adoption_date,
           aa.legal_id_image,
           u.name AS user_name,
           p.name AS pet_name
    FROM adoption_application aa
    JOIN users u ON aa.user_id = u.user_id
    JOIN pet p ON aa.pet_id = p.pet_id
    ORDER BY aa.adoption_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Adoption Requests</title>
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

    
    table { 
        width: 100%; 
        border-collapse: collapse; 
        background: #fff; 
        border-radius: 10px; 
        overflow: hidden; 
        margin-top: 20px; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
    }
    th, td { padding: 12px; border: 1px solid #ccc; text-align: left; }
    th { background:#2f3640; color:#fff; }
    tr:nth-child(even) { background:#f7f7f7; }

    a.action { color:#00a8ff; text-decoration:none; }
    a.action:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="dashboard-container">

  
    <div class="sidebar">
        <h2>Buddy Admin</h2>

        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="manage_pets.php">Manage Pets</a></li>
            <li><a class="active" href="adoption_requests.php">Adoption Requests</a></li>
            <li><a href="appointments.php">Appointments</a></li>
        </ul>

        <a href="../logout.php" class="logout-button">Logout</a>
    </div>

   
    <div class="main-content">
        <h1>Adoption Requests</h1>

        <table>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Pet</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Application Date</th>
                <th>Legal ID</th>
                <th>Action</th>
            </tr>

            <?php while($app = $apps->fetch_assoc()): ?>
            <tr>
                <td><?= $app['adoption_id'] ?></td>
                <td><?= htmlspecialchars($app['user_name']) ?></td>
                <td><?= htmlspecialchars($app['pet_name']) ?></td>
                <td><?= htmlspecialchars($app['status']) ?></td>
                <td><?= htmlspecialchars($app['payment_status']) ?></td>
                <td>
                    <?php if (!empty($app['adoption_date'])): ?>
                        <?= date('F j, Y', strtotime($app['adoption_date'])) ?>
                    <?php else: ?>
                        Not set
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($app['legal_id_image'])): ?>
                        <a href="view_id.php?id=<?= $app['adoption_id'] ?>" target="_blank">View File</a>
                    <?php else: ?>
                        No File
                    <?php endif; ?>
                </td>
                <td>
                    <a class="action" href="approve_adoption.php?id=<?= $app['adoption_id'] ?>&action=approve">Approve</a> |
                    <a class="action" href="approve_adoption.php?id=<?= $app['adoption_id'] ?>&action=reject">Reject</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>

</body>
</html>