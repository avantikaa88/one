<?php
session_start();
include(__DIR__ . '/../db.php');

// Redirect if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login/login.php");
    exit;
}

// Check if ID and action are provided
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: admin_dashboard.php?message=Invalid request&type=error");
    exit;
}

$adoption_id = intval($_GET['id']);
$action = $_GET['action'];

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    header("Location: admin_dashboard.php?message=Invalid action&type=error");
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // First, get the pet_id from the adoption application
    $stmt = $conn->prepare("SELECT pet_id, user_id FROM adoption_application WHERE adoption_id = ?");
    $stmt->bind_param("i", $adoption_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Adoption application not found");
    }
    
    $app_data = $result->fetch_assoc();
    $pet_id = $app_data['pet_id'];
    $user_id = $app_data['user_id'];
    $stmt->close();
    
    // Update adoption application status
    $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
    $stmt = $conn->prepare("UPDATE adoption_application SET status = ? WHERE adoption_id = ?");
    $stmt->bind_param("si", $new_status, $adoption_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update adoption application");
    }
    $stmt->close();
    
    // Update pet status based on action
    if ($action === 'approve') {
        // If approved, mark pet as Adopted and update owner
        $stmt = $conn->prepare("UPDATE pet SET status = 'Adopted' WHERE pet_id = ?");
        $stmt->bind_param("i", $pet_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update pet status");
        }
        $stmt->close();
        
        // Reject all other pending applications for the same pet
        $stmt = $conn->prepare("
            UPDATE adoption_application 
            SET status = 'Rejected' 
            WHERE pet_id = ? 
            AND status = 'Pending' 
            AND adoption_id != ?
        ");
        $stmt->bind_param("ii", $pet_id, $adoption_id);
        $stmt->execute();
        $stmt->close();
        
        // Create notification for user (optional - if you have a notifications table)
        $message = "Your adoption application has been approved! The pet is now yours.";
        
    } else {
        // If rejected, check if there are no other approved applications for this pet
        $stmt = $conn->prepare("
            SELECT COUNT(*) as approved_count 
            FROM adoption_application 
            WHERE pet_id = ? AND status = 'Approved'
        ");
        $stmt->bind_param("i", $pet_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $approved_count = $result->fetch_assoc()['approved_count'];
        $stmt->close();
        
        // If no approved applications exist, set pet back to Available
        if ($approved_count == 0) {
            $stmt = $conn->prepare("UPDATE pet SET status = 'Available' WHERE pet_id = ?");
            $stmt->bind_param("i", $pet_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update pet status");
            }
            $stmt->close();
        }
        
        // Create notification for user (optional)
        $message = "Your adoption application has been rejected.";
    }
    
    // Commit transaction
    $conn->commit();
    
    // Redirect with success message
    $success_message = ($action === 'approve') 
        ? "Adoption application approved successfully!" 
        : "Adoption application rejected successfully!";
    
    header("Location: admin_dashboard.php?message=" . urlencode($success_message) . "&type=success");
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    header("Location: admin_dashboard.php?message=" . urlencode($e->getMessage()) . "&type=error");
    exit;
}
?>