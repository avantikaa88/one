<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header("Location: manage_users.php");
    exit;
}


$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: manage_users.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']);
    $user_name = trim($_POST['user_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $gender    = trim($_POST['gender']);
    $address   = trim($_POST['address']);

    if ($name === '') $errors[] = "Full name is required";
    if ($user_name === '') $errors[] = "Username is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if ($phone === '') $errors[] = "Phone number is required";
    if ($address === '') $errors[] = "Address is required";
    if (!in_array($gender, ['Male','Female','Other'])) $errors[] = "Invalid gender selected";

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_name = ? AND user_id != ?");
    $stmt->bind_param("si", $user_name, $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "Username already exists";
    $stmt->close();


    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "Email already exists";
    $stmt->close();

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, user_name = ?, email = ?, phone = ?, gender = ?, address = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param(
            "ssssssi",
            $name, $user_name, $email, $phone, $gender, $address, $user_id
        );

        if ($stmt->execute()) {
            $success = "User updated successfully!";
            $stmt->close();

            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>

<style>
body {
    font-family: Arial, sans-serif;
    background:#f5f6fa;
    padding:20px;
}
.form-container {
    background:#fff;
    padding:20px;
    border-radius:10px;
    max-width:500px;
    margin:auto;
}
input, select, textarea {
    width:100%;
    padding:10px;
    margin:10px 0;
    border-radius:5px;
    border:1px solid #ccc;
}
button {
    padding:10px 20px;
    background:#00a8ff;
    color:#fff;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
button:hover {
    background:#0097e6;
}
.error {
    color:red;
    background:#ffebee;
    padding:10px;
    border-radius:5px;
    margin:10px 0;
}
.success {
    color:green;
    background:#e8f5e9;
    padding:10px;
    border-radius:5px;
    margin:10px 0;
}
.back-link {
    display:inline-block;
    margin-bottom:20px;
    color:#00a8ff;
    text-decoration:none;
}
.back-link:hover {
    text-decoration:underline;
}
</style>
</head>

<body>

<a href="manage_users.php" class="back-link">← Back to Users</a>

<div class="form-container">
<h1>Edit User: <?= htmlspecialchars($user['name']) ?></h1>

<?php if (!empty($errors)): ?>
<div class="error">
    <?php foreach ($errors as $error): ?>
        <p><?= $error ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="success">
    <p><?= $success ?></p>
</div>
<?php endif; ?>

<form method="post">
    <label>Full Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

    <label>Username:</label>
    <input type="text" name="user_name" value="<?= htmlspecialchars($user['user_name']) ?>" required>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

    <label>Phone:</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

    <label>Gender:</label>
    <select name="gender" required>
        <option value="Male"   <?= $user['gender']=='Male'?'selected':'' ?>>Male</option>
        <option value="Female" <?= $user['gender']=='Female'?'selected':'' ?>>Female</option>
        <option value="Other"  <?= $user['gender']=='Other'?'selected':'' ?>>Other</option>
    </select>

    <label>Address:</label>
    <textarea name="address" rows="3" required><?= htmlspecialchars($user['address']) ?></textarea>

    <button type="submit">Update User</button>
</form>
</div>

</body>
</html>
