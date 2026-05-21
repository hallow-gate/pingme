<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PingMe - Connect Instantly</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a2b3e 0%, #0a1a2a 100%);
            min-height: 100vh;
            color: #f1f5f9;
        }
        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            text-align: center;
        }
        .logo {
            font-size: 5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }
        .tagline {
            font-size: 1.5rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 4rem;
        }
        .btn {
            padding: 0.875rem 2rem;
            border-radius: 9999px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }
        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .btn-outline {
            border: 2px solid #60a5fa;
            color: #60a5fa;
            background: transparent;
        }
        .btn-outline:hover {
            background: rgba(96, 165, 250, 0.1);
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 2rem;
            padding: 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .feature-card i {
            font-size: 2.5rem;
            color: #60a5fa;
            margin-bottom: 1rem;
        }
        .feature-card h3 {
            margin-bottom: 0.5rem;
        }
        @media (max-width: 768px) {
            .logo { font-size: 3rem; }
            .tagline { font-size: 1.125rem; }
            .hero { padding: 2rem 1rem; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="logo">⚡ PingMe</div>
        <div class="tagline">Real-time chat. Real connections.</div>
        <div class="btn-group">
            <a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Log In</a>
            <a href="signup.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Sign Up Free</a>
        </div>
        <div class="features">
            <div class="feature-card">
                <i class="fas fa-comment-dots"></i>
                <h3>Real-time Chat</h3>
                <p>Typing indicators, online status, and emoji support</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-globe"></i>
                <h3>Explore Grid</h3>
                <p>Discover new people with public profiles</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-newspaper"></i>
                <h3>Social Feed</h3>
                <p>Post, comment, and react with friends</p>
            </div>
        </div>
    </div>
</body>
</html>