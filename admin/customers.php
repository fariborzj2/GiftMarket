<?php
$pageTitle = 'مدیریت مشتریان';
require_once 'layout_header.php';

$msg = '';
$csrfToken = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_status' && $id) {
        $stmt = db()->prepare("UPDATE customers SET status = 1 - status WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'وضعیت مشتری تغییر کرد.';
    } elseif ($action === 'delete' && $id) {
        $stmt = db()->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'مشتری حذف شد.';
    }
    header('Location: customers.php?msg=' . urlencode($msg));
    exit;
}

$search = clean($_GET['search'] ?? '');
$like = '%' . $search . '%';

$sql = "SELECT c.*,
            (SELECT COUNT(*) FROM customer_watchlist w WHERE w.customer_id = c.id) AS watch_count,
            (SELECT COUNT(*) FROM customer_requests r WHERE r.customer_id = c.id) AS req_count
        FROM customers c
        WHERE (? = '' OR c.name LIKE ? OR c.email LIKE ? OR c.mobile LIKE ?)
        ORDER BY c.created_at DESC";
$stmt = db()->prepare($sql);
$stmt->execute([$search, $like, $like, $like]);
$customers = $stmt->fetchAll();

$totalCustomers = (int) db()->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$displayMsg = $msg ?: ($_GET['msg'] ?? '');
?>

<?php if ($displayMsg): ?>
    <div class="bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/30 px-5 py-3 rounded-lg text-sm mb-6 flex items-center gap-2">
        <iconify-icon icon="lucide:circle-check" class="text-lg"></iconify-icon><?php echo e($displayMsg); ?>
    </div>
<?php endif; ?>

<div class="admin-card !p-0 overflow-hidden">
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/50">
        <h3 class="text-lg flex items-center gap-2 m-0">
            <iconify-icon icon="lucide:users" class="text-primary text-2xl"></iconify-icon>
            <span>مشتریان</span>
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400"><?php echo $totalCustomers; ?></span>
        </h3>
        <form method="GET" class="relative w-full md:w-72">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="جستجو نام، ایمیل یا موبایل..."
                   class="form-input !pe-10">
            <button type="submit" class="absolute inset-y-0 end-3 flex items-center text-slate-400">
                <iconify-icon icon="lucide:search" class="text-xl"></iconify-icon>
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="text-slate-400 text-xs uppercase bg-slate-50/30 dark:bg-slate-800/30">
                    <th class="px-6 py-4 font-medium">مشتری</th>
                    <th class="px-6 py-4 font-medium">موبایل</th>
                    <th class="px-6 py-4 font-medium text-center">واچ‌لیست</th>
                    <th class="px-6 py-4 font-medium text-center">درخواست‌ها</th>
                    <th class="px-6 py-4 font-medium">عضویت</th>
                    <th class="px-6 py-4 font-medium text-center">وضعیت</th>
                    <th class="px-6 py-4 font-medium w-28">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php if (empty($customers)): ?>
                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">
                        <iconify-icon icon="lucide:users" class="text-5xl mb-3 opacity-20"></iconify-icon>
                        <div><?php echo $search ? 'نتیجه‌ای یافت نشد.' : 'هنوز مشتری‌ای ثبت‌نام نکرده است.'; ?></div>
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($customers as $c): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold uppercase shrink-0"><?php echo e(mb_substr($c['name'], 0, 1)); ?></div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 dark:text-white truncate"><?php echo e($c['name']); ?></div>
                                <div class="text-xs text-slate-400 truncate" dir="ltr"><?php echo e($c['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500" dir="ltr"><?php echo e($c['mobile'] ?: '—'); ?></td>
                    <td class="px-6 py-4 text-center"><span class="text-sm font-bold"><?php echo (int) $c['watch_count']; ?></span></td>
                    <td class="px-6 py-4 text-center"><span class="text-sm font-bold"><?php echo (int) $c['req_count']; ?></span></td>
                    <td class="px-6 py-4 text-xs text-slate-400 font-mono" dir="ltr"><?php echo e(date('Y-m-d', strtotime($c['created_at']))); ?></td>
                    <td class="px-6 py-4 text-center">
                        <?php if ((int) $c['status'] === 1): ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">فعال</span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">غیرفعال</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1">
                            <form method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-primary rounded-lg transition-colors" title="<?php echo (int) $c['status'] === 1 ? 'غیرفعال کردن' : 'فعال کردن'; ?>">
                                    <iconify-icon icon="<?php echo (int) $c['status'] === 1 ? 'lucide:user-x' : 'lucide:user-check'; ?>" class="text-xl"></iconify-icon>
                                </button>
                            </form>
                            <form method="POST" class="inline" onsubmit="return confirm('حذف این مشتری و همه داده‌هایش؟')">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                <button type="submit" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="حذف">
                                    <iconify-icon icon="lucide:trash-2" class="text-xl"></iconify-icon>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
