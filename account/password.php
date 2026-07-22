<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = __('acc_nav_password');

$msg = '';
$isError = false;
$csrfToken = generateCsrfToken();
$c = currentCustomer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $c['password'])) {
        $msg = __('acc_err_current_pw'); $isError = true;
    } elseif (strlen($new) < 8) {
        $msg = __('acc_err_pw_short'); $isError = true;
    } elseif ($new !== $confirm) {
        $msg = __('acc_err_pw_match'); $isError = true;
    } elseif (password_verify($new, $c['password'])) {
        $msg = __('acc_err_pw_same'); $isError = true;
    } else {
        $stmt = db()->prepare("UPDATE customers SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $c['id']]);
        $msg = __('acc_ok_password'); $isError = false;
    }
}

require __DIR__ . '/layout_header.php';
?>
<div class="max-w-2xl">
    <?php if ($msg): ?>
        <div class="<?php echo $isError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-900/30'; ?> border px-5 py-3 rounded-xl text-sm mb-6 flex items-center gap-2">
            <iconify-icon icon="<?php echo $isError ? 'lucide:circle-alert' : 'lucide:circle-check'; ?>" class="text-lg"></iconify-icon><?php echo e($msg); ?>
        </div>
    <?php endif; ?>

    <div class="acc-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center"><iconify-icon icon="lucide:lock-keyhole" class="text-2xl"></iconify-icon></div>
            <div>
                <h3 class="text-lg"><?php echo e(__('acc_password_title')); ?></h3>
                <p class="text-xs text-slate-400"><?php echo e(__('acc_password_sub')); ?></p>
            </div>
        </div>

        <form method="POST" class="space-y-5" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div>
                <label class="label"><?php echo e(__('acc_current_password')); ?></label>
                <input type="password" name="current_password" class="field" required autocomplete="current-password">
            </div>
            <div class="h-px bg-slate-100 dark:bg-slate-800"></div>
            <div>
                <label class="label"><?php echo e(__('acc_new_password')); ?></label>
                <input type="password" name="new_password" class="field" minlength="8" required autocomplete="new-password">
                <p class="text-xs text-slate-400 mt-1.5"><?php echo e(__('acc_remember_hint')); ?></p>
            </div>
            <div>
                <label class="label"><?php echo e(__('acc_confirm_password')); ?></label>
                <input type="password" name="confirm_password" class="field" minlength="8" required autocomplete="new-password">
            </div>
            <div class="pt-2">
                <button type="submit" class="btn-primary"><iconify-icon icon="lucide:shield-check" class="text-lg"></iconify-icon><?php echo e(__('acc_save_password')); ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
