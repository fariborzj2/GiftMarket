<?php
require_once __DIR__ . '/bootstrap.php';
redirectIfCustomer();

$error = '';
$oldEmail = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $oldEmail = $email;

    $stmt = db()->prepare("SELECT * FROM customers WHERE email = ? AND status = 1");
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if ($customer && password_verify($pass, $customer['password'])) {
        session_regenerate_id(true);
        $_SESSION['customer_id'] = (int) $customer['id'];
        redirect('index.php');
    } else {
        $error = __('acc_err_login');
    }
}

$authTitle = __('acc_login');
$authSub   = __('acc_login_sub');
require __DIR__ . '/auth_header.php';
?>
<?php if ($error): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 p-3.5 rounded-xl text-sm mb-6 flex items-center gap-2">
        <iconify-icon icon="lucide:circle-alert"></iconify-icon><?php echo e($error); ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-4">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <div>
        <label class="label"><?php echo e(__('acc_email')); ?></label>
        <input type="email" name="email" class="field" value="<?php echo e($oldEmail); ?>" dir="ltr" required autocomplete="email">
    </div>
    <div>
        <label class="label"><?php echo e(__('acc_password')); ?></label>
        <input type="password" name="password" class="field" required autocomplete="current-password">
    </div>
    <button type="submit" class="w-full bg-primary hover:bg-primary-600 text-white font-bold h-11 text-sm rounded-lg transition-all active:scale-[.98]"><?php echo e(__('acc_login')); ?></button>
</form>

<p class="text-center mt-6 text-sm text-slate-500 dark:text-slate-400">
    <?php echo e(__('acc_no_account')); ?>
    <a href="register.php" class="text-primary font-bold hover:underline"><?php echo e(__('acc_register')); ?></a>
</p>
<?php require __DIR__ . '/auth_footer.php'; ?>
