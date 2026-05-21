<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');
$user_id = $_SESSION['user_id'];

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, full_name, age, gender, is_private FROM users WHERE id != ? AND full_name LIKE ? LIMIT 30");
$stmt->execute([$user_id, "%$query%"]);
$users = $stmt->fetchAll();

foreach ($users as &$user) {
    $user['avatar'] = getAvatar($user['gender']);
    $user['full_name'] = htmlspecialchars($user['full_name']);
    $user['friend_status'] = getFriendStatus($pdo, $user_id, $user['id']);
}

echo json_encode($users);
?>