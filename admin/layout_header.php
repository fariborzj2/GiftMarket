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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#497FFF',
                    },
                    fontFamily: {
                        zain: ['Vazirmatn', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply font-zain bg-slate-50 text-slate-600 dark:bg-slate-950 dark:text-slate-400;
            }
            h1, h2, h3, h4, h5, h6 {
                @apply text-slate-900 dark:text-white font-bold;
            }
        }
        @layer components {
            .admin-card {
                @apply bg-white dark:bg-slate-900 p-4 md:p-6 lg:p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm;
            }
            .btn-primary {
                @apply bg-primary hover:bg-blue-600 text-white px-6 py-2 rounded-xl transition-all duration-200 flex items-center justify-center gap-2 font-medium;
            }
            .sidebar-link {
                @apply flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-400 hover:bg-primary/10 hover:text-primary;
            }
            .sidebar-link.active {
                @apply bg-primary text-white shadow-lg shadow-primary/30 hover:bg-primary hover:text-white;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="fixed inset-0 bg-slate-900/50 z-40 hidden" id="sidebarOverlay"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 right-0 w-64 bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 p-6 z-50 transform translate-x-full transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0" id="sidebar">
            <div class="mb-10">
                <img src="../assets/images/logo.svg" alt="Logo" class="h-10 dark:invert dark:hue-rotate-180 dark:brightness-[1.5]">
            </div>

            <nav class="space-y-1">
                <a href="index.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <iconify-icon icon="solar:chart-2-bold-duotone" class="text-2xl"></iconify-icon>
                    <span>داشبورد</span>
                </a>
                <a href="brands.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>">
                    <iconify-icon icon="solar:tag-bold-duotone" class="text-2xl"></iconify-icon>
                    <span>برندها</span>
                </a>
                <a href="countries.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'countries.php' ? 'active' : ''; ?>">
                    <iconify-icon icon="solar:globus-bold-duotone" class="text-2xl"></iconify-icon>
                    <span>کشورها</span>
                </a>
                <a href="products.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <iconify-icon icon="solar:box-bold-duotone" class="text-2xl"></iconify-icon>
                    <span>محصولات</span>
                </a>
                <a href="settings.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <iconify-icon icon="solar:settings-bold-duotone" class="text-2xl"></iconify-icon>
                    <span>تنظیمات</span>
                </a>
                <a href="telegram_bot.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) == 'telegram_bot.php' ? 'active' : ''; ?>">
                    <iconify-icon icon="solar:plain-2-bold-duotone" class="text-2xl"></iconify-icon>
                    <span>ربات تلگرام</span>
                </a>

                <div class="pt-10">
                    <a href="logout.php" class="sidebar-link text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600">
                        <iconify-icon icon="solar:logout-bold-duotone" class="text-2xl"></iconify-icon>
                        <span>خروج</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Content Area -->
        <main class="flex-1 min-w-0 flex flex-col">
            <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 px-4 md:px-8 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button class="lg:hidden p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg" id="hamburger">
                        <iconify-icon icon="solar:hamburger-menu-bold-duotone" class="text-2xl"></iconify-icon>
                    </button>
                    <h1 class="text-xl md:text-2xl"><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden md:block text-sm">
                        <span class="text-slate-400">سلام،</span>
                        <span class="font-bold text-slate-900 dark:text-white"><?php echo $_SESSION['username']; ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold">
                        <?php echo mb_substr($_SESSION['username'], 0, 1); ?>
                    </div>
                </div>
            </header>

            <div class="p-4 md:p-8">
