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
$content = isset($input['content']) ? trim($input['content']) : '';

if ($post_id <= 0 || empty($content)) {
    echo json_encode(['error' => 'Invalid comment data']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$post_id, $user_id, $content]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>