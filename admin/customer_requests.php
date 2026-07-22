<?php
$pageTitle = 'درخواست‌های مشتریان';
require_once 'layout_header.php';
require_once __DIR__ . '/../system/includes/mailer.php';

$msg = '';
$isError = false;
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $id = (int) ($_POST['id'] ?? 0);

    if (($_POST['action'] ?? '') === 'delete' && $id) {
        $stmt = db()->prepare("DELETE FROM customer_requests WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: customer_requests.php?msg=' . urlencode('درخواست حذف شد.'));
        exit;
    }

    if (($_POST['action'] ?? '') === 'reply' && $id) {
        $reply = trim($_POST['reply'] ?? '');
        if ($reply === '') {
            $msg = 'متن پاسخ خالی است.'; $isError = true;
        } else {
            // Load the request + customer
            $stmt = db()->prepare("SELECT r.*, c.name AS customer_name, c.email AS customer_email, c.lang AS customer_lang
                                   FROM customer_requests r JOIN customers c ON c.id = r.customer_id WHERE r.id = ?");
            $stmt->execute([$id]);
            $req = $stmt->fetch();

            if ($req) {
                $upd = db()->prepare("UPDATE customer_requests SET admin_reply = ?, status = 'answered' WHERE id = ?");
                $upd->execute([$reply, $id]);

                $lang = ($req['customer_lang'] === 'ar') ? 'ar' : 'en';
                $heading = $lang === 'ar' ? 'لديك رد على طلبك' : 'You have a reply to your request';
                $introTxt = $lang === 'ar' ? 'مرحباً' : 'Hello';
                $yourMsgTxt = $lang === 'ar' ? 'رسالتك' : 'Your message';
                $replyTxt = $lang === 'ar' ? 'ردّنا' : 'Our reply';
                $body = '<p>' . htmlspecialchars($introTxt) . ' ' . htmlspecialchars($req['customer_name']) . ',</p>'
                    . '<div style="background:#EFF6FF;border-radius:10px;padding:14px 16px;margin:14px 0;">'
                    . '<div style="font-size:12px;color:#2563EB;font-weight:bold;margin-bottom:6px;">' . htmlspecialchars($replyTxt) . '</div>'
                    . nl2br(htmlspecialchars($reply)) . '</div>'
                    . '<div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin:14px 0;color:#64748b;font-size:13px;">'
                    . '<div style="font-size:12px;font-weight:bold;margin-bottom:6px;">' . htmlspecialchars($yourMsgTxt) . '</div>'
                    . nl2br(htmlspecialchars($req['message'])) . '</div>';
                $subject = ($lang === 'ar' ? 'رد على طلبك' : 'Reply to your request') . ' — UAE.GIFT';
                $html = buildBrandedEmail($lang, $heading, $body);

                $res = sendSystemMail($req['customer_email'], $req['customer_name'], $subject, $html);
                $note = $res['ok'] ? ' و ایمیل ارسال شد.' : (' اما ارسال ایمیل ناموفق بود: ' . $res['error']);
                $flag = $res['ok'] ? '' : '&warn=1';
                header('Location: customer_requests.php?msg=' . urlencode('پاسخ ثبت شد' . $note) . $flag);
                exit;
            } else {
                $msg = 'درخواست یافت نشد.'; $isError = true;
            }
        }
    }
}

$displayMsg = $msg ?: ($_GET['msg'] ?? '');
if (!$isError) $isError = isset($_GET['warn']);

$requests = db()->query("SELECT r.*, c.name AS customer_name, c.email AS customer_email, c.mobile AS customer_mobile
                         FROM customer_requests r JOIN customers c ON c.id = r.customer_id
                         ORDER BY (r.status = 'open') DESC, r.created_at DESC")->fetchAll();

$statusStyles = [
    'open'     => ['amber',   'در انتظار'],
    'answered' => ['green',   'پاسخ داده شده'],
    'closed'   => ['slate',   'بسته شده'],
];

if (!mailerReady()): ?>
<div class="bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30 px-5 py-3 rounded-lg text-sm mb-6 flex items-center gap-2">
    <iconify-icon icon="lucide:triangle-alert" class="text-lg"></iconify-icon>
    ارسال ایمیل هنوز فعال/تنظیم نشده — پاسخ‌ها ثبت می‌شوند ولی ایمیلی ارسال نمی‌شود. <a href="email.php" class="font-bold underline">تنظیمات ایمیل</a>
</div>
<?php endif; ?>

<?php if ($displayMsg): ?>
    <div class="<?php echo $isError ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'; ?> px-5 py-3 rounded-lg border text-sm mb-6 flex items-center gap-2">
        <iconify-icon icon="<?php echo $isError ? 'lucide:triangle-alert' : 'lucide:circle-check'; ?>" class="text-lg"></iconify-icon>
        <?php echo e($displayMsg); ?>
    </div>
<?php endif; ?>

<div class="space-y-4">
    <?php if (empty($requests)): ?>
        <div class="admin-card p-10 text-center text-slate-400">
            <iconify-icon icon="lucide:inbox" class="text-5xl mb-3 opacity-20"></iconify-icon>
            <div>هیچ درخواستی ثبت نشده است.</div>
        </div>
    <?php else: foreach ($requests as $r):
        [$color, $label] = $statusStyles[$r['status']] ?? $statusStyles['open'];
    ?>
        <div class="admin-card">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <div class="font-bold text-slate-900 dark:text-white"><?php echo e($r['subject'] ?: '(بدون موضوع)'); ?></div>
                    <div class="text-xs text-slate-400 mt-1">
                        <span class="font-medium text-slate-500 dark:text-slate-300"><?php echo e($r['customer_name']); ?></span>
                        · <span dir="ltr"><?php echo e($r['customer_email']); ?></span>
                        · <span dir="ltr"><?php echo e(date('Y-m-d H:i', strtotime($r['created_at']))); ?></span>
                    </div>
                </div>
                <span class="shrink-0 text-[11px] font-bold px-2.5 py-1 rounded-full bg-<?php echo $color; ?>-50 dark:bg-<?php echo $color; ?>-900/20 text-<?php echo $color; ?>-600 dark:text-<?php echo $color; ?>-400"><?php echo e($label); ?></span>
            </div>

            <?php if ($r['brand'] || $r['country']): ?>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <?php if ($r['brand']): ?><span class="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500"><?php echo e($r['brand']); ?></span><?php endif; ?>
                    <?php if ($r['country']): ?><span class="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500"><?php echo e($r['country']); ?></span><?php endif; ?>
                </div>
            <?php endif; ?>

            <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line bg-slate-50 dark:bg-slate-950/50 rounded-lg p-3 mb-3"><?php echo e($r['message']); ?></p>

            <?php if (!empty($r['admin_reply'])): ?>
                <div class="border-s-2 border-primary ps-3 mb-3">
                    <div class="text-[11px] font-bold text-primary mb-1">پاسخ شما</div>
                    <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line"><?php echo e($r['admin_reply']); ?></p>
                </div>
            <?php endif; ?>

            <details class="group" <?php echo empty($r['admin_reply']) ? 'open' : ''; ?>>
                <summary class="cursor-pointer text-sm font-bold text-primary inline-flex items-center gap-1.5 select-none">
                    <iconify-icon icon="lucide:reply" class="text-lg"></iconify-icon>
                    <?php echo empty($r['admin_reply']) ? 'پاسخ' : 'ویرایش پاسخ'; ?>
                </summary>
                <form method="POST" class="mt-3 space-y-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                    <textarea name="reply" rows="3" required class="w-full px-4 py-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-sm" placeholder="پاسخ شما به مشتری..."><?php echo e($r['admin_reply']); ?></textarea>
                    <button type="submit" class="btn-primary"><iconify-icon icon="lucide:send" class="text-lg"></iconify-icon>ارسال پاسخ</button>
                </form>
            </details>
            <form method="POST" class="mt-2" onsubmit="return confirm('حذف این درخواست؟')">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                <button type="submit" class="text-xs text-slate-400 hover:text-red-500 inline-flex items-center gap-1"><iconify-icon icon="lucide:trash-2"></iconify-icon>حذف</button>
            </form>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php require_once 'layout_footer.php'; ?>
