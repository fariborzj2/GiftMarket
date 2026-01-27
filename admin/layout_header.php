<?php
require_once __DIR__ . '/../system/includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/grid.css">
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            background: var(--color-body);
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
        .content-area {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 40px;
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
        .stat-card {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #eaeaff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            font-size: 24px;
        }
    </style>
</head>
<body class="dark-theme">
    <div class="admin-main">
        <div class="sidebar">
            <div class="logo">
                <img src="../assets/images/logo.svg" alt="Logo">
            </div>
            <div class="sidebar-menu">
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">Products</a>
                <a href="orders.php">Orders</a>
                <a href="customers.php">Customers</a>
                <a href="settings.php">Settings</a>
                <a href="logout.php" style="margin-top: 50px; color: #ef4444;">Logout</a>
            </div>
        </div>
        <div class="content-area">
            <header class="d-flex just-between align-center mb-40">
                <h1 class="color-title font-size-2"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                <div class="user-info d-flex align-center gap-10">
                    <span class="color-text">Hello, <b><?php echo $_SESSION['username']; ?></b></span>
                </div>
            </header>
