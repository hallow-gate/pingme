<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
session_start();

// Manually set a user ID for testing (change 1 to your actual user ID)
$_SESSION['user_id'] = 1;

header('Content-Type: text/html');

echo "<h1>Feed API Test</h1>";

// Test the feed query directly
$user_id = $_SESSION['user_id'];
echo "<p>Testing for user ID: $user_id</p>";

// Get friends
$friendsStmt = $pdo->prepare("SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted' 
                              UNION SELECT user_id FROM friends WHERE friend_id = ? AND status = 'accepted'");
$friendsStmt->execute([$user_id, $user_id]);
$friends = $friendsStmt->fetchAll(PDO::FETCH_COLUMN);

echo "<p>Friends: " . print_r($friends, true) . "</p>";

$user_ids = $friends;
$user_ids[] = $user_id;
$user_ids = array_unique($user_ids);

echo "<p>User IDs to show: " . print_r($user_ids, true) . "</p>";

if(empty($user_ids)) {
    echo "<p style='color:red'>ERROR: No user IDs found!</p>";
} else {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $sql = "
        SELECT p.*, u.full_name, u.gender,
            (SELECT COUNT(*) FROM reactions WHERE post_id = p.id) as reaction_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.user_id IN ($placeholders)
        ORDER BY p.created_at DESC
    ";
    
    echo "<p>SQL: $sql</p>";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($user_ids);
    $posts = $stmt->fetchAll();
    
    echo "<h2>Posts found: " . count($posts) . "</h2>";
    
    if(count($posts) > 0) {
        echo "<ul>";
        foreach($posts as $post) {
            echo "<li><strong>{$post['full_name']}</strong>: " . htmlspecialchars($post['content']) . " (Created: {$post['created_at']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>No posts found in database!</p>";
        
        // Check if posts table has data
        $countStmt = $pdo->query("SELECT COUNT(*) FROM posts");
        $totalPosts = $countStmt->fetchColumn();
        echo "<p>Total posts in database: $totalPosts</p>";
        
        if($totalPosts > 0) {
            // Show all posts regardless of user
            $allStmt = $pdo->query("SELECT p.*, u.full_name FROM posts p JOIN users u ON u.id = p.user_id");
            $allPosts = $allStmt->fetchAll();
            echo "<h3>All posts in database:</h3>";
            foreach($allPosts as $post) {
                echo "<p><strong>{$post['full_name']}</strong> (User ID: {$post['user_id']}): " . htmlspecialchars($post['content']) . "</p>";
            }
        }
    }
}

// Check if current user has posts
$userPosts = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$userPosts->execute([$user_id]);
echo "<p>Current user has " . $userPosts->fetchColumn() . " posts</p>";
?>