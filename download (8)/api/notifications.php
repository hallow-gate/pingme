<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set headers first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized', 'success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Calculate ETag based on current data (MUST be done before any output)
$etag = '"' . md5($user_id . floor(time() / 30)) . '"'; // Changed time()/30 to floor(time()/30)

// Check If-None-Match header FIRST (before generating data)
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    header('HTTP/1.1 304 Not Modified');
    exit; // Exit here to save bandwidth
}

// Get pending friend requests
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.gender, u.bio, u.profile_pic,
           f.created_at as request_date
    FROM friends f 
    JOIN users u ON u.id = f.user_id 
    WHERE f.friend_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
");
$stmt->execute([$user_id]);
$friend_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($friend_requests as &$req) {
    $req['full_name'] = htmlspecialchars($req['full_name']);
    $req['bio'] = htmlspecialchars(substr($req['bio'] ?? '', 0, 50));
    $req['time_ago'] = timeAgo($req['request_date']);
}

// Get unread messages
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        m.sender_id,
        u.full_name as sender_name,
        u.profile_pic,
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
$unread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($unread_messages as &$msg) {
    $msg['sender_name'] = htmlspecialchars($msg['sender_name']);
    $msg['message_preview'] = htmlspecialchars(substr($msg['message'], 0, 50)) . (strlen($msg['message']) > 50 ? '...' : '');
    $msg['time_ago'] = timeAgo($msg['created_at']);
    $msg['full_message'] = htmlspecialchars($msg['message']);
}

// Send ETag header
header('ETag: ' . $etag);

// Output JSON
echo json_encode([
    'success' => true,
    'timestamp' => time(),
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