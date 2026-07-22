<?php
// Shared chrome for the public auth pages (login / register).
$lang  = getLanguage();
$isRtl = ($lang === 'ar');
$dir   = $isRtl ? 'rtl' : 'ltr';
$fontStack = $isRtl ? "'Vazirmatn', sans-serif" : "'Poppins', sans-serif";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($authTitle ?? __('acc_login')) . ' | ' . e(__('acc_area')); ?></title>
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
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: {
            colors: { primary: { DEFAULT: '#2563EB', 600: '#1D4ED8', 700: '#1E40AF' } },
        } } }
    </script>
    <style type="text/tailwindcss">
        @layer base { body { @apply bg-slate-50 text-slate-600 dark:bg-[#0b1120] dark:text-slate-300 antialiased; } }
        @layer components {
            .field { @apply w-full px-4 h-11 text-sm rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all; }
            .label { @apply block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2; }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <div class="flex items-center justify-between px-5 py-4">
        <a href="../"><img src="../assets/images/logo.svg" alt="UAE.GIFT" class="h-8 dark:invert dark:hue-rotate-180 dark:brightness-[1.5]"></a>
        <div class="flex items-center gap-2">
            <div class="flex items-center rounded-xl bg-slate-100 dark:bg-slate-800 p-0.5 text-xs font-bold">
                <a href="?lang=en" class="px-2.5 py-1.5 rounded-lg <?php echo !$isRtl ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-500'; ?>">EN</a>
                <a href="?lang=ar" class="px-2.5 py-1.5 rounded-lg <?php echo $isRtl ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white' : 'text-slate-500'; ?>">ع</a>
            </div>
            <button type="button" id="themeToggle" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" aria-label="theme">
                <iconify-icon icon="lucide:sun" class="text-xl hidden dark:block"></iconify-icon>
                <iconify-icon icon="lucide:moon" class="text-xl block dark:hidden"></iconify-icon>
            </button>
        </div>
    </div>

    <div class="flex-1 flex items-center justify-center p-5">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 md:p-10">
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-1.5"><?php echo e($authTitle ?? __('acc_login')); ?></h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($authSub ?? ''); ?></p>
                </div>
