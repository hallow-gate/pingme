<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = intval($_GET['user_id'] ?? 0);
$current_user_id = $_SESSION['user_id'];

if ($user_id <= 0) {
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT id, full_name, email, bio, age, gender, is_private, is_online, last_active, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Check if private profile
if ($user['is_private'] == 1 && $user_id != $current_user_id) {
    $isFriend = isFriend($pdo, $current_user_id, $user_id);
    if (!$isFriend) {
        echo json_encode(['error' => 'This profile is private']);
        exit;
    }
}

// Get friend status
$friendStatus = getFriendStatus($pdo, $current_user_id, $user_id);

// Get mutual friends count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as mutual_count 
    FROM friends f1 
    JOIN friends f2 ON f1.friend_id = f2.friend_id 
    WHERE f1.user_id = ? AND f1.status = 'accepted' 
    AND f2.user_id = ? AND f2.status = 'accepted'
");
$stmt->execute([$current_user_id, $user_id]);
$mutual = $stmt->fetch();

// Get posts count
$stmt = $pdo->prepare("SELECT COUNT(*) as post_count FROM posts WHERE user_id = ?");
$stmt->execute([$user_id]);
$postCount = $stmt->fetch();

// Get friends count
$stmt = $pdo->prepare("SELECT COUNT(*) as friend_count FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
$stmt->execute([$user_id, $user_id]);
$friendCount = $stmt->fetch();

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'full_name' => htmlspecialchars($user['full_name']),
        'bio' => nl2br(htmlspecialchars($user['bio'] ?? 'No bio yet')),
        'age' => $user['age'] ?? 'Not specified',
        'gender' => $user['gender'],
        'avatar' => getAvatar($user['gender']),
        'is_online' => $user['is_online'],
        'last_active' => timeAgo($user['last_active']),
        'member_since' => date('F Y', strtotime($user['created_at'])),
        'post_count' => $postCount['post_count'],
        'friend_count' => $friendCount['friend_count'],
        'mutual_friends' => $mutual['mutual_count']
    ],
    'friend_status' => $friendStatus,
    'is_own_profile' => ($user_id == $current_user_id)
]);
?>