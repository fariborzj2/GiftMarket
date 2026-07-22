<?php
$pageTitle = 'تغییر رمز عبور';
require_once 'layout_header.php';

$msg = '';
$isError = false;
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Load the currently logged-in user
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        $msg = 'خطا: کاربر یافت نشد.';
        $isError = true;
    } elseif (!password_verify($current, $user['password'])) {
        $msg = 'خطا: رمز عبور فعلی اشتباه است.';
        $isError = true;
    } elseif (strlen($new) < 8) {
        $msg = 'خطا: رمز عبور جدید باید حداقل ۸ کاراکتر باشد.';
        $isError = true;
    } elseif ($new !== $confirm) {
        $msg = 'خطا: رمز عبور جدید و تکرار آن یکسان نیستند.';
        $isError = true;
    } elseif (password_verify($new, $user['password'])) {
        $msg = 'خطا: رمز عبور جدید نباید با رمز فعلی یکسان باشد.';
        $isError = true;
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
        $msg = 'رمز عبور با موفقیت تغییر کرد!';
        $isError = false;
    }
}
?>

<div class="mb-8">
    <?php if ($msg): ?>
        <div class="<?php echo $isError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'; ?> px-6 py-3 rounded-xl border text-sm flex items-center gap-2">
            <iconify-icon icon="<?php echo $isError ? 'lucide:triangle-alert' : 'lucide:circle-check'; ?>" class="text-xl"></iconify-icon>
            <?php echo e($msg); ?>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
            <iconify-icon icon="lucide:lock-keyhole" class="text-2xl"></iconify-icon>
        </div>
        <div>
            <h3 class="text-xl">تغییر رمز عبور</h3>
            <p class="text-xs text-slate-400 mt-1">حساب: <?php echo e($_SESSION['username']); ?></p>
        </div>
    </div>

    <form method="POST" class="space-y-6" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <div class="space-y-2">
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">رمز عبور فعلی</label>
            <input type="password" name="current_password" required autocomplete="current-password"
                   class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                   placeholder="••••••••">
        </div>

        <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

        <div class="space-y-2">
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">رمز عبور جدید</label>
            <input type="password" name="new_password" required minlength="8" autocomplete="new-password"
                   class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                   placeholder="حداقل ۸ کاراکتر">
            <p class="text-xs text-slate-400">برای امنیت بیشتر، از ترکیب حروف بزرگ و کوچک، اعداد و نمادها استفاده کنید.</p>
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">تکرار رمز عبور جدید</label>
            <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password"
                   class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                   placeholder="••••••••">
        </div>

        <div class="pt-2">
            <button type="submit" class="btn-primary w-full py-3">
                <iconify-icon icon="lucide:shield-check" class="text-xl"></iconify-icon>
                ذخیره رمز جدید
            </button>
        </div>
    </form>
</div>

<?php require_once 'layout_footer.php'; ?>
