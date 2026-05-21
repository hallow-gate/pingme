<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'is_typing' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$from_user = intval($_GET['from_user'] ?? 0);

if ($from_user <= 0) {
    echo json_encode(['success' => false, 'is_typing' => false]);
    exit;
}

try {
    // Check if the other user is typing to current user
    $stmt = $pdo->prepare("SELECT is_typing, updated_at FROM typing_status WHERE user_id = ? AND to_user_id = ? AND is_typing = 1 AND updated_at > DATE_SUB(NOW(), INTERVAL 3 SECOND)");
    $stmt->execute([$from_user, $user_id]);
    $result = $stmt->fetch();
    
    $is_typing = $result ? true : false;
    
    echo json_encode([
        'success' => true,
        'is_typing' => $is_typing,
        'user_id' => $from_user
    ]);
} catch (PDOException $e) {
    error_log("Check typing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'is_typing' => false]);
}
?>