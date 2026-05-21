<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$to_user = intval($_GET['to_user'] ?? 0);
$is_typing = isset($_GET['typing']) ? intval($_GET['typing']) : 0;

if ($to_user <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO typing_status (user_id, to_user_id, is_typing, updated_at) VALUES (?, ?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE is_typing = ?, updated_at = NOW()");
    $stmt->execute([$user_id, $to_user, $is_typing, $is_typing]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Typing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>