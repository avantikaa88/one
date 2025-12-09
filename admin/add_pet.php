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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("POST Data: " . print_r($_POST, true));
    
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $gender = $_POST['gender'];
    $status = trim($_POST['status']);
    $adoption_fee = isset($_POST['adoption_fee']) ? (float)$_POST['adoption_fee'] : 0;
    $type_option = $_POST['type_option'] ?? '';
    $type_id = 0;

    error_log("Type option selected: " . $type_option);
    
    // Handle pet type selection
    if ($type_option === 'existing') {
        if (isset($_POST['existing_type_id']) && !empty($_POST['existing_type_id'])) {
            $type_id = (int)$_POST['existing_type_id'];
            error_log("Existing type ID selected: " . $type_id);
        } else {
            $errors[] = "Please select a pet type from the list.";
        }
    } elseif ($type_option === 'new') {
        // New pet type
        $new_species = trim($_POST['new_species'] ?? '');
        $new_breed = trim($_POST['new_breed'] ?? '');
        $new_size = trim($_POST['new_size'] ?? '');
        $new_life_span = trim($_POST['new_life_span'] ?? '');

        // Validate new pet type fields
        if (empty($new_species)) $errors[] = "Species is required for new type.";
        if (empty($new_breed)) $errors[] = "Breed is required for new type.";
        if (empty($new_size)) $errors[] = "Size is required for new type.";
        if (empty($new_life_span)) $errors[] = "Life span is required for new type.";

        if (empty($errors)) {
            // Check if type already exists
            $check_stmt = $conn->prepare("SELECT type_id FROM pet_type WHERE species = ? AND breed = ?");
            if (!$check_stmt) {
                $errors[] = "Prepare failed: " . $conn->error;
            } else {
                $check_stmt->bind_param("ss", $new_species, $new_breed);
                if ($check_stmt->execute()) {
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $existing = $check_result->fetch_assoc();
                        $type_id = $existing['type_id'];
                        error_log("Type already exists with ID: " . $type_id);
                    } else {
                        // Insert new type
                        $stmt = $conn->prepare("INSERT INTO pet_type (species, breed, size, life_span) VALUES (?,?,?,?)");
                        if (!$stmt) {
                            $errors[] = "Prepare failed: " . $conn->error;
                        } else {
                            $stmt->bind_param("ssss", $new_species, $new_breed, $new_size, $new_life_span);
                            if ($stmt->execute()) {
                                $type_id = $stmt->insert_id;
                                error_log("New type created with ID: " . $type_id);
                            } else {
                                $errors[] = "Failed to add new pet type: " . $stmt->error;
                            }
                            $stmt->close();
                        }
                    }
                    $check_stmt->close();
                } else {
                    $errors[] = "Execute failed: " . $check_stmt->error;
                }
            }
        }
    } else {
        $errors[] = "Please select a pet type option.";
    }

    // Validation
    if (empty($name)) $errors[] = "Pet name is required.";
    if ($age <= 0) $errors[] = "Valid age (greater than 0) is required.";
    if (!in_array($gender,['Male','Female','Other'])) $errors[] = "Valid gender required.";
    if (empty($status)) $errors[] = "Status is required.";
    if ($type_id <= 0) $errors[] = "Valid pet type is required.";
    if ($adoption_fee < 0) $errors[] = "Adoption fee cannot be negative.";

    error_log("Type ID after processing: " . $type_id);
    error_log("Errors count: " . count($errors));

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO pet (name, age, gender, status, type_id, adoption_fee) VALUES (?,?,?,?,?,?)");
        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param("sissid", $name, $age, $gender, $status, $type_id, $adoption_fee);
            if ($stmt->execute()) {
                $success = "Pet added successfully!";
                error_log("Pet added successfully with ID: " . $stmt->insert_id);
                
                // Reset form
                $_POST = [];
                $type_option = '';
            } else {
                $errors[] = "Database error: " . $stmt->error;
                error_log("Insert failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } else {
        error_log("Validation errors: " . implode(", ", $errors));
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
input, select { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc; }
button { padding:10px 20px; background:#00a8ff; color:#fff; border:none; border-radius:5px; cursor:pointer; }
button:hover { background:#0097e6; }
.error { color:red; margin:5px 0; padding:10px; background:#ffe6e6; border-radius:5px; border-left:4px solid red; }
.success { color:green; margin:5px 0; padding:10px; background:#e6ffe6; border-radius:5px; border-left:4px solid green; }
fieldset { margin-top:20px; padding:15px; border:1px solid #ddd; }
legend { font-weight:bold; padding:0 10px; }
.radio-group { margin:15px 0; padding:15px; background:#f0f8ff; border-radius:5px; }
.radio-group label { display:inline-block; margin-right:20px; }
.type-section { margin:15px 0; padding:15px; background:#f9f9f9; border-radius:5px; }
.debug { background:#f5f5f5; padding:10px; margin:10px 0; border:1px solid #ddd; font-family: monospace; }
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
        // Make existing type required, new type not required
        document.querySelector('[name="existing_type_id"]').required = true;
        document.querySelector('[name="new_species"]').required = false;
        document.querySelector('[name="new_breed"]').required = false;
        document.querySelector('[name="new_size"]').required = false;
        document.querySelector('[name="new_life_span"]').required = false;
    } else {
        existingSection.style.display = 'none';
        newSection.style.display = 'block';
        // Make new type required, existing type not required
        document.querySelector('[name="existing_type_id"]').required = false;
        document.querySelector('[name="new_species"]').required = true;
        document.querySelector('[name="new_breed"]').required = true;
        document.querySelector('[name="new_size"]').required = true;
        document.querySelector('[name="new_life_span"]').required = true;
    }
}

// Debug function to show all form values
function debugForm() {
    const formData = new FormData(document.querySelector('form'));
    console.log("Form Data:");
    for (let [key, value] of formData.entries()) {
        console.log(key + ": " + value);
    }
}
</script>
</head>
<body>

<h1>Add Pet</h1>
<a href="manage_pets.php">← Back to Pets</a>

<?php 
// Debug: Show PHP errors if any
if (ini_get('display_errors')) {
    error_reporting(E_ALL);
}

// Display errors and success messages
if ($errors) {
    echo '<div class="error-container">';
    foreach($errors as $e) {
        echo "<p class='error'>$e</p>";
    }
    echo '</div>';
}
if ($success) {
    echo "<p class='success'>$success</p>";
}

// Debug: Show POST data for troubleshooting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    echo '<div class="debug">';
    echo '<strong>Debug Information:</strong><br>';
    echo 'Type Option Selected: ' . ($_POST['type_option'] ?? 'none') . '<br>';
    echo 'Existing Type ID: ' . ($_POST['existing_type_id'] ?? 'none') . '<br>';
    echo 'New Species: ' . ($_POST['new_species'] ?? 'none') . '<br>';
    echo 'Type ID after processing: ' . ($type_id ?? 0);
    echo '</div>';
}
?>

<form method="post" onsubmit="debugForm()">
    <label>Pet Name:</label>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

    <label>Age:</label>
    <input type="number" name="age" min="1" max="50" value="<?= $_POST['age'] ?? '' ?>" required>

    <label>Gender:</label>
    <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male" <?= (($_POST['gender'] ?? '')=='Male')?'selected':'' ?>>Male</option>
        <option value="Female" <?= (($_POST['gender'] ?? '')=='Female')?'selected':'' ?>>Female</option>
        <option value="Other" <?= (($_POST['gender'] ?? '')=='Other')?'selected':'' ?>>Other</option>
    </select>

    <label>Status:</label>
    <select name="status" required>
        <option value="">Select Status</option>
        <option value="Available" <?= (($_POST['status'] ?? '')=='Available')?'selected':'' ?>>Available</option>
        <option value="Adopted" <?= (($_POST['status'] ?? '')=='Adopted')?'selected':'' ?>>Adopted</option>
        <option value="Pending" <?= (($_POST['status'] ?? '')=='Pending')?'selected':'' ?>>Pending</option>
        <option value="Reserved" <?= (($_POST['status'] ?? '')=='Reserved')?'selected':'' ?>>Reserved</option>
    </select>

    <label>Adoption Fee (NPR):</label>
    <input type="number" name="adoption_fee" min="0" step="0.01" value="<?= $_POST['adoption_fee'] ?? '0' ?>" required>

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
                // Reset pointer to beginning
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
            <input type="text" name="new_species" value="<?= htmlspecialchars($_POST['new_species'] ?? '') ?>" placeholder="e.g., Dog, Cat, Rabbit">

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
    <button type="button" onclick="debugForm()" style="background:#6c757d; margin-left:10px;">Debug Form</button>
</form>

<script>
// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleTypeFields();
    
    // If no type option is selected, select "existing" by default
    if (!document.querySelector('input[name="type_option"]:checked')) {
        document.querySelector('input[name="type_option"][value="existing"]').checked = true;
        toggleTypeFields();
    }
});
</script>

</body>
</html>