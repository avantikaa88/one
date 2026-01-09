<?php
session_start();
include(__DIR__ . '/../db.php');

/* ---------------- ADMIN CHECK ---------------- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

/* ---------------- FETCH PET TYPES ---------------- */
$pet_types = $conn->query("SELECT type_id, species, breed FROM pet_type ORDER BY species, breed");

/* ---------------- FETCH PET TO EDIT ---------------- */
$pet_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pet_id <= 0) die("Invalid Pet ID");

$stmt = $conn->prepare("SELECT * FROM pet WHERE pet_id = ?");
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pet) die("Pet not found");

/* ---------------- INIT ---------------- */
$errors = [];
$success = '';
$image_path = $pet['image'] ?? '';
$type_option = 'existing'; // default

/* ---------------- FORM SUBMIT ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $adoption_fee = (float)($_POST['adoption_fee'] ?? 0);
    $status = 'Available'; // FORCE STATUS

    // Handle pet type selection
    $type_option = $_POST['type_option'] ?? '';
    $type_id = 0;

    if ($type_option === 'existing') {
        if (!empty($_POST['existing_type_id'])) {
            $type_id = (int)$_POST['existing_type_id'];
        } else {
            $errors[] = "Please select a pet type from the list.";
        }
    } elseif ($type_option === 'new') {
        $new_species = trim($_POST['new_species'] ?? '');
        $new_breed = trim($_POST['new_breed'] ?? '');
        $new_size = trim($_POST['new_size'] ?? '');
        $new_life_span = trim($_POST['new_life_span'] ?? '');

        if (!$new_species) $errors[] = "Species is required for new type.";
        if (!$new_breed) $errors[] = "Breed is required for new type.";
        if (!$new_size) $errors[] = "Size is required for new type.";
        if (!$new_life_span) $errors[] = "Life span is required for new type.";

        if (empty($errors)) {
            $check_stmt = $conn->prepare("SELECT type_id FROM pet_type WHERE species = ? AND breed = ?");
            $check_stmt->bind_param("ss", $new_species, $new_breed);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            if ($result->num_rows > 0) {
                $type_id = $result->fetch_assoc()['type_id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO pet_type (species, breed, size, life_span) VALUES (?,?,?,?)");
                $stmt->bind_param("ssss", $new_species, $new_breed, $new_size, $new_life_span);
                $stmt->execute();
                $type_id = $stmt->insert_id;
                $stmt->close();
            }
            $check_stmt->close();
        }
    } else {
        $errors[] = "Please select a pet type option.";
    }

    /* ---------------- VALIDATION ---------------- */
    if ($name === '') $errors[] = "Pet name is required.";
    if (!in_array($gender, ['Male','Female'])) $errors[] = "Select valid gender.";
    if (!$dob || strtotime($dob) > time()) $errors[] = "Enter valid DOB.";
    if ($type_id <= 0) $errors[] = "Select valid pet type.";
    if ($adoption_fee < 0) $errors[] = "Invalid adoption fee.";

    /* ---------------- IMAGE UPLOAD ---------------- */
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) $errors[] = "Invalid image format.";
        if ($file_size > 5*1024*1024) $errors[] = "Max file size is 5MB.";

        if (empty($errors)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('pet_', true) . '.' . $ext;
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                // Delete old image if exists
                if (!empty($image_path) && file_exists('../'.$image_path)) unlink('../'.$image_path);
                $image_path = 'uploads/' . $image_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    /* ---------------- UPDATE PET ---------------- */
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "UPDATE pet SET name=?, gender=?, dob=?, status=?, type_id=?, description=?, adoption_fee=?, image=? WHERE pet_id=?"
        );
        // FIXED bind_param types: 9 variables
        $stmt->bind_param(
            "ssssisdsi",
            $name,
            $gender,
            $dob,
            $status,
            $type_id,
            $description,
            $adoption_fee,
            $image_path,
            $pet_id
        );
        if ($stmt->execute()) {
            $success = "Pet updated successfully!";
            $stmt->close();
            $pet = $conn->query("SELECT * FROM pet WHERE pet_id = $pet_id")->fetch_assoc();
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
<title>Edit Pet</title>
<style>
body { font-family: Arial; background:#f5f6fa; padding:20px; }
form { background:#fff; padding:20px; border-radius:10px; max-width:600px; margin:auto; }
input, select, textarea { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc; box-sizing: border-box; }
button { padding:10px 20px; background:#00a8ff; color:#fff; border:none; border-radius:5px; cursor:pointer; }
button:hover { background:#0097e6; }
.error { color:red; margin:5px 0; padding:10px; background:#ffe6e6; border-radius:5px; border-left:4px solid red; }
.success { color:green; margin:5px 0; padding:10px; background:#e6ffe6; border-radius:5px; border-left:4px solid green; }
fieldset { margin-top:20px; padding:15px; border:1px solid #ddd; }
legend { font-weight:bold; padding:0 10px; }
.radio-group { margin:15px 0; padding:15px; background:#f0f8ff; border-radius:5px; }
.radio-group label { display:inline-block; margin-right:20px; }
.type-section { margin:15px 0; padding:15px; background:#f9f9f9; border-radius:5px; }
.image-preview { margin:15px 0; text-align: center; }
.image-preview img { max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 5px; padding: 5px; background: #f9f9f9; }
</style>
<script>
function toggleTypeFields() {
    const typeOption = document.querySelector('input[name="type_option"]:checked');
    if (!typeOption) return;
    const value = typeOption.value;
    document.getElementById('existing-type-section').style.display = value==='existing' ? 'block' : 'none';
    document.getElementById('new-type-section').style.display = value==='new' ? 'block' : 'none';
}

function previewImage(event) {
    const reader = new FileReader();
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    reader.onload = function() {
        preview.style.display = 'block';
        previewImg.src = reader.result;
    }
    if (event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
}

document.addEventListener('DOMContentLoaded', toggleTypeFields);
</script>
</head>
<body>

<h1>Edit Pet</h1>
<a href="manage_pets.php"><- Back to Pets</a>

<?php 
if ($errors) foreach($errors as $e) echo "<p class='error'>$e</p>";
if ($success) echo "<p class='success'>$success</p>";
?>

<form method="POST" enctype="multipart/form-data">
    <label>Pet Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? $pet['name']) ?>" required>

    <label>Gender:</label>
    <select name="gender" required>
        <option value="">-- Select --</option>
        <option value="Male" <?= (($_POST['gender'] ?? $pet['gender'])=='Male')?'selected':'' ?>>Male</option>
        <option value="Female" <?= (($_POST['gender'] ?? $pet['gender'])=='Female')?'selected':'' ?>>Female</option>
    </select>

    <label>Date of Birth:</label>
    <input type="date" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? $pet['dob']) ?>" required>

    <label>Description:</label>
    <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? $pet['description']) ?></textarea>

    <label>Adoption Fee:</label>
    <input type="number" step="0.01" name="adoption_fee" value="<?= htmlspecialchars($_POST['adoption_fee'] ?? $pet['adoption_fee']) ?>" required>

    <!-- Pet Type -->
    <fieldset class="type-section">
        <legend>Pet Type</legend>
        <div class="radio-group">
            <label>
                <input type="radio" name="type_option" value="existing" <?= ($type_option=='existing')?'checked':'' ?> onclick="toggleTypeFields()">
                Existing Type
            </label>
            <label>
                <input type="radio" name="type_option" value="new" <?= ($type_option=='new')?'checked':'' ?> onclick="toggleTypeFields()">
                Add New Type
            </label>
        </div>

        <div id="existing-type-section">
            <label>Select Type:</label>
            <select name="existing_type_id">
                <option value="">-- Select Type --</option>
                <?php
                $pet_types->data_seek(0);
                while($row = $pet_types->fetch_assoc()) {
                    $selected = (($row['type_id'] ?? 0) == ($pet['type_id'] ?? 0)) ? 'selected' : '';
                    echo "<option value='{$row['type_id']}' $selected>{$row['species']} - {$row['breed']}</option>";
                }
                ?>
            </select>
        </div>

        <div id="new-type-section" style="display:none;">
            <label>Species:</label>
            <input type="text" name="new_species" value="<?= htmlspecialchars($_POST['new_species'] ?? '') ?>">

            <label>Breed:</label>
            <input type="text" name="new_breed" value="<?= htmlspecialchars($_POST['new_breed'] ?? '') ?>">

            <label>Size:</label>
            <input type="text" name="new_size" value="<?= htmlspecialchars($_POST['new_size'] ?? '') ?>">

            <label>Life Span:</label>
            <input type="text" name="new_life_span" value="<?= htmlspecialchars($_POST['new_life_span'] ?? '') ?>">
        </div>
    </fieldset>

    <!-- Image Upload -->
    <div class="image-preview" id="image-preview" style="<?= $image_path?'display:block':'display:none' ?>">
        <img id="preview-img" src="../<?= $image_path ?>" alt="Pet Image">
    </div>
    <input type="file" name="image" accept="image/*" onchange="previewImage(event)">

    <button type="submit">Update Pet</button>
</form>

</body>
</html>
