<?php
require_once __DIR__ . '/../system/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($pageTitle) ? $pageTitle . ' | ' : '') . SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/grid.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            background: var(--color-body);
            font-family: 'Vazirmatn', sans-serif;
        }
        input, button, textarea, select {
            font-family: 'Vazirmatn', sans-serif !important;
        }
        .admin-main {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--color-surface);
            border-right: 1px solid var(--color-border);
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
        }
        [dir="rtl"] .sidebar {
            left: auto;
            right: 0;
            border-right: none;
            border-left: 1px solid var(--color-border);
        }
        .content-area {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 40px;
        }
        [dir="rtl"] .content-area {
            margin-left: 0;
            margin-right: var(--sidebar-width);
        }
        .sidebar-menu {
            margin-top: 40px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--color-text);
            border-radius: 10px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: var(--color-primary);
            color: #fff;
        }
        .admin-card {
            background: var(--color-surface);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid var(--color-border);
            margin-bottom: 30px;
        }
        .hamburger {
            display: none;
            cursor: pointer;
            font-size: 24px;
            color: var(--color-title);
        }
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 260px;
            }
            .sidebar {
                transform: translateX(-100%);
                transition: all 0.3s ease;
                z-index: 1001;
            }
            [dir="rtl"] .sidebar {
                transform: translateX(100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content-area {
                margin-left: 0;
                padding: 20px;
            }
            [dir="rtl"] .content-area {
                margin-right: 0;
            }
            .hamburger {
                display: block;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }
        .stat-card {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: var(--color-body);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            font-size: 24px;
            border: 1px solid var(--color-border);
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-main">
        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="../assets/images/logo.svg" alt="Logo">
            </div>
            <div class="sidebar-menu">
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">داشبورد</a>
                <a href="brands.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>">برندها</a>
                <a href="countries.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'countries.php' ? 'active' : ''; ?>">کشورها</a>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">محصولات</a>
                <!-- Future modules:
                <a href="orders.php">سفارشات</a>
                <a href="customers.php">مشتریان</a>
                <a href="settings.php">تنظیمات</a>
                -->
                <a href="logout.php" style="margin-top: 50px; color: #ef4444;">خروج</a>
            </div>
        </div>
        <div class="content-area">
            <header class="d-flex just-between align-center mb-40">
                <div class="d-flex align-center gap-15">
                    <div class="hamburger" id="hamburger">☰</div>
                    <h1 class="color-title font-size-2"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>
                <div class="user-info d-flex align-center gap-10">
                    <span class="color-text">سلام، <b><?php echo $_SESSION['username']; ?></b></span>
                </div>
            </header>
