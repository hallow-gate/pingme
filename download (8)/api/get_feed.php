<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$user_id = $_SESSION['user_id'];

// Get friends list
$stmt = $pdo->prepare("SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted' UNION SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'");
$stmt->execute([$user_id, $user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_COLUMN);
$friends[] = $user_id;

if (empty($friends)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($friends), '?'));
$sql = "
    SELECT p.*, u.full_name, u.gender,
        (SELECT COUNT(*) FROM reactions WHERE post_id = p.id) as reaction_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
        (SELECT type FROM reactions WHERE post_id = p.id AND user_id = ?) as my_reaction
    FROM posts p
    JOIN users u ON u.id = p.user_id
    WHERE p.user_id IN ($placeholders)
    ORDER BY p.created_at DESC
    LIMIT 10 OFFSET ?
";
$params = array_merge([$user_id], $friends, [$offset]);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

foreach ($posts as &$post) {
    $post['content'] = nl2br(htmlspecialchars($post['content']));
    $post['time_ago'] = timeAgo($post['created_at']);
    $post['avatar'] = getAvatar($post['gender']);
    $post['full_name'] = htmlspecialchars($post['full_name']);
    
    // Get comments
    $commStmt = $pdo->prepare("SELECT c.*, u.full_name FROM comments c JOIN users u ON u.id = c.user_id WHERE c.post_id = ? ORDER BY c.created_at DESC LIMIT 5");
    $commStmt->execute([$post['id']]);
    $post['comments'] = $commStmt->fetchAll();
    
    foreach ($post['comments'] as &$comment) {
        $comment['content'] = htmlspecialchars($comment['content']);
        $comment['full_name'] = htmlspecialchars($comment['full_name']);
    }
}

echo json_encode($posts);
?>