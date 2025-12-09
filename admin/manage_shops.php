<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Fetch pet shops
$shops = $conn->query("SELECT * FROM pet_shop ORDER BY shop_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Pet Shops</title>
<style>
body { font-family: Arial, sans-serif; background:#f5f6fa; }
table { width:100%; border-collapse: collapse; margin:20px 0; background:#fff; border-radius:10px; overflow:hidden; }
th, td { padding:10px; border:1px solid #ccc; text-align:left; }
th { background:#2f3640; color:#fff; }
a { text-decoration:none; color:#00a8ff; }
a:hover { text-decoration:underline; }
</style>
</head>
<body>
<h2>Manage Pet Shops</h2>
<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>Address</th>
<th>Phone</th>
<th>Email</th>
<th>Manager</th>
<th>Action</th>
</tr>
<?php while($shop = $shops->fetch_assoc()): ?>
<tr>
<td><?= $shop['shop_id'] ?></td>
<td><?= htmlspecialchars($shop['name']) ?></td>
<td><?= htmlspecialchars($shop['address']) ?></td>
<td><?= htmlspecialchars($shop['phone_no']) ?></td>
<td><?= htmlspecialchars($shop['email']) ?></td>
<td><?= htmlspecialchars($shop['manager_name']) ?></td>
<td>
<a href="edit_shop.php?id=<?= $shop['shop_id'] ?>">Edit</a> | 
<a href="delete_shop.php?id=<?= $shop['shop_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>
</table>
<a href="admin_dashboard.php">Back to Dashboard</a>
</body>
</html>
