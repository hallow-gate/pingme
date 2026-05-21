<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Verify CSRF
$csrf_token = isset($input['csrf_token']) ? $input['csrf_token'] : '';
if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = isset($input['post_id']) ? intval($input['post_id']) : 0;
$type = isset($input['type']) ? $input['type'] : 'like';

if ($post_id <= 0) {
    echo json_encode(['error' => 'Invalid post']);
    exit;
}

$allowed_types = ['like', 'love', 'laugh'];
if (!in_array($type, $allowed_types)) {
    $type = 'like';
}

try {
    $stmt = $pdo->prepare("INSERT INTO reactions (post_id, user_id, type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE type = ?");
    $stmt->execute([$post_id, $user_id, $type, $type]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>