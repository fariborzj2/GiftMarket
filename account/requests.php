<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = __('acc_nav_requests');

$sent = false;
$csrfToken = generateCsrfToken();
$c = currentCustomer();
$lang = getLanguage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $subject = clean($_POST['subject'] ?? '');
    $brand   = clean($_POST['brand'] ?? '');
    $country = clean($_POST['country'] ?? '');
    $message = clean($_POST['message'] ?? '');

    if ($message !== '') {
        $stmt = db()->prepare("INSERT INTO customer_requests (customer_id, subject, brand, country, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$c['id'], $subject, $brand ?: null, $country ?: null, $message]);
        redirect('requests.php?sent=1');
    }
}
$sent = isset($_GET['sent']);

$brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
$countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();

$stmt = db()->prepare("SELECT * FROM customer_requests WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$c['id']]);
$requests = $stmt->fetchAll();

$statusStyles = [
    'open'     => ['amber',   'acc_status_open'],
    'answered' => ['emerald', 'acc_status_answered'],
    'closed'   => ['slate',   'acc_status_closed'],
];

require __DIR__ . '/layout_header.php';
?>
<div class="mb-6">
    <h2 class="text-xl mb-1"><?php echo e(__('acc_requests_title')); ?></h2>
    <p class="text-slate-500 dark:text-slate-400 text-sm"><?php echo e(__('acc_requests_sub')); ?></p>
</div>

<?php if ($sent): ?>
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-5 py-3 rounded-xl text-sm mb-6 flex items-center gap-2">
        <iconify-icon icon="lucide:circle-check" class="text-lg"></iconify-icon><?php echo e(__('acc_ok_request')); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- New request -->
    <div class="lg:col-span-2">
        <div class="acc-card p-6">
            <h3 class="text-base mb-5 flex items-center gap-2"><iconify-icon icon="lucide:message-square-plus" class="text-primary text-xl"></iconify-icon><?php echo e(__('acc_new_request')); ?></h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div>
                    <label class="label"><?php echo e(__('acc_subject')); ?> <span class="text-slate-400 font-normal">(<?php echo e(__('acc_optional')); ?>)</span></label>
                    <input type="text" name="subject" class="field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label"><?php echo e(__('brand')); ?></label>
                        <select name="brand" class="field">
                            <option value="">—</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?php echo e($b['code']); ?>"><?php echo e(__("brand_{$b['code']}", $b['name'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label"><?php echo e(__('country')); ?></label>
                        <select name="country" class="field">
                            <option value="">—</option>
                            <?php foreach ($countries as $co): ?>
                                <option value="<?php echo e($co['code']); ?>"><?php echo e(__("country_{$co['code']}", $co['name'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label"><?php echo e(__('acc_message')); ?></label>
                    <textarea name="message" rows="4" required class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"></textarea>
                </div>
                <button type="submit" class="btn-primary w-full"><iconify-icon icon="lucide:send" class="text-lg"></iconify-icon><?php echo e(__('acc_send_request')); ?></button>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="lg:col-span-3 space-y-4">
        <?php if (empty($requests)): ?>
            <div class="acc-card p-10 text-center text-slate-400">
                <iconify-icon icon="lucide:inbox" class="text-5xl mb-3 opacity-30"></iconify-icon>
                <div class="text-sm"><?php echo e(__('acc_no_requests')); ?></div>
            </div>
        <?php else: foreach ($requests as $r):
            [$color, $label] = $statusStyles[$r['status']] ?? $statusStyles['open'];
        ?>
            <div class="acc-card p-5">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 dark:text-white truncate"><?php echo e($r['subject'] ?: __('acc_message')); ?></div>
                        <div class="text-[11px] text-slate-400 mt-0.5" dir="ltr"><?php echo e(date('Y/m/d H:i', strtotime($r['created_at']))); ?></div>
                    </div>
                    <span class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full bg-<?php echo $color; ?>-50 dark:bg-<?php echo $color; ?>-900/20 text-<?php echo $color; ?>-600 dark:text-<?php echo $color; ?>-400"><?php echo e(__($label)); ?></span>
                </div>
                <?php if ($r['brand'] || $r['country']): ?>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <?php if ($r['brand']): ?><span class="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500"><?php echo e(__("brand_{$r['brand']}", $r['brand'])); ?></span><?php endif; ?>
                        <?php if ($r['country']): ?><span class="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500"><?php echo e(__("country_{$r['country']}", $r['country'])); ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line"><?php echo e($r['message']); ?></p>

                <?php if (!empty($r['admin_reply'])): ?>
                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-[11px] font-bold text-primary mb-1 flex items-center gap-1.5"><iconify-icon icon="lucide:corner-down-left"></iconify-icon><?php echo e(__('acc_reply')); ?></div>
                        <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line bg-primary/5 rounded-lg p-3"><?php echo e($r['admin_reply']); ?></p>
                    </div>
                <?php else: ?>
                    <div class="mt-3 text-[11px] text-slate-400 flex items-center gap-1.5"><iconify-icon icon="lucide:clock"></iconify-icon><?php echo e(__('acc_awaiting_reply')); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
