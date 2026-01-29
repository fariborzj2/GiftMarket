<?php
$pageTitle = 'تنظیمات سیستم';
require_once 'layout_header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usd_to_aed = clean($_POST['usd_to_aed']);
    if (is_numeric($usd_to_aed)) {
        updateSetting('usd_to_aed', $usd_to_aed);
        $msg = 'تنظیمات با موفقیت ذخیره شد!';
    } else {
        $msg = 'خطا: مقدار وارد شده برای نرخ ارز معتبر نیست.';
    }
}

$current_rate = getSetting('usd_to_aed', '3.673');
?>

<div class="d-flex just-between align-center mb-30">
    <div>
        <?php if ($msg): ?>
            <div style="background: <?php echo strpos($msg, 'خطا') === false ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo strpos($msg, 'خطا') === false ? '#166534' : '#991b1b'; ?>; padding: 10px 20px; border-radius: 10px; margin-bottom: 20px;">
                <?php echo e($msg); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card max-w600">
    <h3 class="color-title mb-30">تنظیمات نرخ ارز</h3>
    <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
        <div class="input-item mb-20">
            <div class="input-label">نرخ تبدیل ۱ دلار به درهم (USD to AED)</div>
            <div class="input d-flex align-center gap-10">
                <input type="number" step="0.0001" name="usd_to_aed" id="usd_to_aed" value="<?php echo e($current_rate); ?>" required style="flex: 1;">
                <button type="button" class="btn-sm" id="fetch-api-btn" style="height: 48px; width: auto; white-space: nowrap; border-color: var(--color-primary); color: var(--color-primary);">دریافت از API 🔄</button>
            </div>
            <div class="font-size-0-8 color-bright mt-10">
                این نرخ برای محاسبه قیمت نمایش داده شده در سایت استفاده می‌شود. تمام قیمت‌های محصولات در پنل بر پایه دلار وارد می‌شوند.
            </div>
        </div>

        <div class="d-flex gap-10">
            <button type="submit" class="btn-primary radius-100">ذخیره تنظیمات</button>
        </div>
    </form>
</div>

<script>
document.getElementById('fetch-api-btn').addEventListener('click', function() {
    const btn = this;
    const originalText = btn.innerText;
    btn.innerText = 'در حال دریافت...';
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
            btn.innerText = originalText;
            btn.disabled = false;
        });
});
</script>

<?php require_once 'layout_footer.php'; ?>
