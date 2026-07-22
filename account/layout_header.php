<?php
require_once __DIR__ . '/bootstrap.php';
requireCustomer();

$lang  = getLanguage();
$isRtl = ($lang === 'ar');
$dir   = $isRtl ? 'rtl' : 'ltr';
$fontStack = $isRtl ? "'Vazirmatn', sans-serif" : "'Poppins', sans-serif";

$customer     = currentCustomer();
$currentPage  = basename($_SERVER['PHP_SELF']);
$userInitial  = mb_substr($customer['name'] ?: $customer['email'], 0, 1);

$watchCount   = (int) (function () use ($customer) {
    $s = db()->prepare("SELECT COUNT(*) FROM customer_watchlist WHERE customer_id = ?");
    $s->execute([$customer['id']]);
    return $s->fetchColumn();
})();
$requestCount = (int) (function () use ($customer) {
    $s = db()->prepare("SELECT COUNT(*) FROM customer_requests WHERE customer_id = ?");
    $s->execute([$customer['id']]);
    return $s->fetchColumn();
})();

// RTL/LTR-aware sidebar positioning for the mobile off-canvas
$sidePos    = $isRtl ? 'right-0' : 'left-0';
$sideHidden = $isRtl ? 'translate-x-full' : '-translate-x-full';
$sideBorder = $isRtl ? 'border-l' : 'border-r';

