<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$other_id = intval($_GET['user_id'] ?? 0);
$last_id = intval($_GET['last_id'] ?? 0);

if ($other_id <= 0) {
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

// Get or create conversation
$stmt = $pdo->prepare("SELECT id FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)");
$stmt->execute([$user_id, $other_id, $other_id, $user_id]);
$conv = $stmt->fetch();

if (!$conv) {
    $stmt = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $other_id]);
    $conv_id = $pdo->lastInsertId();
} else {
    $conv_id = $conv['id'];
}

// Mark messages as read
$stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id = ? AND is_read = 0");
$stmt->execute([$conv_id, $other_id]);

// Get messages
$stmt = $pdo->prepare("SELECT m.*, u.full_name FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.conversation_id = ? AND m.id > ? ORDER BY m.created_at ASC LIMIT 50");
$stmt->execute([$conv_id, $last_id]);
$messages = $stmt->fetchAll();

foreach ($messages as &$msg) {
    $msg['message'] = html_entity_decode($msg['message'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $msg['is_mine'] = ($msg['sender_id'] == $user_id);
    $msg['time'] = date('H:i', strtotime($msg['created_at']));
}

// Check if other user is typing
$stmt = $pdo->prepare("SELECT is_typing FROM typing_status WHERE user_id = ? AND to_user_id = ? AND is_typing = 1 AND updated_at > DATE_SUB(NOW(), INTERVAL 3 SECOND)");
$stmt->execute([$other_id, $user_id]);
$is_typing = $stmt->fetch() ? true : false;

$user = getUserById($pdo, $other_id);
echo json_encode([
    'messages' => $messages,
    'conversation_id' => $conv_id,
    'other_user_online' => $user ? $user['is_online'] : false,
    'last_active' => $user ? timeAgo($user['last_active']) : 'unknown',
    'is_typing' => $is_typing
]);
?>