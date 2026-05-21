<?php 
require_once 'includes/config.php'; 
require_once 'includes/functions.php'; 
require_once 'includes/csrf.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $bio = substr(trim($_POST['bio']), 0, 300);
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE users SET bio = ?, age = ?, gender = ?, is_private = ? WHERE id = ?");
    if ($stmt->execute([$bio, $age, $gender, $is_private, $user_id])) {
        $success = 'Profile updated successfully!';
    }
}

$user = getUserById($pdo, $user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Profile - PingMe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a2b3e 0%, #0a1a2a 100%);
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #1e293b;
            border-radius: 2rem;
            padding: 2rem;
        }
        h2 {
            color: #f1f5f9;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        label {
            color: #cbd5e1;
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.875rem;
            margin-bottom: 1rem;
            border: none;
            border-radius: 1rem;
            background: #0f172a;
            color: #f1f5f9;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .checkbox-label input {
            width: auto;
            margin: 0;
        }
        button {
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 9999px;
            background: #3b82f6;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        .success {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            padding: 0.75rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .skip {
            text-align: center;
            margin-top: 1rem;
        }
        .skip a {
            color: #60a5fa;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>✨ Complete Your Profile</h2>
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <label>Bio</label>
            <textarea name="bio" placeholder="Tell us about yourself..."><?= escape($user['bio']) ?></textarea>
            
            <label>Age</label>
            <input type="number" name="age" value="<?= $user['age'] ?>" placeholder="Your age">
            
            <label>Gender</label>
            <select name="gender">
                <option value="male" <?= $user['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $user['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= $user['gender'] == 'other' ? 'selected' : '' ?>>Other</option>
            </select>
            
            <label class="checkbox-label">
                <input type="checkbox" name="is_private" <?= $user['is_private'] ? 'checked' : '' ?>>
                Make my account private (hidden from explore)
            </label>
            
            <button type="submit">Save & Continue →</button>
        </form>
        <div class="skip">
            <a href="home.php">Skip for now</a>
        </div>
    </div>
</body>
</html>