<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END as other_user_id,
        u.full_name, u.is_online, u.last_active, u.gender,
        c.last_message, c.last_message_time,
        (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) as unread_count
    FROM conversations c
    JOIN users u ON (u.id = c.user1_id OR u.id = c.user2_id) AND u.id != ?
    WHERE c.user1_id = ? OR c.user2_id = ?
    ORDER BY c.last_message_time DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

foreach ($conversations as &$conv) {
    $conv['avatar'] = getAvatar($conv['gender']);
    $onlineStatus = $conv['is_online'] ? '🟢 Online' : '⚫ Last seen ' . timeAgo($conv['last_active']);
    $conv['last_active_text'] = $onlineStatus;
    $conv['last_message'] = htmlspecialchars(substr($conv['last_message'] ?? '', 0, 50));
    $conv['full_name'] = htmlspecialchars($conv['full_name']);
    $conv['unread_count'] = (int)$conv['unread_count'];
}
echo json_encode($conversations);
?>