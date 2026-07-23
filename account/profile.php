<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = __('acc_nav_profile');

$done = false;
$csrfToken = generateCsrfToken();
$c = currentCustomer();

$isCompany = (($c['account_type'] ?? 'personal') === 'company');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $name   = clean($_POST['name'] ?? '');
    $mobile = clean($_POST['mobile'] ?? '');

    if ($name !== '') {
        if ($isCompany) {
            $companyName  = clean($_POST['company_name'] ?? '');
            $tradeLicense = clean($_POST['trade_license'] ?? '');
            $taxNumber    = clean($_POST['tax_number'] ?? '');
            if ($companyName !== '') {
                $stmt = db()->prepare("UPDATE customers SET name = ?, mobile = ?, company_name = ?, trade_license = ?, tax_number = ? WHERE id = ?");
                $stmt->execute([$name, $mobile, $companyName, $tradeLicense ?: null, $taxNumber ?: null, $c['id']]);
                $done = true;
                $c['name'] = $name; $c['mobile'] = $mobile;
                $c['company_name'] = $companyName; $c['trade_license'] = $tradeLicense; $c['tax_number'] = $taxNumber;
            }
        } else {
            $stmt = db()->prepare("UPDATE customers SET name = ?, mobile = ? WHERE id = ?");
            $stmt->execute([$name, $mobile, $c['id']]);
            $done = true;
            $c['name'] = $name;
            $c['mobile'] = $mobile;
        }
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
            <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center"><iconify-icon icon="<?php echo $isCompany ? 'lucide:building-2' : 'lucide:user-round'; ?>" class="text-2xl"></iconify-icon></div>
            <div class="flex-1 min-w-0">
                <h3 class="text-lg"><?php echo e(__('acc_profile')); ?></h3>
                <p class="text-xs text-slate-400"><?php echo e(__('acc_profile_sub')); ?></p>
            </div>
            <span class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full <?php echo $isCompany ? 'bg-primary/10 text-primary' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'; ?>">
                <?php echo e($isCompany ? __('acc_type_company') : __('acc_type_personal')); ?>
            </span>
        </div>

        <form method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <?php if ($isCompany): ?>
            <div class="space-y-5 p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40">
                <div class="text-sm font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                    <iconify-icon icon="lucide:building-2" class="text-primary text-lg"></iconify-icon>
                    <?php echo e(__('acc_company_details')); ?>
                </div>
                <div>
                    <label class="label"><?php echo e(__('acc_company_name')); ?></label>
                    <input type="text" name="company_name" class="field" value="<?php echo e($c['company_name']); ?>" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label"><?php echo e(__('acc_trade_license')); ?> <span class="text-slate-400 font-normal">(<?php echo e(__('acc_optional')); ?>)</span></label>
                        <input type="text" name="trade_license" class="field" value="<?php echo e($c['trade_license']); ?>" dir="ltr">
                    </div>
                    <div>
                        <label class="label"><?php echo e(__('acc_tax_number')); ?> <span class="text-slate-400 font-normal">(<?php echo e(__('acc_optional')); ?>)</span></label>
                        <input type="text" name="tax_number" class="field" value="<?php echo e($c['tax_number']); ?>" dir="ltr">
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div>
                <label class="label"><?php echo e($isCompany ? __('acc_contact_name') : __('acc_name')); ?></label>
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
