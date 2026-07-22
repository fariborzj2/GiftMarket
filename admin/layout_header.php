<?php
ob_start();
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

    <!-- Apply saved theme before first paint to avoid a flash -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('admin-theme');
                if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    <style>
        html { background: #f8fafc; }
        html.dark { background: #020617; }
        /* Active nav accent bar (RTL: sits on the right edge) */
        .sidebar-link.active::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 55%;
            border-radius: 9999px 0 0 9999px;
            background: #497FFF;
        }
        /* Thin, refined scrollbar for the sidebar nav */
        .nav-scroll::-webkit-scrollbar { width: 6px; }
        .nav-scroll::-webkit-scrollbar-thumb { background: rgba(100,116,139,.25); border-radius: 9999px; }
        .nav-scroll { scrollbar-width: thin; scrollbar-color: rgba(100,116,139,.25) transparent; }
        /* Uniform form controls: 40px tall, 14px font */
        main input:not([type="checkbox"]):not([type="radio"]):not([type="file"]),
        main select {
            height: 2.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        main textarea { font-size: 0.875rem; }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>assets/images/favicon-16x16.png">
    <link rel="manifest" href="<?php echo BASE_URL; ?>assets/images/site.webmanifest">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: '#497FFF',
                            600: '#3B6BEA',
                            700: '#2F58C9',
                        },
                    },
                    fontFamily: {
                        zain: ['Vazirmatn', 'sans-serif'],
                        sans: ['Vazirmatn', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply font-zain bg-slate-50 text-slate-600 dark:bg-slate-950 dark:text-slate-300 antialiased;
            }
            h1, h2, h3, h4, h5, h6 {
                @apply text-slate-900 dark:text-white font-bold;
            }
            ::selection { @apply bg-primary/20; }
        }
        @layer components {
            .admin-card {
                @apply bg-white dark:bg-slate-900 p-4 md:p-6 lg:p-8 rounded-2xl border border-slate-200/80 dark:border-slate-800;
            }
            .btn-primary {
                @apply bg-primary hover:bg-primary-600 text-white px-5 h-10 text-sm rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-medium active:scale-[.98];
            }
            .btn-secondary {
                @apply bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 px-5 h-10 text-sm rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-medium active:scale-[.98];
            }
            .btn-danger {
                @apply bg-red-500 hover:bg-red-600 text-white px-5 h-10 text-sm rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-medium active:scale-[.98];
            }
            .form-input {
                @apply w-full px-4 h-10 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all;
            }
            .icon-tile {
                @apply flex items-center justify-center rounded-xl shrink-0;
            }
            .nav-section {
                @apply px-3 mb-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/70;
            }
            .sidebar-link {
                @apply relative flex items-center gap-3 px-2.5 py-2 rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-800/70;
            }
            .sidebar-ico {
                @apply w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 transition-colors;
            }
            .sidebar-link:hover .sidebar-ico {
                @apply bg-primary/10 text-primary;
            }
            .sidebar-link.active {
                @apply bg-primary/10 text-primary font-bold hover:bg-primary/10;
            }
            .sidebar-link.active .sidebar-ico {
                @apply bg-primary text-white;
            }
            .topbar-btn {
                @apply w-10 h-10 rounded-xl flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition-colors;
            }
            .menu-item {
                @apply flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="fixed inset-0 bg-slate-900/50 z-40 hidden" id="sidebarOverlay"></div>

    <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $unreadCount = (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
        try { $openRequests = (int) db()->query("SELECT COUNT(*) FROM customer_requests WHERE status = 'open'")->fetchColumn(); }
        catch (Exception $e) { $openRequests = 0; }
        $userInitial = mb_substr($_SESSION['username'] ?? '?', 0, 1);
    ?>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 right-0 w-72 bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 p-4 z-50 flex flex-col transform translate-x-full transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0" id="sidebar">
            <!-- Brand -->
            <div class="shrink-0 flex items-center px-2 py-2 mb-4">
                <img src="../assets/images/logo.svg" alt="Logo" class="h-8 dark:invert dark:hue-rotate-180 dark:brightness-[1.5]">
            </div>

            <!-- Navigation -->
            <nav class="nav-scroll flex-1 overflow-y-auto -mr-1 pr-1 space-y-6">
                <div>
                    <div class="nav-section">اصلی</div>
                    <div class="space-y-1">
                        <a href="index.php" class="sidebar-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:chart-no-axes-combined" class="text-xl"></iconify-icon></span>
                            <span>داشبورد</span>
                        </a>
                    </div>
                </div>

                <div>
                    <div class="nav-section">مدیریت محتوا</div>
                    <div class="space-y-1">
                        <a href="brands.php" class="sidebar-link <?php echo $currentPage == 'brands.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:tag" class="text-xl"></iconify-icon></span>
                            <span>برندها</span>
                        </a>
                        <a href="countries.php" class="sidebar-link <?php echo $currentPage == 'countries.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:globe" class="text-xl"></iconify-icon></span>
                            <span>کشورها</span>
                        </a>
                        <a href="products.php" class="sidebar-link <?php echo $currentPage == 'products.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:package" class="text-xl"></iconify-icon></span>
                            <span>محصولات</span>
                        </a>
                    </div>
                </div>

                <div>
                    <div class="nav-section">ارتباطات</div>
                    <div class="space-y-1">
                        <a href="messages.php" class="sidebar-link <?php echo $currentPage == 'messages.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:mail" class="text-xl"></iconify-icon></span>
                            <span class="flex-1">پیام‌ها</span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="bg-red-500 text-white text-[10px] font-bold min-w-[20px] h-5 px-1 rounded-full flex items-center justify-center"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="customers.php" class="sidebar-link <?php echo $currentPage == 'customers.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:users" class="text-xl"></iconify-icon></span>
                            <span>مشتریان</span>
                        </a>
                        <a href="customer_requests.php" class="sidebar-link <?php echo $currentPage == 'customer_requests.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:message-square" class="text-xl"></iconify-icon></span>
                            <span class="flex-1">درخواست‌های مشتریان</span>
                            <?php if ($openRequests > 0): ?>
                                <span class="bg-amber-500 text-white text-[10px] font-bold min-w-[20px] h-5 px-1 rounded-full flex items-center justify-center"><?php echo $openRequests; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="telegram_bot.php" class="sidebar-link <?php echo $currentPage == 'telegram_bot.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:send" class="text-xl"></iconify-icon></span>
                            <span>ربات تلگرام</span>
                        </a>
                    </div>
                </div>

                <div>
                    <div class="nav-section">سیستم</div>
                    <div class="space-y-1">
                        <a href="settings.php" class="sidebar-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:settings" class="text-xl"></iconify-icon></span>
                            <span>تنظیمات</span>
                        </a>
                        <a href="email.php" class="sidebar-link <?php echo $currentPage == 'email.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:mail-check" class="text-xl"></iconify-icon></span>
                            <span>تنظیمات ایمیل</span>
                        </a>
                        <a href="account.php" class="sidebar-link <?php echo $currentPage == 'account.php' ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="lucide:lock-keyhole" class="text-xl"></iconify-icon></span>
                            <span>تغییر رمز عبور</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User card -->
            <div class="shrink-0 pt-3 mt-3 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3 p-2 rounded-xl">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0"><?php echo e($userInitial); ?></div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-900 dark:text-white text-sm truncate"><?php echo e($_SESSION['username']); ?></div>
                        <div class="text-[11px] text-slate-400">مدیر سیستم</div>
                    </div>
                    <a href="logout.php" title="خروج" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 transition-colors shrink-0">
                        <iconify-icon icon="lucide:log-out" class="text-xl"></iconify-icon>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Content Area -->
        <main class="flex-1 min-w-0 flex flex-col">
            <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 px-4 md:px-8 h-16 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button class="lg:hidden w-10 h-10 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl" id="hamburger" aria-label="منو">
                        <iconify-icon icon="lucide:menu" class="text-2xl"></iconify-icon>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-lg md:text-xl leading-tight truncate"><?php echo e($pageTitle ?? 'Dashboard'); ?></h1>
                        <div class="text-[11px] text-slate-400 hidden sm:block">پنل مدیریت <?php echo e(SITE_NAME); ?></div>
                    </div>
                </div>

                <div class="flex items-center gap-1 md:gap-1.5">
                    <a href="../" target="_blank" rel="noopener" class="topbar-btn hidden sm:flex" title="مشاهده سایت" aria-label="مشاهده سایت">
                        <iconify-icon icon="lucide:external-link" class="text-xl"></iconify-icon>
                    </a>

                    <a href="messages.php" class="topbar-btn relative" title="پیام‌ها" aria-label="پیام‌ها">
                        <iconify-icon icon="lucide:bell" class="text-xl"></iconify-icon>
                        <?php if ($unreadCount > 0): ?>
                            <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
                        <?php endif; ?>
                    </a>

                    <button type="button" id="themeToggle" class="topbar-btn" aria-label="تغییر تم روشن/تاریک">
                        <iconify-icon icon="lucide:sun" class="text-xl hidden dark:block"></iconify-icon>
                        <iconify-icon icon="lucide:moon" class="text-xl block dark:hidden"></iconify-icon>
                    </button>

                    <div class="w-px h-6 bg-slate-200 dark:bg-slate-800 mx-1 hidden md:block"></div>

                    <!-- User menu -->
                    <div class="relative" id="userMenu">
                        <button type="button" id="userMenuBtn"
                                class="flex items-center gap-2 p-1 md:pr-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0"><?php echo e($userInitial); ?></span>
                            <span class="hidden md:block text-sm font-bold text-slate-900 dark:text-white max-w-[120px] truncate"><?php echo e($_SESSION['username']); ?></span>
                            <iconify-icon icon="lucide:chevron-down" class="text-base text-slate-400 hidden md:block"></iconify-icon>
                        </button>

                        <div id="userMenuDropdown"
                             class="absolute left-0 mt-2 w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-1.5 hidden z-50">
                            <div class="px-3 py-2.5 mb-1 border-b border-slate-100 dark:border-slate-800">
                                <div class="text-sm font-bold text-slate-900 dark:text-white truncate"><?php echo e($_SESSION['username']); ?></div>
                                <div class="text-[11px] text-slate-400 mt-0.5">مدیر سیستم</div>
                            </div>
                            <a href="account.php" class="menu-item">
                                <iconify-icon icon="lucide:lock-keyhole" class="text-lg text-slate-400"></iconify-icon>
                                <span>تغییر رمز عبور</span>
                            </a>
                            <a href="../" target="_blank" rel="noopener" class="menu-item">
                                <iconify-icon icon="lucide:external-link" class="text-lg text-slate-400"></iconify-icon>
                                <span>مشاهده سایت</span>
                            </a>
                            <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                            <a href="logout.php" class="menu-item text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600">
                                <iconify-icon icon="lucide:log-out" class="text-lg"></iconify-icon>
                                <span>خروج از حساب</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-4 md:p-8">
