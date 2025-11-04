<?php
require_once 'database.php';
requireLogin();

$database = new Database();
$conn = $database->connect();

$faculty_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($faculty_id <= 0) {
    $_SESSION['error'] = "Invalid faculty ID.";
    header("Location: view_faculty.php");
    exit();
}

// Permission check: Only admins can delete
if (!canDeleteFaculty($faculty_id)) {
    $_SESSION['error'] = "You don't have permission to delete faculty members.";
    header("Location: view_faculty.php");
    exit();
}

try {
    // Get faculty details for cleanup
    $stmt = $conn->prepare("SELECT photo, user_id FROM faculty WHERE faculty_id = ?");
    $stmt->execute([$faculty_id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$faculty) {
        $_SESSION['error'] = "Faculty member not found.";
        header("Location: view_faculty.php");
        exit();
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // Delete related records first (to avoid foreign key constraints)
    // Delete education records
    $stmt = $conn->prepare("DELETE FROM education WHERE faculty_id = ?");
    $stmt->execute([$faculty_id]);
    
    // Delete research records
    $stmt = $conn->prepare("DELETE FROM research WHERE faculty_id = ?");
    $stmt->execute([$faculty_id]);
    
    // Delete the faculty record
    $stmt = $conn->prepare("DELETE FROM faculty WHERE faculty_id = ?");
    $stmt->execute([$faculty_id]);
    
    if ($faculty['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$faculty['user_id']]);
    }
    
    // Delete photo file if exists
    if (!empty($faculty['photo']) && file_exists($faculty['photo'])) {
        unlink($faculty['photo']);
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Faculty member deleted successfully.";
    header("Location: view_faculty.php");
    exit();
    
} catch (PDOException $e) {
    // Rollback on error
    $conn->rollBack();
    error_log("Error deleting faculty: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting faculty member: " . $e->getMessage();
    header("Location: view_faculty.php");
    exit();
}
?>
