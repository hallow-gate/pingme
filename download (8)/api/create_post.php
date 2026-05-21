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
    echo json_encode(['error' => 'Invalid JSON input. Please refresh the page.']);
    exit;
}

// Debug logging
error_log("=== CREATE POST DEBUG ===");
error_log("Input data: " . print_r($input, true));
error_log("Session CSRF: " . ($_SESSION['csrf_token'] ?? 'not set'));

// Verify CSRF token
$csrf_token = isset($input['csrf_token']) ? $input['csrf_token'] : '';
if (empty($csrf_token)) {
    echo json_encode(['error' => 'CSRF token missing. Please refresh the page.']);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    echo json_encode(['error' => 'Session expired. Please login again.']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['error' => 'Invalid CSRF token. Please refresh the page.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$content = isset($input['content']) ? trim($input['content']) : '';

if (empty($content)) {
    echo json_encode(['error' => 'Post cannot be empty']);
    exit;
}

// Rate limiting
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)");
$stmt->execute([$user_id]);
if ($stmt->fetchColumn() > 1) {
    echo json_encode(['error' => 'Please wait a few seconds before posting again']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $content]);
    
    echo json_encode([
        'success' => true,
        'post_id' => $pdo->lastInsertId(),
        'message' => 'Post created successfully!'
    ]);
} catch(PDOException $e) {
    error_log("Post error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>