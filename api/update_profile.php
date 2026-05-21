<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

verify_csrf();

$user_id = $_SESSION['user_id'];
$bio = substr(trim($_POST['bio'] ?? ''), 0, 300);
$age = intval($_POST['age'] ?? 0);
$gender = $_POST['gender'] ?? 'other';
$is_private = isset($_POST['is_private']) ? 1 : 0;

try {
    $stmt = $pdo->prepare("UPDATE users SET bio = ?, age = ?, gender = ?, is_private = ? WHERE id = ?");
    $stmt->execute([$bio, $age, $gender, $is_private, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
} catch(PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>