<?php
session_start();
include(__DIR__ . '/../db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Prevent admin from deleting themselves
$current_admin_id = $_SESSION['user_id'];

$user_id = (int)$_GET['id'];

// Check if trying to delete own account
if ($user_id == $current_admin_id) {
    $_SESSION['error'] = "You cannot delete your own account!";
    header("Location: manage_users.php");
    exit;
}

// Check if user exists
$user_check = $conn->query("SELECT user_id FROM users WHERE user_id = $user_id");
if ($user_check->num_rows === 0) {
    $_SESSION['error'] = "User not found!";
    header("Location: manage_users.php");
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // First, delete related records in other tables that reference this user
    // Delete from vet_appointments
    $conn->query("DELETE FROM vet_appointments WHERE user_id = $user_id");
    
    // Delete from adoption_application
    $conn->query("DELETE FROM adoption_application WHERE user_id = $user_id");
    
    // Now delete the user
    $conn->query("DELETE FROM users WHERE user_id = $user_id");
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "User and all related records deleted successfully!";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
}

header("Location: manage_users.php");
exit;
?>