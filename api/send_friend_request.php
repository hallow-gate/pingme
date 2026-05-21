<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Verify CSRF
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;

if ($user_id == $friend_id) {
    echo json_encode(['error' => 'Cannot add yourself']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$friend_id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status, created_at) VALUES (?, ?, 'pending', NOW()) ON DUPLICATE KEY UPDATE status = 'pending'");
    $stmt->execute([$user_id, $friend_id]);
    echo json_encode(['success' => true, 'message' => 'Friend request sent!']);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Request already sent']);
}
?>