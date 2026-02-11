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

/* ---------------- INIT ---------------- */
$errors = [];
$success = '';
$image_path = '';

/* ---------------- FORM SUBMIT ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $adoption_fee = isset($_POST['adoption_fee']) ? (float)$_POST['adoption_fee'] : -1; // Default -1 if not set
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
    if ($adoption_fee <= 0) $errors[] = "Adoption fee must be greater than 0."; // <- Changed validation

    /* ---------------- IMAGE UPLOAD ---------------- */
    $image_name = '';
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
                $image_path = 'uploads/' . $image_name;
            } else {
                $errors[] = "Failed to upload image.";
            }
        }
    }

    /* ---------------- INSERT PET ---------------- */
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO pet (name, gender, dob, status, type_id, description, adoption_fee, image)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            "ssssisds",
            $name,
            $gender,
            $dob,
            $status,
            $type_id,
            $description,
            $adoption_fee,
            $image_path
        );
        if ($stmt->execute()) {
            $success = "Pet added successfully!";
            $_POST = [];
            $type_option = '';
            $image_path = '';
        } else {
            $errors[] = "Database error: " . $stmt->error;
            if ($image_path && file_exists('../'.$image_path)) unlink('../'.$image_path);
        }
        $stmt->close();
    } else {
        if ($image_path && file_exists('../'.$image_path)) unlink('../'.$image_path);
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
</style>

</head>
<body>

<h1>Add Pet</h1>
<a href="manage_pets.php"><- Back to Pets</a>

<?php 
if ($errors) {
    foreach($errors as $e) echo "<p class='error'>$e</p>";
}
if ($success) echo "<p class='success'>$success</p>";
?>

<form method="POST" enctype="multipart/form-data">
    <label>Pet Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

    <label>Gender:</label>
    <select name="gender" required>
        <option value="">-- Select --</option>
        <option value="Male" <?= (($_POST['gender'] ?? '')=='Male')?'selected':'' ?>>Male</option>
        <option value="Female" <?= (($_POST['gender'] ?? '')=='Female')?'selected':'' ?>>Female</option>
    </select>

    <label>Date of Birth:</label>
    <input type="date" name="dob" value="<?= $_POST['dob'] ?? '' ?>" required>

    <label>Description:</label>
    <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

    <label>Adoption Fee:</label>
    <input type="number" step="0.01" name="adoption_fee" value="<?= $_POST['adoption_fee'] ?? 0 ?>" required>

    <label>Pet Picture:</label>
    <input type="file" name="image" accept="image/*" onchange="previewImage(event)">
    <div id="image-preview" class="image-preview" style="display:none;">
        <img id="preview-img" src="" alt="Image Preview">
    </div>

    <div class="radio-group">
        <label><input type="radio" name="type_option" value="existing" onchange="toggleTypeFields()" <?= (($_POST['type_option'] ?? 'existing')=='existing')?'checked':'' ?>> Select Existing Type</label><br>
        <label><input type="radio" name="type_option" value="new" onchange="toggleTypeFields()" <?= (($_POST['type_option'] ?? '')=='new')?'checked':'' ?>> Add New Type</label>
    </div>

    <div id="existing-type-section" class="type-section">
        <label>Select Existing Pet Type:</label>
        <select name="existing_type_id">
            <option value="">-- Select Species/Breed --</option>
            <?php
            if ($pet_types->num_rows > 0) {
                $pet_types->data_seek(0);
                while($type = $pet_types->fetch_assoc()):
            ?>
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

    <div id="new-type-section" class="type-section" style="display:none;">
        <fieldset>
            <legend>New Pet Type Details</legend>
            <label>Species:</label>
            <input type="text" name="new_species" value="<?= htmlspecialchars($_POST['new_species'] ?? '') ?>" placeholder="e.g., Dog, Cat">
            <label>Breed:</label>
            <input type="text" name="new_breed" value="<?= htmlspecialchars($_POST['new_breed'] ?? '') ?>" placeholder="e.g., Labrador, Persian">
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
    <button type="button" onclick="resetForm()" style="background:#dc3545; margin-left:10px;">Reset Form</button>

<script>
function previewImage(event) {
    const previewDiv = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    const file = event.target.files[0];
    if(file) {
        previewImg.src = URL.createObjectURL(file);
        previewDiv.style.display = 'block';
    } else {
        previewImg.src = '';
        previewDiv.style.display = 'none';
    }
}

function toggleTypeFields() {
    const existingSection = document.getElementById('existing-type-section');
    const newSection = document.getElementById('new-type-section');
    const typeOption = document.querySelector('input[name="type_option"]:checked').value;
    if(typeOption === 'existing') {
        existingSection.style.display = 'block';
        newSection.style.display = 'none';
    } else {
        existingSection.style.display = 'none';
        newSection.style.display = 'block';
    }
}

// FULL RESET FUNCTION
function resetForm() {
    const form = document.querySelector('form');
    form.reset();

    // Hide image preview
    const previewDiv = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    previewImg.src = '';
    previewDiv.style.display = 'none';

    // Reset pet type sections
    document.getElementById('existing-type-section').style.display = 'block';
    document.getElementById('new-type-section').style.display = 'none';
}
</script>

</form>

</body>
</html>
