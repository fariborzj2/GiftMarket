<?php
require_once __DIR__ . '/bootstrap.php';
redirectIfCustomer();

$errors = [];
$old = ['name' => '', 'email' => '', 'mobile' => ''];
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
    $old = ['name' => $name, 'email' => $email, 'mobile' => $mobile];

    if ($name === '' || $email === '' || $pass === '') {
        $errors[] = __('acc_err_fill');
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
        $stmt = db()->prepare("INSERT INTO customers (name, email, mobile, password, lang) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $mobile, $hash, getLanguage()]);
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
    <div>
        <label class="label"><?php echo e(__('acc_name')); ?></label>
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
<?php require __DIR__ . '/auth_footer.php'; ?>
