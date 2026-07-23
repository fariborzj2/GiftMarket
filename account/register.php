<?php
require_once __DIR__ . '/bootstrap.php';
redirectIfCustomer();

$errors = [];
$old = [
    'name' => '', 'email' => '', 'mobile' => '',
    'account_type' => 'personal', 'company_name' => '', 'trade_license' => '', 'tax_number' => '',
];
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $name    = clean($_POST['name'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $mobile  = clean($_POST['mobile'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $accountType  = (($_POST['account_type'] ?? 'personal') === 'company') ? 'company' : 'personal';
    $companyName  = clean($_POST['company_name'] ?? '');
    $tradeLicense = clean($_POST['trade_license'] ?? '');
    $taxNumber    = clean($_POST['tax_number'] ?? '');

    $old = [
        'name' => $name, 'email' => $email, 'mobile' => $mobile,
        'account_type' => $accountType, 'company_name' => $companyName,
        'trade_license' => $tradeLicense, 'tax_number' => $taxNumber,
    ];

    if ($name === '' || $email === '' || $pass === '') {
        $errors[] = __('acc_err_fill');
    }
    if ($accountType === 'company' && $companyName === '') {
        $errors[] = __('acc_err_company');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('acc_err_email');
    }
    if (strlen($pass) < 8) {
        $errors[] = __('acc_err_pw_short');
    }
    if ($pass !== $confirm) {
        $errors[] = __('acc_err_pw_match');
    }

    if (empty($errors)) {
        $check = db()->prepare("SELECT id FROM customers WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = __('acc_err_email_taken');
        }
    }

    if (empty($errors)) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = db()->prepare("INSERT INTO customers (name, email, mobile, password, lang, account_type, company_name, trade_license, tax_number)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $email, $mobile, $hash, getLanguage(),
            $accountType,
            $accountType === 'company' ? $companyName : null,
            $accountType === 'company' ? ($tradeLicense ?: null) : null,
            $accountType === 'company' ? ($taxNumber ?: null) : null,
        ]);
        $_SESSION['customer_id'] = (int) db()->lastInsertId();
        redirect('index.php');
    }
}

$authTitle = __('acc_register');
$authSub   = __('acc_register_sub');
require __DIR__ . '/auth_header.php';
?>
<?php if (!empty($errors)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 p-3.5 rounded-xl text-sm mb-6">
        <?php foreach ($errors as $err): ?><div class="flex items-center gap-2"><iconify-icon icon="lucide:circle-alert"></iconify-icon><?php echo e($err); ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

    <!-- Account type -->
    <div>
        <label class="label"><?php echo e(__('acc_account_type')); ?></label>
        <div class="grid grid-cols-2 gap-3">
            <label class="cursor-pointer">
                <input type="radio" name="account_type" value="personal" class="peer sr-only" <?php echo $old['account_type'] !== 'company' ? 'checked' : ''; ?>>
                <div class="h-full p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:border-primary peer-checked:bg-primary/5 transition-colors">
                    <iconify-icon icon="lucide:user-round" class="text-xl text-primary mb-1.5"></iconify-icon>
                    <div class="text-sm font-bold text-slate-900 dark:text-white"><?php echo e(__('acc_type_personal')); ?></div>
                    <div class="text-[11px] text-slate-400 mt-0.5"><?php echo e(__('acc_type_personal_sub')); ?></div>
                </div>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="account_type" value="company" class="peer sr-only" <?php echo $old['account_type'] === 'company' ? 'checked' : ''; ?>>
                <div class="h-full p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 peer-checked:border-primary peer-checked:bg-primary/5 transition-colors">
                    <iconify-icon icon="lucide:building-2" class="text-xl text-primary mb-1.5"></iconify-icon>
                    <div class="text-sm font-bold text-slate-900 dark:text-white"><?php echo e(__('acc_type_company')); ?></div>
                    <div class="text-[11px] text-slate-400 mt-0.5"><?php echo e(__('acc_type_company_sub')); ?></div>
                </div>
            </label>
        </div>
    </div>

    <!-- Company fields (shown only for business accounts) -->
    <div id="companyFields" class="space-y-4 <?php echo $old['account_type'] === 'company' ? '' : 'hidden'; ?>">
        <div>
            <label class="label"><?php echo e(__('acc_company_name')); ?></label>
            <input type="text" name="company_name" id="companyNameInput" class="field" value="<?php echo e($old['company_name']); ?>" <?php echo $old['account_type'] === 'company' ? 'required' : ''; ?>>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="label"><?php echo e(__('acc_trade_license')); ?> <span class="text-slate-400 font-normal">(<?php echo e(__('acc_optional')); ?>)</span></label>
                <input type="text" name="trade_license" class="field" value="<?php echo e($old['trade_license']); ?>" dir="ltr">
            </div>
            <div>
                <label class="label"><?php echo e(__('acc_tax_number')); ?> <span class="text-slate-400 font-normal">(<?php echo e(__('acc_optional')); ?>)</span></label>
                <input type="text" name="tax_number" class="field" value="<?php echo e($old['tax_number']); ?>" dir="ltr">
            </div>
        </div>
    </div>

    <div>
        <label class="label" id="nameLabel" data-personal="<?php echo e(__('acc_name')); ?>" data-company="<?php echo e(__('acc_contact_name')); ?>"><?php echo e($old['account_type'] === 'company' ? __('acc_contact_name') : __('acc_name')); ?></label>
        <input type="text" name="name" class="field" value="<?php echo e($old['name']); ?>" required>
    </div>
    <div>
        <label class="label"><?php echo e(__('acc_email')); ?></label>
        <input type="email" name="email" class="field" value="<?php echo e($old['email']); ?>" dir="ltr" required>
    </div>
    <div>
        <label class="label"><?php echo e(__('acc_mobile')); ?> <span class="text-slate-400 font-normal">(<?php echo e(__('acc_optional')); ?>)</span></label>
        <input type="tel" name="mobile" class="field" value="<?php echo e($old['mobile']); ?>" dir="ltr">
    </div>
    <div>
        <label class="label"><?php echo e(__('acc_password')); ?></label>
        <input type="password" name="password" class="field" minlength="8" required autocomplete="new-password">
        <p class="text-xs text-slate-400 mt-1.5"><?php echo e(__('acc_remember_hint')); ?></p>
    </div>
    <div>
        <label class="label"><?php echo e(__('acc_confirm_password')); ?></label>
        <input type="password" name="confirm_password" class="field" minlength="8" required autocomplete="new-password">
    </div>
    <button type="submit" class="w-full bg-primary hover:bg-primary-600 text-white font-bold h-11 text-sm rounded-lg transition-all active:scale-[.98]"><?php echo e(__('acc_register')); ?></button>
</form>

<p class="text-center mt-6 text-sm text-slate-500 dark:text-slate-400">
    <?php echo e(__('acc_have_account')); ?>
    <a href="login.php" class="text-primary font-bold hover:underline"><?php echo e(__('acc_login')); ?></a>
</p>

<script>
    // Toggle company fields with the account-type selector
    (function () {
        const radios = document.querySelectorAll('input[name="account_type"]');
        const fields = document.getElementById('companyFields');
        const companyName = document.getElementById('companyNameInput');
        const nameLabel = document.getElementById('nameLabel');
        const sync = () => {
            const isCompany = document.querySelector('input[name="account_type"]:checked')?.value === 'company';
            fields.classList.toggle('hidden', !isCompany);
            companyName.required = isCompany;
            nameLabel.textContent = isCompany ? nameLabel.dataset.company : nameLabel.dataset.personal;
        };
        radios.forEach(r => r.addEventListener('change', sync));
        sync();
    })();
</script>
<?php require __DIR__ . '/auth_footer.php'; ?>
