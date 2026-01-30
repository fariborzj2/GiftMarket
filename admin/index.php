<?php
$pageTitle = 'داشبورد';
require_once 'layout_header.php';

// Fetch some stats
$totalProducts = db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalBrands = db()->query("SELECT COUNT(*) FROM brands")->fetchColumn();
$totalCountries = db()->query("SELECT COUNT(*) FROM countries")->fetchColumn();
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="admin-card !p-6 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 text-2xl">
            📦
        </div>
        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400">کل محصولات</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $totalProducts; ?></div>
        </div>
    </div>

    <div class="admin-card !p-6 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-600 dark:text-purple-400 text-2xl">
            🏷️
        </div>
        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400">برندهای فعال</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $totalBrands; ?></div>
        </div>
    </div>

    <div class="admin-card !p-6 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400 text-2xl">
            🌍
        </div>
        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400">کشورها</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $totalCountries; ?></div>
        </div>
    </div>

    <div class="admin-card !p-6 flex items-center gap-5 opacity-60">
        <div class="w-14 h-14 rounded-2xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center text-green-600 dark:text-green-400 text-2xl">
            🛒
        </div>
        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400">سفارشات</div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white">0</div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="flex items-center gap-4 mb-6">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xl">
            👋
        </div>
        <div>
            <h3 class="text-xl">خوش آمدید، <?php echo $_SESSION['username']; ?></h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm">به پنل مدیریت UAE.GIFT خوش آمدید. تمامی ابزارها برای مدیریت سایت در دسترس شماست.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-8">
        <a href="products.php?action=add" class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-primary dark:hover:border-primary transition-colors group">
            <div class="text-primary mb-2 group-hover:scale-110 transition-transform">➕</div>
            <div class="font-bold text-slate-900 dark:text-white">افزودن محصول</div>
            <div class="text-xs text-slate-500 mt-1">ایجاد محصول جدید با قیمت‌های دیجیتال و فیزیکی</div>
        </a>

        <a href="settings.php" class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-primary dark:hover:border-primary transition-colors group">
            <div class="text-primary mb-2 group-hover:scale-110 transition-transform">📈</div>
            <div class="font-bold text-slate-900 dark:text-white">نرخ ارز</div>
            <div class="text-xs text-slate-500 mt-1">بروزرسانی نرخ تبدیل USD به AED</div>
        </a>

        <a href="telegram_bot.php" class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-primary dark:hover:border-primary transition-colors group">
            <div class="text-primary mb-2 group-hover:scale-110 transition-transform">🤖</div>
            <div class="font-bold text-slate-900 dark:text-white">ربات تلگرام</div>
            <div class="text-xs text-slate-500 mt-1">مدیریت انتشار خودکار قیمت‌ها در کانال‌ها</div>
        </a>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
