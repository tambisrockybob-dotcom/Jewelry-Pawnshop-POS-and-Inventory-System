<?php
require 'db.php';
require 'auth.php';

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lockedUntil = $_SESSION['login_locked_until'] ?? 0;

    if ($lockedUntil > time()) {
        $mins = (int)ceil(($lockedUntil - time()) / 60);
        $msg = "Too many failed attempts. Try again in {$mins} minute(s).";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);          // prevent session fixation
            unset($user['password']);
            $_SESSION['user']          = $user;
            $_SESSION['username']      = $user['full_name'] ?: $user['username'];  // used by header.php
            $_SESSION['last_activity'] = time();
            unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
            header('Location: index.php');
            exit;
        }

        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + ACCOUNT_LOCK_DURATION;
            $_SESSION['login_attempts'] = 0;
        }
        $msg = "Invalid credentials.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Megan's Jewelry</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        overflow: hidden;
    }
    
    /* Elegant animated background */
    body::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: 
            radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.08) 0%, transparent 50%);
        animation: rotate 30s linear infinite;
    }
    
    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Floating particles */
    body::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            radial-gradient(2px 2px at 20% 30%, rgba(212, 175, 55, 0.3), transparent),
            radial-gradient(2px 2px at 60% 70%, rgba(212, 175, 55, 0.3), transparent),
            radial-gradient(1px 1px at 50% 50%, rgba(212, 175, 55, 0.3), transparent),
            radial-gradient(1px 1px at 80% 10%, rgba(212, 175, 55, 0.3), transparent);
        background-size: 200% 200%;
        animation: sparkle 4s linear infinite;
        pointer-events: none;
    }

    @keyframes sparkle {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 0.8; }
    }
    
    .login-container {
        width: 100%;
        max-width: 480px;
        position: relative;
        z-index: 1;
        animation: slideUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(40px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .login-card {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.95));
        border-radius: 28px;
        box-shadow: 
            0 20px 60px rgba(0, 0, 0, 0.4),
            0 0 0 1px rgba(212, 175, 55, 0.2) inset;
        overflow: hidden;
        backdrop-filter: blur(20px);
        border: 1px solid rgba(212, 175, 55, 0.2);
    }
    
    .login-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        padding: 50px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .login-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
        animation: headerGlow 8s linear infinite;
    }

    @keyframes headerGlow {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .login-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, #d4af37, transparent);
        box-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
    }
    
    .login-logo {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #d4af37 0%, #f4d03f 50%, #d4af37 100%);
        border-radius: 50%;
        margin: 0 auto 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 45px;
        box-shadow: 
            0 0 0 4px rgba(212, 175, 55, 0.2),
            0 0 0 8px rgba(212, 175, 55, 0.1),
            0 12px 32px rgba(212, 175, 55, 0.4),
            0 0 80px rgba(212, 175, 55, 0.3) inset;
        animation: logoPulse 3s ease-in-out infinite;
        position: relative;
        z-index: 1;
    }
    
    @keyframes logoPulse {
        0%, 100% { 
            transform: scale(1);
            box-shadow: 
                0 0 0 4px rgba(212, 175, 55, 0.2),
                0 0 0 8px rgba(212, 175, 55, 0.1),
                0 12px 32px rgba(212, 175, 55, 0.4),
                0 0 80px rgba(212, 175, 55, 0.3) inset;
        }
        50% { 
            transform: scale(1.05);
            box-shadow: 
                0 0 0 4px rgba(212, 175, 55, 0.3),
                0 0 0 8px rgba(212, 175, 55, 0.15),
                0 12px 40px rgba(212, 175, 55, 0.5),
                0 0 100px rgba(212, 175, 55, 0.4) inset;
        }
    }
    
    .login-title {
        color: #ffffff;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.02em;
        font-family: 'Playfair Display', serif;
        position: relative;
        z-index: 1;
    }
    
    .login-subtitle {
        color: #d4af37;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        position: relative;
        z-index: 1;
    }
    
    .login-body {
        padding: 48px 40px;
    }
    
    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 28px;
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        color: #991b1b;
        border-left: 4px solid #dc2626;
        font-size: 0.875rem;
        font-weight: 500;
        animation: shake 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-8px); }
        75% { transform: translateX(8px); }
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #1a1a2e;
        font-size: 0.875rem;
        letter-spacing: 0.02em;
    }
    
    .form-control {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f9fafb;
        color: #1a1a2e;
        font-weight: 500;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #d4af37;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.1);
        transform: translateY(-1px);
    }
    
    .input-icon {
        position: relative;
    }
    
    .input-icon::before {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.25rem;
        z-index: 1;
        opacity: 0.6;
    }
    
    .input-icon.username::before {
        content: '👤';
    }
    
    .input-icon.password::before {
        content: '🔒';
    }
    
    .input-icon .form-control {
        padding-left: 56px;
    }
    
    .btn-login {
        width: 100%;
        padding: 18px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
        color: #1a1a2e;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 
            0 8px 24px rgba(212, 175, 55, 0.4),
            0 1px 0 rgba(255, 255, 255, 0.2) inset;
        margin-top: 12px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        position: relative;
        overflow: hidden;
    }

    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .btn-login:hover::before {
        left: 100%;
    }
    
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 
            0 12px 32px rgba(212, 175, 55, 0.5),
            0 1px 0 rgba(255, 255, 255, 0.3) inset;
    }
    
    .btn-login:active {
        transform: translateY(0);
    }
    
    .login-footer {
        padding: 24px 40px;
        background: linear-gradient(135deg, #f8f9fa, #f0f1f3);
        text-align: center;
        font-size: 0.813rem;
        color: #6b7280;
        border-top: 1px solid #e5e7eb;
    }
    
    .login-footer p {
        margin-bottom: 8px;
    }

    .login-footer a {
        color: #d4af37;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .login-footer a:hover {
        color: #b8941f;
        text-decoration: underline;
    }

    .remember-me {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 24px 0;
        font-size: 0.875rem;
        color: #4b5563;
        font-weight: 500;
    }
    
    .remember-me input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #d4af37;
    }

    .decorative-line {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 24px 0;
        color: #9ca3af;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .decorative-line::before,
    .decorative-line::after {
        content: '';
        flex: 1;
        height: 1px;
        background: linear-gradient(90deg, transparent, #d4af37, transparent);
    }

    .decorative-line span {
        padding: 0 16px;
    }
    
    @media (max-width: 480px) {
        .login-card {
            border-radius: 20px;
        }
        
        .login-header {
            padding: 40px 24px;
        }
        
        .login-body {
            padding: 32px 24px;
        }
        
        .login-title {
            font-size: 1.75rem;
        }

        .login-logo {
            width: 75px;
            height: 75px;
            font-size: 38px;
        }
    }
  </style>
</head>
<body>
<div class="login-container">
  <div class="login-card">
    <div class="login-header">
      <div class="login-logo">💎</div>
      <h1 class="login-title">Megan's Jewelry</h1>
      <p class="login-subtitle">Luxury POS & Inventory</p>
    </div>
    
    <div class="login-body">
      <?php if($msg): ?>
        <div class="alert">⚠️ <?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <div class="decorative-line">
        <span>◆ Sign In ◆</span>
      </div>
      
      <form method="post">
        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-icon username">
            <input name="username" type="text" class="form-control" placeholder="Enter your username" required autofocus>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-icon password">
            <input name="password" type="password" class="form-control" placeholder="Enter your password" required>
          </div>
        </div>
        
        <div class="remember-me">
          <input type="checkbox" id="remember">
          <label for="remember">Remember me for 30 days</label>
        </div>
        
        <button type="submit" class="btn-login">Access System</button>
      </form>
    </div>
    
    <div class="login-footer">
      <p>© <?= date('Y') ?> Megan's Jewelry. All rights reserved.</p>
      <p style="margin-top: 8px; font-size: 0.75rem; color: #9ca3af;">
        Need help? Contact <a href="mailto:support@megans-jewelry.online">support@megans-jewelry.online</a>
      </p>
    </div>
  </div>
</div>
</body>
</html>