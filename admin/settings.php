<?php
$pageTitle = 'تنظیمات سیستم';
require_once 'layout_header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usd_to_aed = clean($_POST['usd_to_aed']);
    $auto_update_rate = isset($_POST['auto_update_rate']) ? '1' : '0';
    $update_interval_hours = (int)$_POST['update_interval_hours'];

    if (is_numeric($usd_to_aed)) {
        updateSetting('usd_to_aed', $usd_to_aed);
        updateSetting('auto_update_rate', $auto_update_rate);
        updateSetting('update_interval_hours', (string)$update_interval_hours);
        $msg = 'تنظیمات با موفقیت ذخیره شد!';
    } else {
        $msg = 'خطا: مقدار وارد شده برای نرخ ارز معتبر نیست.';
    }
}

$current_rate = getSetting('usd_to_aed', '3.673');
$auto_update = getSetting('auto_update_rate', '0');
$update_interval = getSetting('update_interval_hours', '12');
$last_update = (int)getSetting('last_rate_update', 0);
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <?php if ($msg): ?>
            <div class="<?php echo strpos($msg, 'خطا') === false ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30'; ?> px-6 py-3 rounded-xl border text-sm">
                <?php echo strpos($msg, 'خطا') === false ? '✅' : '❌'; ?> <?php echo e($msg); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary text-xl">📈</div>
        <h3 class="text-xl">تنظیمات نرخ ارز</h3>
    </div>

    <form method="POST" class="space-y-8">
        <div class="space-y-4">
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">نرخ تبدیل ۱ دلار به درهم (USD to AED)</label>
            <div class="flex flex-col md:flex-row gap-3">
                <div class="relative flex-1">
                    <input type="number" step="0.0001" name="usd_to_aed" id="usd_to_aed" value="<?php echo e($current_rate); ?>" required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-lg font-bold focus:border-primary outline-none transition-all">
                </div>
                <button type="button" id="fetch-api-btn"
                        class="px-6 py-3 rounded-xl border border-primary text-primary hover:bg-primary hover:text-white transition-all font-bold whitespace-nowrap flex items-center justify-center gap-2">
                    <span>🔄</span>
                    بروزرسانی از API
                </button>
            </div>
            <p class="text-xs text-slate-400 leading-relaxed">
                این نرخ برای محاسبه قیمت نمایش داده شده در سایت استفاده می‌شود. تمام قیمت‌های محصولات در پنل بر پایه دلار وارد می‌شوند.
            </p>
        </div>

        <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

        <div class="space-y-6">
            <h3 class="text-lg flex items-center gap-2">
                <span class="text-primary">🤖</span>
                بروزرسانی خودکار
            </h3>

            <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 cursor-pointer group hover:border-primary transition-colors">
                <input type="checkbox" name="auto_update_rate" value="1" <?php echo $auto_update === '1' ? 'checked' : ''; ?>
                       class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">فعالسازی بروزرسانی خودکار قیمت درهم</span>
            </label>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">فاصله زمانی بروزرسانی (ساعت)</label>
                <div class="relative max-w-[200px]">
                    <input type="number" name="update_interval_hours" value="<?php echo e($update_interval); ?>" min="1" max="168"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:border-primary outline-none transition-all">
                </div>
                <?php if ($last_update > 0): ?>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 ms-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        آخرین بروزرسانی موفق: <?php echo date('Y-m-d H:i:s', $last_update); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="pt-6">
            <button type="submit" class="btn-primary w-full py-4 text-lg font-bold shadow-xl shadow-primary/30">ذخیره تنظیمات</button>
        </div>
    </form>
</div>

<script>
document.getElementById('fetch-api-btn').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerHTML;
    btn.innerHTML = 'در حال دریافت...';
    btn.disabled = true;

    fetch('https://open.er-api.com/v6/latest/USD')
        .then(response => response.json())
        .then(data => {
            if (data && data.rates && data.rates.AED) {
                document.getElementById('usd_to_aed').value = data.rates.AED.toFixed(4);
                alert('نرخ جدید با موفقیت دریافت شد: ' + data.rates.AED);
            } else {
                alert('خطا در دریافت اطلاعات از API');
            }
        })
        .catch(error => {
            console.error('Error fetching exchange rate:', error);
            alert('خطا در ارتباط با API');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
});
</script>

<?php require_once 'layout_footer.php'; ?>
