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
    SELECT u.id, u.full_name, u.gender, u.bio
    FROM friends f 
    JOIN users u ON u.id = f.user_id 
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

foreach ($requests as &$req) {
    $req['avatar'] = getAvatar($req['gender']);
    $req['full_name'] = htmlspecialchars($req['full_name']);
    $req['bio'] = htmlspecialchars(substr($req['bio'] ?? '', 0, 100));
}

echo json_encode($requests);
?>