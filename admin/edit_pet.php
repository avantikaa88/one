<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Validate pet ID
$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pet_id <= 0) die("Invalid Pet ID");

// Fetch pet and pet types
$stmt = $conn->prepare("SELECT * FROM pet WHERE pet_id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pet) die("Pet not found");

$types_result = $conn->query("SELECT type_id, species, breed FROM pet_type ORDER BY species, breed");

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $gender = $_POST['gender'];
    $status = trim($_POST['status']);
    $type_id = (int)$_POST['type_id'];
    $adoption_fee = floatval($_POST['adoption_fee']);

    // Basic validation
    if ($name === '') $errors[] = "Name is required";
    if ($age < 0) $errors[] = "Age must be 0 or higher";
    if (!in_array($gender, ['Male','Female','Other'])) $errors[] = "Invalid gender selected";
    if ($status === '') $errors[] = "Status is required";
    if ($adoption_fee < 0) $errors[] = "Adoption fee must be 0 or higher";

    if (!$errors) {
        $stmt = $conn->prepare("UPDATE pet SET name=?, age=?, gender=?, status=?, type_id=?, adoption_fee=? WHERE pet_id=?");
        $stmt->bind_param("sissdii", $name, $age, $gender, $status, $type_id, $adoption_fee, $pet_id);
        if ($stmt->execute()) {
            $success = "Pet updated successfully!";
            // Refresh pet data
            $pet = $conn->query("SELECT * FROM pet WHERE pet_id = $pet_id")->fetch_assoc();
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Pet</title>
<style>
body { font-family: Arial, sans-serif; padding: 20px; background:#f5f6fa; }
form { background: #fff; padding: 20px; border-radius: 8px; max-width: 500px; }
input, select, button { width: 100%; padding: 10px; margin: 8px 0; border-radius: 5px; border: 1px solid #ccc; }
button { background: #4CAF50; color: white; border: none; cursor: pointer; }
button:hover { background: #45a049; }
p.success { color: green; }
p.error { color: red; }
</style>
</head>
<body>

<h1>Edit Pet</h1>
<a href="manage_pets.php">← Back to Pets</a>

<?php foreach($errors as $e) echo "<p class='error'>$e</p>"; ?>
<?php if ($success) echo "<p class='success'>$success</p>"; ?>

<form method="post">
    <label>Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($pet['name']) ?>" required>

    <label>Age:</label>
    <input type="number" name="age" value="<?= $pet['age'] ?>" min="0" required>

    <label>Gender:</label>
    <select name="gender" required>
        <option value="Male" <?= $pet['gender']=='Male'?'selected':'' ?>>Male</option>
        <option value="Female" <?= $pet['gender']=='Female'?'selected':'' ?>>Female</option>
        <option value="Other" <?= $pet['gender']=='Other'?'selected':'' ?>>Other</option>
    </select>

    <label>Status:</label>
    <input type="text" name="status" value="<?= htmlspecialchars($pet['status']) ?>" required>

    <label>Type:</label>
    <select name="type_id" required>
        <?php while($type = $types_result->fetch_assoc()): ?>
        <option value="<?= $type['type_id'] ?>" <?= $pet['type_id']==$type['type_id']?'selected':'' ?>>
            <?= htmlspecialchars($type['species'].' - '.$type['breed']) ?>
        </option>
        <?php endwhile; ?>
    </select>

    <label>Adoption Fee (NPR):</label>
    <input type="number" name="adoption_fee" step="0.01" min="0" value="<?= number_format($pet['adoption_fee'], 2) ?>" required>

    <button type="submit">Update Pet</button>
</form>

</body>
</html>
