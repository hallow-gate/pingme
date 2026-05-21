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
$other_id = intval($_POST['user_id'] ?? 0);

try {
    // Get conversation
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    $conv = $stmt->fetch();
    
    if ($conv) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id = ? AND is_read = 0");
        $stmt->execute([$conv['id'], $other_id]);
    }
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>