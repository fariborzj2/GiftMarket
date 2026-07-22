<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = __('acc_nav_profile');

$done = false;
$csrfToken = generateCsrfToken();
$c = currentCustomer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $name   = clean($_POST['name'] ?? '');
    $mobile = clean($_POST['mobile'] ?? '');
    if ($name !== '') {
        $stmt = db()->prepare("UPDATE customers SET name = ?, mobile = ? WHERE id = ?");
        $stmt->execute([$name, $mobile, $c['id']]);
        $done = true;
        $c['name'] = $name;
        $c['mobile'] = $mobile;
    }
}

require __DIR__ . '/layout_header.php';
?>
<div class="max-w-2xl">
    <?php if ($done): ?>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-5 py-3 rounded-xl text-sm mb-6 flex items-center gap-2">
            <iconify-icon icon="lucide:circle-check" class="text-lg"></iconify-icon><?php echo e(__('acc_ok_profile')); ?>
        </div>
    <?php endif; ?>

    <div class="acc-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center"><iconify-icon icon="lucide:user-round" class="text-2xl"></iconify-icon></div>
            <div>
                <h3 class="text-lg"><?php echo e(__('acc_profile')); ?></h3>
                <p class="text-xs text-slate-400"><?php echo e(__('acc_profile_sub')); ?></p>
            </div>
        </div>

        <form method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div>
                <label class="label"><?php echo e(__('acc_name')); ?></label>
                <input type="text" name="name" class="field" value="<?php echo e($c['name']); ?>" required>
            </div>
            <div>
                <label class="label"><?php echo e(__('acc_email')); ?></label>
                <input type="email" class="field opacity-70 cursor-not-allowed" value="<?php echo e($c['email']); ?>" dir="ltr" disabled>
            </div>
            <div>
                <label class="label"><?php echo e(__('acc_mobile')); ?></label>
                <input type="tel" name="mobile" class="field" value="<?php echo e($c['mobile']); ?>" dir="ltr">
            </div>
            <div class="pt-2">
                <button type="submit" class="btn-primary"><iconify-icon icon="lucide:save" class="text-lg"></iconify-icon><?php echo e(__('acc_save')); ?></button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
