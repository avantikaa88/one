<?php
session_start();
include(__DIR__ . '/../db.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $pet_id = intval($_POST['pet_id']);
    $reason = trim($_POST['reason']);

    // Handle uploaded legal ID image
    if (isset($_FILES['legal_id_image']) && $_FILES['legal_id_image']['error'] === 0) {
        $legal_id_image = file_get_contents($_FILES['legal_id_image']['tmp_name']);
    } else {
        die("Legal ID image is required.");
    }

    // Get adoption fee from pet table
    $fee_stmt = $conn->prepare("SELECT adoption_fee FROM pet WHERE pet_id = ?");
    $fee_stmt->bind_param("i", $pet_id);
    $fee_stmt->execute();
    $fee_result = $fee_stmt->get_result();
    $pet_data = $fee_result->fetch_assoc();
    $adoption_fee = $pet_data['adoption_fee'] ?? 0;
    $fee_stmt->close();

    // Insert adoption application
    $stmt = $conn->prepare("
        INSERT INTO adoption_application 
        (user_id, pet_id, adoption_date, status, payment_status, payment_amount, adoption_fee, reason, legal_id_image)
        VALUES (?, ?, CURRENT_DATE, 'Pending', 'Pending', 0, ?, ?, ?)
    ");

    if (!$stmt) die("Prepare failed: " . $conn->error);

    // Bind parameters: i = int, d = double, s = string, b = blob
    $stmt->bind_param("iidsb", $user_id, $pet_id, $adoption_fee, $reason, $legal_id_image);
    $stmt->send_long_data(4, $legal_id_image); // Send blob data

    if ($stmt->execute()) {
        // Update pet status to 'Pending'
        $update_pet = $conn->prepare("UPDATE pet SET status = 'Pending' WHERE pet_id = ?");
        $update_pet->bind_param("i", $pet_id);
        $update_pet->execute();
        $update_pet->close();

        header("Location: ../user/User_dashboard.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    header("Location: pet.php");
    exit;
}
?>
