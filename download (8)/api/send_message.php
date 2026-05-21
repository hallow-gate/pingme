<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Verify CSRF
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$other_id = isset($_POST['to_user_id']) ? intval($_POST['to_user_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($message)) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

if ($other_id <= 0) {
    echo json_encode(['error' => 'Invalid recipient']);
    exit;
}

// Rate limiting
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 SECOND)");
$stmt->execute([$user_id]);
if ($stmt->fetchColumn() > 3) {
    echo json_encode(['error' => 'Too many messages. Please slow down.']);
    exit;
}

try {
    // Get or create conversation
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$user_id, $other_id, $other_id, $user_id]);
    $conv = $stmt->fetch();

    if (!$conv) {
        $stmt = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id, last_message_time) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $other_id]);
        $conv_id = $pdo->lastInsertId();
    } else {
        $conv_id = $conv['id'];
    }

    // Store message as is (with special characters and emojis)
    // MySQL with utf8mb4 supports emojis natively
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$conv_id, $user_id, $message]);

    // Update conversation
    $stmt = $pdo->prepare("UPDATE conversations SET last_message = ?, last_message_time = NOW() WHERE id = ?");
    $stmt->execute([$message, $conv_id]);

    echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
} catch(PDOException $e) {
    error_log("Send message error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>