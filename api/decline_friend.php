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
$friend_id = intval($_POST['friend_id'] ?? 0);

try {
    $stmt = $pdo->prepare("DELETE FROM friends WHERE user_id = ? AND friend_id = ? AND status = 'pending'");
    $stmt->execute([$friend_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Friend request declined']);
    } else {
        echo json_encode(['error' => 'No pending request found']);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>