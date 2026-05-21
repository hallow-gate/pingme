<?php
require_once 'includes/config.php';

echo "<h1>PingMe Debug Information</h1>";

// Test database connection
echo "<h2>Database Connection:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Database connected. Users count: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test session
echo "<h2>Session:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "User logged in: " . (isset($_SESSION['user_id']) ? "Yes (ID: " . $_SESSION['user_id'] . ")" : "No") . "<br>";

// Test CSRF
echo "<h2>CSRF Token:</h2>";
echo "Token exists: " . (isset($_SESSION['csrf_token']) ? "Yes" : "No") . "<br>";

// Test public users
echo "<h2>Public Users (for Explore):</h2>";
$stmt = $pdo->query("SELECT id, full_name, email, is_private FROM users WHERE is_private = 0 LIMIT 5");
$users = $stmt->fetchAll();
if (count($users) > 0) {
    echo "Found " . count($users) . " public users:<br>";
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Name: {$user['full_name']}<br>";
    }
} else {
    echo "⚠️ No public users found! Run: UPDATE users SET is_private = 0 WHERE id = 1<br>";
}

// Test tables
echo "<h2>Database Tables:</h2>";
$tables = ['users', 'posts', 'comments', 'reactions', 'friends', 'conversations', 'messages', 'typing_status'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "✅ $table: $count records<br>";
    } catch (Exception $e) {
        echo "❌ $table: Table missing<br>";
    }
}
?>