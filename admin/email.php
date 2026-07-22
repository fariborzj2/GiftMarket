<?php
$pageTitle = 'تنظیمات ایمیل';
require_once 'layout_header.php';
require_once __DIR__ . '/../system/includes/mailer.php';

$msg = '';
$isError = false;
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    if (isset($_POST['save_email'])) {
        updateSetting('smtp_enabled', isset($_POST['smtp_enabled']) ? '1' : '0');
        updateSetting('smtp_host', clean($_POST['smtp_host'] ?? ''));
        updateSetting('smtp_port', (string) (int) ($_POST['smtp_port'] ?? 465));
        updateSetting('smtp_secure', in_array($_POST['smtp_secure'] ?? 'ssl', ['ssl', 'tls', 'none']) ? $_POST['smtp_secure'] : 'ssl');
        updateSetting('smtp_user', clean($_POST['smtp_user'] ?? ''));
        // Only overwrite the password when a new value is provided.
        if (($_POST['smtp_pass'] ?? '') !== '') {
            updateSetting('smtp_pass', $_POST['smtp_pass']);
        }
        updateSetting('smtp_from_email', clean($_POST['smtp_from_email'] ?? ''));
        updateSetting('smtp_from_name', clean($_POST['smtp_from_name'] ?? 'UAE.GIFT'));
        header('Location: email.php?msg=' . urlencode('تنظیمات ایمیل ذخیره شد!'));
        exit;
    }

    if (isset($_POST['test_email'])) {
        $to = trim($_POST['test_to'] ?? '');
        $html = buildBrandedEmail('en', 'SMTP test',
            '<p>This is a test email from your UAE.GIFT admin panel. If you received it, outgoing email is configured correctly.</p>');
        $res = sendSystemMail($to, '', 'UAE.GIFT — SMTP test', $html);
        if ($res['ok']) {
            $msg = 'ایمیل آزمایشی با موفقیت به ' . $to . ' ارسال شد.';
            $isError = false;
        } else {
            $msg = 'ارسال ناموفق بود: ' . $res['error'];
            $isError = true;
        }
    }
}

$displayMsg = $msg ?: ($_GET['msg'] ?? '');
if ($displayMsg && !$isError) $isError = (strpos($displayMsg, 'ناموفق') !== false);

$smtp = [
    'enabled'    => getSetting('smtp_enabled', '0'),
    'host'       => getSetting('smtp_host', ''),
    'port'       => getSetting('smtp_port', '465'),
    'secure'     => getSetting('smtp_secure', 'ssl'),
    'user'       => getSetting('smtp_user', ''),
    'has_pass'   => getSetting('smtp_pass', '') !== '',
    'from_email' => getSetting('smtp_from_email', ''),
    'from_name'  => getSetting('smtp_from_name', 'UAE.GIFT'),
];
?>

<?php if ($displayMsg): ?>
    <div class="<?php echo $isError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'; ?> px-5 py-3 rounded-lg border text-sm mb-6 flex items-center gap-2">
        <iconify-icon icon="<?php echo $isError ? 'lucide:triangle-alert' : 'lucide:circle-check'; ?>" class="text-lg"></iconify-icon>
        <?php echo e($displayMsg); ?>
    </div>
<?php endif; ?>

<div class="max-w-2xl mx-auto space-y-6">
    <div class="admin-card">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center text-primary"><iconify-icon icon="lucide:mail-check" class="text-2xl"></iconify-icon></div>
            <div>
                <h3 class="text-lg">تنظیمات ایمیل خروجی (SMTP)</h3>
                <p class="text-xs text-slate-400 mt-0.5">برای ارسال پاسخ‌ها به مشتریان استفاده می‌شود.</p>
            </div>
        </div>

        <form method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="save_email" value="1">

            <label class="flex items-center gap-3 p-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 cursor-pointer hover:border-primary transition-colors">
                <input type="checkbox" name="smtp_enabled" value="1" <?php echo $smtp['enabled'] === '1' ? 'checked' : ''; ?> class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary">
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">فعال‌سازی ارسال ایمیل</span>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">سرور SMTP</label>
                    <input type="text" name="smtp_host" value="<?php echo e($smtp['host']); ?>" placeholder="mail.uae.gift" dir="ltr" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">پورت</label>
                    <input type="number" name="smtp_port" value="<?php echo e($smtp['port']); ?>" dir="ltr" class="form-input">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">نوع رمزنگاری</label>
                <select name="smtp_secure" class="form-input">
                    <option value="ssl" <?php echo $smtp['secure'] === 'ssl' ? 'selected' : ''; ?>>SSL (پورت 465)</option>
                    <option value="tls" <?php echo $smtp['secure'] === 'tls' ? 'selected' : ''; ?>>TLS / STARTTLS (پورت 587)</option>
                    <option value="none" <?php echo $smtp['secure'] === 'none' ? 'selected' : ''; ?>>بدون رمزنگاری</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">نام کاربری (ایمیل)</label>
                <input type="text" name="smtp_user" value="<?php echo e($smtp['user']); ?>" placeholder="info@uae.gift" dir="ltr" class="form-input">
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">رمز عبور ایمیل</label>
                <input type="password" name="smtp_pass" dir="ltr" class="form-input" placeholder="<?php echo $smtp['has_pass'] ? '•••••••• (برای تغییر وارد کنید)' : 'رمز عبور صندوق ایمیل'; ?>" autocomplete="new-password">
                <p class="text-xs text-slate-400 mt-1.5">رمز فقط در دیتابیس سرور شما ذخیره می‌شود و در گیت‌هاب قرار نمی‌گیرد.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">آدرس فرستنده (From)</label>
                    <input type="text" name="smtp_from_email" value="<?php echo e($smtp['from_email']); ?>" placeholder="info@uae.gift" dir="ltr" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">نام فرستنده</label>
                    <input type="text" name="smtp_from_name" value="<?php echo e($smtp['from_name']); ?>" dir="ltr" class="form-input">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary"><iconify-icon icon="lucide:save" class="text-lg"></iconify-icon>ذخیره تنظیمات</button>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h3 class="text-base mb-4 flex items-center gap-2"><iconify-icon icon="lucide:send" class="text-primary text-xl"></iconify-icon>ارسال ایمیل آزمایشی</h3>
        <form method="POST" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="test_email" value="1">
            <input type="email" name="test_to" value="<?php echo e($smtp['user'] ?: 'info@uae.gift'); ?>" dir="ltr" required class="form-input flex-1" placeholder="آدرس مقصد">
            <button type="submit" class="btn-primary whitespace-nowrap"><iconify-icon icon="lucide:send-horizontal" class="text-lg"></iconify-icon>ارسال آزمایشی</button>
        </form>
        <p class="text-xs text-slate-400 mt-3">قبل از آزمایش، تنظیمات را ذخیره و «فعال‌سازی» را تیک بزنید.</p>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
