<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Fetch existing pet types for dropdown
$pet_types = $conn->query("SELECT type_id, species, breed FROM pet_type ORDER BY species, breed");

$errors = [];
$success = '';
$image_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $gender = $_POST['gender'];
    $status = trim($_POST['status']);
    $adoption_fee = isset($_POST['adoption_fee']) ? (float)$_POST['adoption_fee'] : 0;
    $description = trim($_POST['description'] ?? '');
    $type_option = $_POST['type_option'] ?? '';
    $type_id = 0;

    // Handle image upload
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file_type = mime_content_type($_FILES['image']['tmp_name']);
        $file_size = $_FILES['image']['size'];

        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Allowed: JPG, PNG, GIF, WebP.";
        }
        if ($file_size > $max_size) {
            $errors[] = "File is too large. Maximum size is 5MB.";
        }

        if (empty($errors)) {
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('pet_', true) . '.' . $extension;
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $target_path = $upload_dir . $image_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/' . $image_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    // Handle pet type selection
    if ($type_option === 'existing') {
        if (isset($_POST['existing_type_id']) && !empty($_POST['existing_type_id'])) {
            $type_id = (int)$_POST['existing_type_id'];
        } else {
            $errors[] = "Please select a pet type from the list.";
        }
    } elseif ($type_option === 'new') {
        $new_species = trim($_POST['new_species'] ?? '');
        $new_breed = trim($_POST['new_breed'] ?? '');
        $new_size = trim($_POST['new_size'] ?? '');
        $new_life_span = trim($_POST['new_life_span'] ?? '');

        if (empty($new_species)) $errors[] = "Species is required for new type.";
        if (empty($new_breed)) $errors[] = "Breed is required for new type.";
        if (empty($new_size)) $errors[] = "Size is required for new type.";
        if (empty($new_life_span)) $errors[] = "Life span is required for new type.";

        if (empty($errors)) {
            $check_stmt = $conn->prepare("SELECT type_id FROM pet_type WHERE species = ? AND breed = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("ss", $new_species, $new_breed);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $existing = $check_result->fetch_assoc();
                    $type_id = $existing['type_id'];
                } else {
                    $stmt = $conn->prepare("INSERT INTO pet_type (species, breed, size, life_span) VALUES (?,?,?,?)");
                    if ($stmt) {
                        $stmt->bind_param("ssss", $new_species, $new_breed, $new_size, $new_life_span);
                        $stmt->execute();
                        $type_id = $stmt->insert_id;
                        $stmt->close();
                    } else {
                        $errors[] = "Failed to prepare new pet type.";
                    }
                }
                $check_stmt->close();
            } else {
                $errors[] = "Failed to check existing pet types.";
            }
        }
    } else {
        $errors[] = "Please select a pet type option.";
    }

    // Validation
    if (empty($name)) $errors[] = "Pet name is required.";
    if ($age <= 0) $errors[] = "Valid age (greater than 0) is required.";
    if (!in_array($gender, ['Male', 'Female'])) $errors[] = "Valid gender required.";
    if (empty($status)) $errors[] = "Status is required.";
    if ($type_id <= 0) $errors[] = "Valid pet type is required.";
    if ($adoption_fee < 0) $errors[] = "Adoption fee cannot be negative.";

    if (empty($errors)) {
        if (!empty($image_path)) {
            $stmt = $conn->prepare("INSERT INTO pet (name, age, gender, status, type_id, adoption_fee, description, image) VALUES (?,?,?,?,?,?,?,?)");
            if ($stmt) $stmt->bind_param("sissidss", $name, $age, $gender, $status, $type_id, $adoption_fee, $description, $image_path);
        } else {
            $stmt = $conn->prepare("INSERT INTO pet (name, age, gender, status, type_id, adoption_fee, description) VALUES (?,?,?,?,?,?,?)");
            if ($stmt) $stmt->bind_param("sissids", $name, $age, $gender, $status, $type_id, $adoption_fee, $description);
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $success = "Pet added successfully!";
                $_POST = [];
                $type_option = '';
                $image_path = '';
            } else {
                $errors[] = "Database error: " . $stmt->error;
                if (!empty($image_path) && file_exists('../' . $image_path)) {
                    unlink('../' . $image_path);
                }
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to prepare insert statement.";
        }
    } else {
        if (!empty($image_path) && file_exists('../' . $image_path)) {
            unlink('../' . $image_path);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Pet</title>
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
.file-input-container { margin: 15px 0; }
.file-input-container input[type="file"] { padding: 5px; }
.file-info { font-size: 12px; color: #666; margin-top: 5px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
</style>
<script>
function toggleTypeFields() {
    const typeOption = document.querySelector('input[name="type_option"]:checked');
    if (!typeOption) return;

    const value = typeOption.value;
    const existingSection = document.getElementById('existing-type-section');
    const newSection = document.getElementById('new-type-section');

    if (value === 'existing') {
        existingSection.style.display = 'block';
        newSection.style.display = 'none';
        document.querySelector('[name="existing_type_id"]').required = true;
        document.querySelector('[name="new_species"]').required = false;
        document.querySelector('[name="new_breed"]').required = false;
        document.querySelector('[name="new_size"]').required = false;
        document.querySelector('[name="new_life_span"]').required = false;
    } else {
        existingSection.style.display = 'none';
        newSection.style.display = 'block';
        document.querySelector('[name="existing_type_id"]').required = false;
        document.querySelector('[name="new_species"]').required = true;
        document.querySelector('[name="new_breed"]').required = true;
        document.querySelector('[name="new_size"]').required = true;
        document.querySelector('[name="new_life_span"]').required = true;
    }
}

function previewImage(event) {
    const reader = new FileReader();
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');

    reader.onload = function() {
        preview.style.display = 'block';
        previewImg.src = reader.result;
    }

    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleTypeFields();
    if (!document.querySelector('input[name="type_option"]:checked')) {
        document.querySelector('input[name="type_option"][value="existing"]').checked = true;
        toggleTypeFields();
    }

    const previewImg = document.getElementById('preview-img');
    if (previewImg && previewImg.src) {
        document.getElementById('image-preview').style.display = 'block';
    }
});
</script>
</head>
<body>

<h1>Add Pet</h1>
<a href="manage_pets.php">← Back to Pets</a>

<?php 
if ($errors) {
    echo '<div class="error-container">';
    foreach($errors as $e) echo "<p class='error'>$e</p>";
    echo '</div>';
}
if ($success) echo "<p class='success'>$success</p>";
?>

<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label>Pet Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label>Age:</label>
        <input type="number" name="age" min="1" max="50" value="<?= $_POST['age'] ?? '' ?>" required>
    </div>

    <div class="form-group">
        <label>Gender:</label>
        <select name="gender" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= (($_POST['gender'] ?? '')=='Male')?'selected':'' ?>>Male</option>
            <option value="Female" <?= (($_POST['gender'] ?? '')=='Female')?'selected':'' ?>>Female</option>
        </select>
    </div>

    <div class="form-group">
        <label>Status:</label>
        <select name="status" required>
            <option value="">Select Status</option>
            <option value="Available" <?= (($_POST['status'] ?? '')=='Available')?'selected':'' ?>>Available</option>
            <option value="Adopted" <?= (($_POST['status'] ?? '')=='Adopted')?'selected':'' ?>>Adopted</option>
        </select>
    </div>

    <div class="form-group">
        <label>Description:</label>
        <textarea name="description" rows="3" placeholder="Describe the pet's personality, habits, etc."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label>Adoption Fee (NPR):</label>
        <input type="number" name="adoption_fee" min="0" step="0.01" value="<?= $_POST['adoption_fee'] ?? '0' ?>" required>
    </div>

    <div class="form-group file-input-container">
        <label>Pet Picture:</label>
        <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" onchange="previewImage(event)">
        <div class="file-info">Max file size: 5MB. Allowed types: JPG, PNG, GIF, WebP.</div>
    </div>

    <div id="image-preview" class="image-preview" style="display: <?= (!empty($image_path) || (isset($_FILES['image']['tmp_name']) && !empty($_FILES['image']['tmp_name']))) ? 'block' : 'none' ?>;">
        <img id="preview-img" src="<?= (!empty($image_path) && file_exists('../' . $image_path)) ? '../' . $image_path : '' ?>" alt="Image Preview">
    </div>

    <div class="radio-group">
        <label><strong>Pet Type:</strong></label><br><br>
        <label><input type="radio" name="type_option" value="existing" onchange="toggleTypeFields()" <?= (($_POST['type_option'] ?? 'existing')=='existing')?'checked':'' ?> required> Select Existing Type</label><br>
        <label><input type="radio" name="type_option" value="new" onchange="toggleTypeFields()" <?= (($_POST['type_option'] ?? '')=='new')?'checked':'' ?>> Add New Type</label>
    </div>

    <div id="existing-type-section" class="type-section">
        <label>Select Existing Pet Type:</label>
        <select name="existing_type_id">
            <option value="">-- Select Species/Breed --</option>
            <?php 
            if ($pet_types->num_rows > 0) {
                $pet_types->data_seek(0);
                while($type = $pet_types->fetch_assoc()): ?>
                <option value="<?= $type['type_id'] ?>" <?= (($_POST['existing_type_id'] ?? '')==$type['type_id'])?'selected':'' ?>>
                    <?= htmlspecialchars($type['species']) ?> - <?= htmlspecialchars($type['breed']) ?>
                </option>
                <?php endwhile; 
            } else {
                echo '<option value="">No pet types found. Please add a new type.</option>';
            }
            ?>
        </select>
    </div>

    <div id="new-type-section" class="type-section" style="display: none;">
        <fieldset>
            <legend>New Pet Type Details</legend>
            <label>Species:</label>
            <input type="text" name="new_species" value="<?= htmlspecialchars($_POST['new_species'] ?? '') ?>" placeholder="e.g., Dog, Cat">

            <label>Breed:</label>
            <input type="text" name="new_breed" value="<?= htmlspecialchars($_POST['new_breed'] ?? '') ?>" placeholder="e.g., Labrador, Persian, Dutch">

            <label>Size:</label>
            <select name="new_size">
                <option value="">Select Size</option>
                <option value="Small" <?= (($_POST['new_size'] ?? '')=='Small')?'selected':'' ?>>Small</option>
                <option value="Medium" <?= (($_POST['new_size'] ?? '')=='Medium')?'selected':'' ?>>Medium</option>
                <option value="Large" <?= (($_POST['new_size'] ?? '')=='Large')?'selected':'' ?>>Large</option>
            </select>

            <label>Life Span:</label>
            <input type="text" name="new_life_span" value="<?= htmlspecialchars($_POST['new_life_span'] ?? '') ?>" placeholder="e.g., 10-15 years">
        </fieldset>
    </div>

    <button type="submit">Add Pet</button>
    <button type="reset" onclick="document.getElementById('image-preview').style.display='none';" style="background:#dc3545; margin-left:10px;">Reset Form</button>
</form>

</body>
</html>
