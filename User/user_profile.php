<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Profile</title>
<style>
    body { font-family: Arial,sans-serif; background:#f5f5f5; margin:0; padding:0; }
    header { background:#fff; padding:15px 20px; box-shadow:0 0 5px rgba(0,0,0,0.1); display:flex; justify-content:space-between; align-items:center; }
    header h2 { margin:0; }
    a.login { text-decoration:none; color:#333; font-weight:bold; }
    .profile-container { max-width:600px; background:#fff; margin:40px auto; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
    .profile-container h2 { margin-bottom:20px; }
    .profile-container p { margin:8px 0; }
    .profile-container strong { color:#444; }
    img { max-width:150px; border-radius:8px; margin:10px 0; display:block; }
    input[type="file"] { margin:8px 0; }
    button { padding:10px 15px; margin-top:10px; cursor:pointer; }
</style>
</head>
<body>

<header>
<h2>Buddy</h2>
<a href="../logout.php" class="login">Logout</a>
</header>

<div class="profile-container">
<h2>User Profile</h2>

<!-- Display photos safely -->
<?php if (!empty($user['profile_photo'])): ?>
<p><strong>Profile Photo:</strong></p>
<img src="../uploads/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo">
<?php endif; ?>

<?php if (!empty($user['id_photo'])): ?>
<p><strong>ID Photo:</strong></p>
<img src="../uploads/<?php echo htmlspecialchars($user['id_photo']); ?>" alt="ID Photo">
<?php endif; ?>

<!-- User Info safely -->
<p><strong>User ID:</strong> <?php echo $user['id'] ?? ''; ?></p>
<p><strong>Username:</strong> <?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
<p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name'] ?? ''); ?></p>
<p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? ''); ?></p>
<p><strong>Registration Date:</strong> <?php echo htmlspecialchars($user['registration_date'] ?? ''); ?></p>
<p><strong>Legal ID:</strong> <?php echo htmlspecialchars($user['legal_id'] ?? ''); ?></p>
<p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
<p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? ''); ?></p>
<p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?? ''); ?></p>

<!-- Upload Form -->
<h3>Upload Photos</h3>
<form method="post" enctype="multipart/form-data" action="upload_photos.php">
    <label>Profile Photo:</label>
    <input type="file" name="profile_photo" accept="image/*"><br>
    <label>ID Photo:</label>
    <input type="file" name="id_photo" accept="image/*"><br>
    <button type="submit">Upload</button>
</form>
</div>

</body>
</html>
