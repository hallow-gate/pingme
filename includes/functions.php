<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function getAvatar($gender) {
    $avatars = [
        'male' => '👨',
        'female' => '👩',
        'other' => '🧑'
    ];
    return $avatars[$gender] ?? '👤';
}

function timeAgo($timestamp) {
    if (!$timestamp) return 'just now';
    
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M d', $time);
}

function updateLastActive($pdo, $userId) {
    $stmt = $pdo->prepare("UPDATE users SET last_active = NOW(), is_online = 1 WHERE id = ?");
    $stmt->execute([$userId]);
}

function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getFriendStatus($pdo, $userId, $friendId) {
    $stmt = $pdo->prepare("SELECT status FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?)");
    $stmt->execute([$userId, $friendId, $friendId, $userId]);
    $result = $stmt->fetch();
    return $result ? $result['status'] : 'none';
}
?>