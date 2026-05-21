<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$user_id = $_SESSION['user_id'];

// Build query - show ONLY public users
$sql = "SELECT id, full_name, age, gender, bio, is_private 
        FROM users 
        WHERE is_private = 0 
        AND id != ?";
$params = [$user_id];

if ($search !== '') {
    $sql .= " AND full_name LIKE ?";
    $params[] = "%$search%";
}

$sql .= " ORDER BY created_at DESC LIMIT 20 OFFSET ?";
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$result = [];
foreach ($users as $user) {
    $friendStatus = getFriendStatus($pdo, $user_id, $user['id']);
    
    // Truncate bio to 15 characters MAX
    $bio = $user['bio'] ?? 'No bio';
    $bio_full = $bio;
    if (strlen($bio) > 15) {
        $bio = substr($bio, 0, 12) . '...';
    }
    
    // Truncate name to 12 characters
    $full_name = $user['full_name'];
    $full_name_raw = $full_name;
    if (strlen($full_name) > 12) {
        $full_name = substr($full_name, 0, 9) . '...';
    }
    
    $result[] = [
        'id' => $user['id'],
        'full_name' => htmlspecialchars($full_name),
        'full_name_raw' => htmlspecialchars($full_name_raw),
        'age' => $user['age'],
        'gender' => $user['gender'],
        'bio' => htmlspecialchars($bio),
        'bio_full' => htmlspecialchars($bio_full),
        'avatar' => getAvatar($user['gender']),
        'friend_status' => $friendStatus
    ];
}

echo json_encode($result);
?>