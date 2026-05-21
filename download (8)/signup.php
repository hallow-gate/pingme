<?php 
require_once 'includes/config.php'; 
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: home.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (empty($full_name)) {
        $error = 'Full name is required';
    } elseif (signup($email, $password, $full_name)) {
        login($email, $password);
        header('Location: set_profile.php');
        exit;
    } else {
        $error = 'Email already registered';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PingMe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a2b3e 0%, #0a1a2a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #1e293b;
            border-radius: 2rem;
            padding: 2rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #f1f5f9;
        }
        input {
            width: 100%;
            padding: 0.875rem;
            margin-bottom: 1rem;
            border: none;
            border-radius: 9999px;
            background: #0f172a;
            color: #f1f5f9;
            font-size: 1rem;
        }
        input::placeholder {
            color: #64748b;
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
            transition: background 0.2s;
        }
        button:hover {
            background: #2563eb;
        }
        .error {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 0.75rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        .link {
            text-align: center;
            margin-top: 1rem;
        }
        .link a {
            color: #60a5fa;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>✍️ Create Account</h2>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password (min 6 characters)" required>
            <button type="submit">Sign Up</button>
        </form>
        <div class="link">
            <a href="login.php">Already have an account? Log in</a>
        </div>
    </div>
</body>
</html>