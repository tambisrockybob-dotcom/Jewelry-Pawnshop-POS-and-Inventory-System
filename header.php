<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['username'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Megan's Jewelry POS & Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            color: #1a1a1a;
            margin: 0;
            position: relative;
            overflow-x: hidden;
        }

        /* Elegant Background Pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(212, 175, 55, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .app-shell {
            min-height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }

        /* LUXURY HEADER */
        .app-card-header {
            background: linear-gradient(135deg, rgba(15, 12, 41, 0.95) 0%, rgba(36, 36, 62, 0.95) 100%);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4), 0 1px 0 rgba(212, 175, 55, 0.1) inset;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-top {
            padding: 10px 40px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, transparent 0%, rgba(212, 175, 55, 0.05) 50%, transparent 100%);
        }

        .header-contact {
            display: flex;
            gap: 24px;
            font-size: 11px;
            color: #c0c0c0;
            letter-spacing: 0.05em;
        }

        .header-contact span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .header-contact span::before {
            content: '◆';
            color: #d4af37;
            font-size: 8px;
        }

        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .user-welcome {
            font-size: 11px;
            color: #d4af37;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .header-main {
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* LUXURY BRAND */
        .app-brand {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .app-brand-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d4af37 0%, #f4d03f 50%, #d4af37 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 0 0 3px rgba(212, 175, 55, 0.2),
                0 0 0 6px rgba(212, 175, 55, 0.1),
                0 8px 24px rgba(212, 175, 55, 0.3),
                0 0 60px rgba(212, 175, 55, 0.2) inset;
            font-size: 32px;
            position: relative;
            animation: gentle-glow 3s ease-in-out infinite;
        }

        @keyframes gentle-glow {
            0%, 100% { box-shadow: 
                0 0 0 3px rgba(212, 175, 55, 0.2),
                0 0 0 6px rgba(212, 175, 55, 0.1),
                0 8px 24px rgba(212, 175, 55, 0.3),
                0 0 60px rgba(212, 175, 55, 0.2) inset;
            }
            50% { box-shadow: 
                0 0 0 3px rgba(212, 175, 55, 0.3),
                0 0 0 6px rgba(212, 175, 55, 0.15),
                0 8px 32px rgba(212, 175, 55, 0.4),
                0 0 80px rgba(212, 175, 55, 0.3) inset;
            }
        }

        .app-brand-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .app-brand-text-title {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #d4af37 0%, #f4d03f 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.02em;
            text-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
        }

        .app-brand-text-subtitle {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #b8b8b8;
            font-weight: 500;
        }

        /* ELEGANT NAVIGATION */
        .app-nav {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .app-nav a {
            font-size: 13px;
            font-weight: 500;
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none;
            color: #e0e0e0;
            border: 1px solid transparent;
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.03em;
            position: relative;
            overflow: hidden;
        }

        .app-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.1), transparent);
            transition: left 0.5s;
        }

        .app-nav a:hover::before {
            left: 100%;
        }

        .app-nav a:hover {
            background: rgba(212, 175, 55, 0.1);
            border-color: rgba(212, 175, 55, 0.3);
            color: #f4d03f;
            transform: translateY(-1px);
        }

        .app-nav a.active {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(212, 175, 55, 0.15));
            border: 1px solid rgba(212, 175, 55, 0.4);
            color: #f4d03f;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.2);
            font-weight: 600;
        }

        .app-nav a.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #d4af37, transparent);
        }

        /* USER PROFILE */
        .app-user {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 18px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(212, 175, 55, 0.12));
            border: 1px solid rgba(212, 175, 55, 0.25);
            transition: all 0.3s ease;
        }

        .app-user:hover {
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(212, 175, 55, 0.16));
            border-color: rgba(212, 175, 55, 0.4);
            box-shadow: 0 4px 16px rgba(212, 175, 55, 0.2);
        }

        .app-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(212, 175, 55, 0.4);
        }

        .app-user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .app-user-name {
            font-size: 13px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 0.01em;
        }

        .app-user-role {
            font-size: 11px;
            color: #d4af37;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .app-user-logout {
            color: #ff6b6b;
            text-decoration: none;
            font-size: 11px;
            padding: 4px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 107, 107, 0.3);
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .app-user-logout:hover {
            background: rgba(255, 107, 107, 0.1);
            border-color: #ff6b6b;
        }

        /* MAIN CONTENT */
        .app-card-body {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 0.01em;
        }

        .page-subtitle {
            font-size: 14px;
            color: #b8b8b8;
            margin-bottom: 28px;
            letter-spacing: 0.02em;
        }

        /* LUXURY CARDS */
        .card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.95));
            border-radius: 20px;
            border: 1px solid rgba(212, 175, 55, 0.15);
            padding: 28px 32px;
            margin-bottom: 24px;
            box-shadow: 
                0 4px 24px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(212, 175, 55, 0.1) inset;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(212, 175, 55, 0.2) inset;
            transform: translateY(-2px);
        }

        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 18px;
            padding: 24px 26px;
            color: #ffffff;
            border: 1px solid rgba(212, 175, 55, 0.2);
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(212, 175, 55, 0.1) inset;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 12px 32px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(212, 175, 55, 0.3) inset;
            border-color: rgba(212, 175, 55, 0.4);
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #b8b8b8;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #d4af37;
            font-family: 'Playfair Display', serif;
        }

        /* ELEGANT TABLE */
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table thead {
            background: linear-gradient(135deg, #f8f9fa, #f0f1f3);
            border-bottom: 2px solid rgba(212, 175, 55, 0.2);
        }

        .table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 11px;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e8e8e8;
            color: #2c3e50;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.05), transparent);
        }

        /* LUXURY BUTTONS */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 24px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            background: #ffffff;
            color: #2c3e50;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.02em;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .btn:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .btn.primary {
            background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
            border: 1px solid rgba(212, 175, 55, 0.4);
            color: #1a1a1a;
            box-shadow: 
                0 4px 16px rgba(212, 175, 55, 0.3),
                0 1px 0 rgba(255, 255, 255, 0.2) inset;
            font-weight: 700;
        }

        .btn.primary:hover {
            background: linear-gradient(135deg, #f4d03f 0%, #d4af37 100%);
            box-shadow: 
                0 6px 20px rgba(212, 175, 55, 0.4),
                0 1px 0 rgba(255, 255, 255, 0.3) inset;
            transform: translateY(-2px);
        }

        /* FOOTER */
        .app-card-footer {
            padding: 20px 40px;
            border-top: 1px solid rgba(212, 175, 55, 0.2);
            background: linear-gradient(135deg, rgba(15, 12, 41, 0.95), rgba(36, 36, 62, 0.95));
            font-size: 12px;
            color: #b8b8b8;
            text-align: center;
            margin-top: 40px;
            letter-spacing: 0.03em;
        }

        .placeholder {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .header-top {
                padding: 8px 20px;
            }
            
            .header-main {
                padding: 16px 20px;
                flex-direction: column;
                gap: 16px;
            }

            .app-nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .app-card-body {
                padding: 24px 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-contact {
                display: none;
            }

            .app-brand-icon {
                width: 48px;
                height: 48px;
                font-size: 24px;
            }

            .app-brand-text-title {
                font-size: 24px;
            }

            .page-title {
                font-size: 28px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="assets/ui.css">
</head>
<body>
<div class="app-shell">
    <div class="app-card">
        <header class="app-card-header">
            <!-- Top Bar -->
            <div class="header-top">
                <div class="header-contact">
                    <span>Premium Jewelry Since 2024</span>
                    <span>Davao, Philippines</span>
                </div>
                <div class="header-actions">
                    <div class="user-welcome">Welcome, <?= htmlspecialchars($user_name) ?></div>
                </div>
            </div>

            <!-- Main Header -->
            <div class="header-main">
                <div class="app-brand">
                    <div class="app-brand-icon">💎</div>
                    <div class="app-brand-text">
                        <div class="app-brand-text-title">Megan's Jewelry</div>
                        <div class="app-brand-text-subtitle">Luxury POS & Inventory</div>
                    </div>
                </div>

                <nav class="app-nav">
                    <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Dashboard</a>
                    <a href="gold_price_calculator.php" class="<?= $current_page === 'gold_price_calculator.php' ? 'active' : '' ?>">💰 Gold Calculator</a>
                    <a href="products.php" class="<?= $current_page === 'products.php' ? 'active' : '' ?>">Products</a>
                    <a href="inventory.php" class="<?= $current_page === 'inventory.php' ? 'active' : '' ?>">Inventory</a>
                    <a href="customers.php" class="<?= $current_page === 'customers.php' ? 'active' : '' ?>">Customers</a>
                    <a href="pos.php" class="<?= $current_page === 'pos.php' ? 'active' : '' ?>">POS</a>
                    <a href="sales.php" class="<?= $current_page === 'sales.php' ? 'active' : '' ?>">Sales</a>
                </nav>

                <div class="app-user">
                    <div class="app-user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
                    <div class="app-user-info">
                        <div class="app-user-name"><?= htmlspecialchars($user_name) ?></div>
                        <div class="app-user-role">Administrator</div>
                    </div>
                    <a href="logout.php" class="app-user-logout">Logout</a>
                </div>
            </div>
        </header>

        <main class="app-card-body">