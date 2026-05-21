<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get pending friend requests
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.gender, u.bio 
    FROM friends f 
    JOIN users u ON u.id = f.user_id 
    WHERE f.friend_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$friend_requests = $stmt->fetchAll();

foreach ($friend_requests as &$req) {
    $req['full_name'] = htmlspecialchars($req['full_name']);
    $req['bio'] = htmlspecialchars(substr($req['bio'] ?? '', 0, 50));
}

// Get unread messages
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        m.sender_id,
        u.full_name as sender_name,
        m.message,
        m.created_at,
        c.id as conversation_id
    FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    JOIN users u ON u.id = m.sender_id
    WHERE (c.user1_id = ? OR c.user2_id = ?) 
    AND m.sender_id != ? 
    AND m.is_read = 0
    ORDER BY m.created_at DESC
    LIMIT 20
");
$stmt->execute([$user_id, $user_id, $user_id]);
$unread_messages = $stmt->fetchAll();

foreach ($unread_messages as &$msg) {
    $msg['sender_name'] = htmlspecialchars($msg['sender_name']);
    $msg['message_preview'] = htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : '');
    $msg['time_ago'] = timeAgo($msg['created_at']);
}

echo json_encode([
    'success' => true,
    'notifications' => [
        'friend_requests' => $friend_requests,
        'unread_messages' => $unread_messages
    ],
    'counts' => [
        'friend_requests' => count($friend_requests),
        'unread_messages' => count($unread_messages)
    ]
]);
?>