$nav = [
    'main' => [
        ['index.php',     'acc_nav_overview',  'lucide:layout-dashboard'],
        ['browse.php',    'acc_nav_browse',    'lucide:gift'],
        ['watchlist.php', 'acc_nav_watchlist', 'lucide:heart'],
        ['requests.php',  'acc_nav_requests',  'lucide:message-square'],
    ],
    'account' => [
        ['profile.php',   'acc_nav_profile',   'lucide:user-round'],
        ['password.php',  'acc_nav_password',  'lucide:lock-keyhole'],
    ],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($pageTitle) ? e($pageTitle) . ' | ' : '') . e(__('acc_area')); ?></title>
    <meta name="robots" content="noindex, nofollow">

    <script>
        (function () {
            try {
                var t = localStorage.getItem('account-theme');
                if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    <style>
        html { background: #f8fafc; }
        html.dark { background: #0b1120; }
        body { font-family: <?php echo $fontStack; ?>; }
        .sidebar-link.active::before {
            content: '';
            position: absolute;
            inset-inline-start: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 55%;
            border-radius: 0 9999px 9999px 0;
            background: #2563EB;
        }
        [dir="rtl"] .sidebar-link.active::before { border-radius: 9999px 0 0 9999px; }
        .nav-scroll::-webkit-scrollbar { width: 6px; }
        .nav-scroll::-webkit-scrollbar-thumb { background: rgba(100,116,139,.25); border-radius: 9999px; }
        .nav-scroll { scrollbar-width: thin; scrollbar-color: rgba(100,116,139,.25) transparent; }
        main input:not([type=checkbox]):not([type=radio]):not([type=file]),
        main select { height: 2.5rem; font-size: .875rem; line-height: 1.25rem; }
        main textarea { font-size: .875rem; }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: {
                colors: { primary: { DEFAULT: '#2563EB', 600: '#1D4ED8', 700: '#1E40AF' } },
            } }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body { @apply bg-slate-50 text-slate-600 dark:bg-[#0b1120] dark:text-slate-300 antialiased; }
            h1,h2,h3,h4,h5,h6 { @apply text-slate-900 dark:text-white font-bold; }
            ::selection { @apply bg-primary/20; }
        }
        @layer components {
            .acc-card { @apply bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800; }
            .btn-primary { @apply bg-primary hover:bg-primary-600 text-white px-5 h-10 text-sm rounded-lg transition-all duration-200 inline-flex items-center justify-center gap-2 font-medium active:scale-[.98]; }
            .btn-ghost { @apply px-5 h-10 text-sm rounded-lg border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors inline-flex items-center justify-center gap-2 font-medium; }
            .field { @apply w-full px-4 h-10 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all; }
            .label { @apply block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2; }
            .nav-section { @apply px-3 mb-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400/70; }
            .sidebar-link { @apply relative flex items-center gap-3 px-2.5 py-2 rounded-xl transition-all duration-200 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-100 dark:hover:bg-slate-800/70; }
            .sidebar-ico { @apply w-9 h-9 rounded-lg flex items-center justify-center shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 transition-colors; }
            .sidebar-link:hover .sidebar-ico { @apply bg-primary/10 text-primary; }
            .sidebar-link.active { @apply bg-primary/10 text-primary font-bold hover:bg-primary/10; }
            .sidebar-link.active .sidebar-ico { @apply bg-primary text-white; }
            .topbar-btn { @apply w-10 h-10 rounded-xl flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition-colors; }
            .menu-item { @apply flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors; }
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="fixed inset-0 bg-slate-900/50 z-40 hidden" id="sidebarOverlay"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 <?php echo $sidePos; ?> w-72 bg-white dark:bg-slate-900 <?php echo $sideBorder; ?> border-slate-200 dark:border-slate-800 p-4 z-50 flex flex-col transform <?php echo $sideHidden; ?> transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0" id="sidebar">
            <div class="shrink-0 flex items-center px-2 py-2 mb-4">
                <img src="../assets/images/logo.svg" alt="UAE.GIFT" class="h-8 dark:invert dark:hue-rotate-180 dark:brightness-[1.5]">
            </div>

            <nav class="nav-scroll flex-1 overflow-y-auto -mx-1 px-1 space-y-6">
                <?php foreach ($nav as $section => $items): ?>
                <div>
                    <div class="nav-section"><?php echo e(__('acc_section_' . $section)); ?></div>
                    <div class="space-y-1">
                        <?php foreach ($items as [$href, $key, $icon]): ?>
                        <a href="<?php echo $href; ?>" class="sidebar-link <?php echo $currentPage === $href ? 'active' : ''; ?>">
                            <span class="sidebar-ico"><iconify-icon icon="<?php echo $icon; ?>" class="text-xl"></iconify-icon></span>
                            <span class="flex-1"><?php echo e(__($key)); ?></span>
                            <?php if ($href === 'watchlist.php' && $watchCount > 0): ?>
                                <span class="text-[10px] font-bold min-w-[20px] h-5 px-1 rounded-full bg-primary/10 text-primary flex items-center justify-center"><?php echo $watchCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </nav>

            <div class="shrink-0 pt-3 mt-3 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3 p-2 rounded-xl">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0 uppercase"><?php echo e($userInitial); ?></div>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-900 dark:text-white text-sm truncate"><?php echo e($customer['name']); ?></div>
                        <div class="text-[11px] text-slate-400 truncate"><?php echo e($customer['email']); ?></div>
                    </div>
                    <a href="logout.php" title="<?php echo e(__('acc_logout')); ?>" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-500 transition-colors shrink-0">
                        <iconify-icon icon="lucide:log-out" class="text-xl"></iconify-icon>
                    </a>
                </div>
            </div>
        </aside>

        <main class="flex-1 min-w-0 flex flex-col">
            <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 px-4 md:px-8 h-16 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <button class="lg:hidden w-10 h-10 flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl" id="hamburger" aria-label="menu">
                        <iconify-icon icon="lucide:menu" class="text-2xl"></iconify-icon>
                    </button>
                    <h1 class="text-lg md:text-xl leading-tight truncate"><?php echo e($pageTitle ?? __('acc_area')); ?></h1>
                </div>

                <div class="flex items-center gap-1 md:gap-1.5">
                    <!-- Language switch (flag dropdown, like the public site) -->
                    <div class="relative" id="langMenu">
                        <button type="button" id="langMenuBtn" class="flex items-center gap-2 h-10 px-2 md:px-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <img src="../assets/images/flag/<?php echo $isRtl ? 'emirates' : 'uk'; ?>.svg" width="22" height="22" class="w-[22px] h-[22px] rounded object-cover shrink-0" alt="">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-200 hidden sm:block"><?php echo e($isRtl ? __('lang_ar') : __('lang_en')); ?></span>
                            <iconify-icon icon="lucide:chevron-down" class="text-base text-slate-400"></iconify-icon>
                        </button>
                        <div id="langMenuDropdown" class="absolute <?php echo $isRtl ? 'left-0' : 'right-0'; ?> mt-2 w-44 max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-1.5 hidden z-50">
                            <a href="?lang=en" class="menu-item <?php echo !$isRtl ? 'bg-primary/10 !text-primary font-bold' : ''; ?>">
                                <img src="../assets/images/flag/uk.svg" width="22" height="22" class="w-[22px] h-[22px] rounded object-cover shrink-0" alt="">
                                <span><?php echo e(__('lang_en')); ?></span>
                            </a>
                            <a href="?lang=ar" class="menu-item <?php echo $isRtl ? 'bg-primary/10 !text-primary font-bold' : ''; ?>">
                                <img src="../assets/images/flag/emirates.svg" width="22" height="22" class="w-[22px] h-[22px] rounded object-cover shrink-0" alt="">
                                <span><?php echo e(__('lang_ar')); ?></span>
                            </a>
                        </div>
                    </div>

                    <button type="button" id="themeToggle" class="topbar-btn" aria-label="theme">
                        <iconify-icon icon="lucide:sun" class="text-xl hidden dark:block"></iconify-icon>
                        <iconify-icon icon="lucide:moon" class="text-xl block dark:hidden"></iconify-icon>
                    </button>

                    <div class="w-px h-6 bg-slate-200 dark:bg-slate-800 mx-1 hidden md:block"></div>

                    <div class="relative" id="userMenu">
                        <button type="button" id="userMenuBtn" class="flex items-center gap-2 p-1 md:pe-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <span class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold shrink-0 uppercase"><?php echo e($userInitial); ?></span>
                            <span class="hidden md:block text-sm font-bold text-slate-900 dark:text-white max-w-[120px] truncate"><?php echo e($customer['name']); ?></span>
                            <iconify-icon icon="lucide:chevron-down" class="text-base text-slate-400 hidden md:block"></iconify-icon>
                        </button>
                        <div id="userMenuDropdown" class="absolute <?php echo $isRtl ? 'left-0' : 'right-0'; ?> mt-2 w-56 max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-1.5 hidden z-50">
                            <div class="px-3 py-2.5 mb-1 border-b border-slate-100 dark:border-slate-800">
                                <div class="text-sm font-bold text-slate-900 dark:text-white truncate"><?php echo e($customer['name']); ?></div>
                                <div class="text-[11px] text-slate-400 truncate"><?php echo e($customer['email']); ?></div>
                            </div>
                            <a href="profile.php" class="menu-item"><iconify-icon icon="lucide:user-round" class="text-lg text-slate-400"></iconify-icon><span><?php echo e(__('acc_nav_profile')); ?></span></a>
                            <a href="../" class="menu-item"><iconify-icon icon="lucide:external-link" class="text-lg text-slate-400"></iconify-icon><span><?php echo e(__('acc_back_to_site')); ?></span></a>
                            <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                            <a href="logout.php" class="menu-item text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600"><iconify-icon icon="lucide:log-out" class="text-lg"></iconify-icon><span><?php echo e(__('acc_logout')); ?></span></a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-4 md:p-8 flex-1">
