<?php
$pageTitle = 'مدیریت کشورها';
require_once 'layout_header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';
$csrfToken = generateCsrfToken();

// Handle Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    // Handle Delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        try {
            $stmt = db()->prepare("SELECT flag FROM countries WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $flag = $stmt->fetchColumn();
            if ($flag && file_exists(__DIR__ . '/../' . $flag)) {
                unlink(__DIR__ . '/../' . $flag);
            }

            $stmt = db()->prepare("DELETE FROM countries WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $msg = 'کشور با موفقیت حذف شد!';
        } catch (PDOException $e) {
            $msg = 'خطا: امکان حذف کشور وجود ندارد. ممکن است در حال استفاده باشد.';
        }
        header("Location: countries.php?msg=" . urlencode($msg));
        exit;
    }

    // Handle Add/Edit
    if (isset($_POST['name'])) {
    $name = clean($_POST['name']);
    $code = strtolower(clean($_POST['code']));
    $currency = strtoupper(clean($_POST['currency']));
    $id = $_POST['id'] ?? '';

    $flag_path = $_POST['old_flag'] ?? '';

    if (isset($_FILES['flag']) && $_FILES['flag']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $upload_dir = '../assets/images/flag/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['flag']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_exts)) {
            $file_name = $code . '_' . time() . '.' . $file_ext;

            if (move_uploaded_file($_FILES['flag']['tmp_name'], $upload_dir . $file_name)) {
                if ($flag_path && file_exists(__DIR__ . '/../' . $flag_path)) {
                    unlink(__DIR__ . '/../' . $flag_path);
                }
                $flag_path = 'assets/images/flag/' . $file_name;
            }
        } else {
            $msg = 'خطا: پسوند فایل مجاز نیست. (فقط تصاویر مجاز هستند)';
        }
    }

    try {
        if (!empty($id)) {
            $stmt = db()->prepare("UPDATE countries SET name=?, code=?, flag=?, currency=? WHERE id=?");
            $stmt->execute([$name, $code, $flag_path, $currency, $id]);
            $msg = 'کشور با موفقیت بروزرسانی شد!';
        } else {
            $stmt = db()->prepare("INSERT INTO countries (name, code, flag, currency) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $code, $flag_path, $currency]);
            $msg = 'کشور با موفقیت اضافه شد!';
        }
        header("Location: countries.php?msg=" . urlencode($msg));
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $msg = 'خطا: کد کشور باید یکتا باشد (این کد قبلاً ثبت شده است).';
        } else {
            $msg = 'خطا در پایگاه داده: ' . $e->getMessage();
        }
        $action = (!empty($id)) ? 'edit' : 'add';
    }
}
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <?php
        $displayMsg = $msg ?: ($_GET['msg'] ?? '');
        if ($displayMsg): ?>
            <div class="<?php echo (strpos($displayMsg, 'خطا') === false) ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30'; ?> px-6 py-3 rounded-xl border text-sm flex items-center gap-3">
                <iconify-icon icon="<?php echo (strpos($displayMsg, 'خطا') === false) ? 'solar:check-circle-bold-duotone' : 'solar:danger-bold-duotone'; ?>" class="text-xl"></iconify-icon>
                <?php echo e($displayMsg); ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="countries.php?action=add" class="btn-primary ">
        <iconify-icon icon="solar:add-circle-bold-duotone" class="text-xl"></iconify-icon>
        <span>افزودن کشور جدید</span>
    </a>
</div>

<?php if ($action === 'list'):
    $countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
?>

    <div class="admin-card !p-0 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
            <h3 class="text-lg flex items-center gap-2 m-0">
                <iconify-icon icon="solar:globus-bold-duotone" class="text-primary text-2xl"></iconify-icon>
                <span>لیست کشورها</span>
            </h3>
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                <?php echo count($countries); ?> کشور
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="text-slate-400 text-xs uppercase bg-slate-50/30 dark:bg-slate-800/30">
                        <th class="px-6 py-4 font-medium w-12"></th>
                        <th class="px-6 py-4 font-medium w-24 text-center">پرچم</th>
                        <th class="px-6 py-4 font-medium">نام کشور</th>
                        <th class="px-6 py-4 font-medium">کد</th>
                        <th class="px-6 py-4 font-medium">واحد پول</th>
                        <th class="px-6 py-4 font-medium w-32">عملیات</th>
                    </tr>
                </thead>
                <tbody id="sortable-countries" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php if (empty($countries)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                <iconify-icon icon="solar:mailbox-bold-duotone" class="text-5xl mb-4 opacity-20"></iconify-icon>
                                <div>هیچ کشوری یافت نشد.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($countries as $c): ?>
                    <tr data-id="<?php echo $c['id']; ?>" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                        <td class="px-6 py-4 cursor-move drag-handle text-slate-300 group-hover:text-slate-500 transition-colors">
                            <iconify-icon icon="solar:reorder-bold-duotone" class="text-xl"></iconify-icon>
                        </td>
                        <td class="px-6 py-4">
                            <div class="w-7 h-7 mx-auto rounded shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden shrink-0">
                                <?php if ($c['flag']): ?>
                                    <img src="../<?php echo e($c['flag']); ?>" alt="" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] text-slate-400">?</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-900 dark:text-white"><?php echo e($c['name']); ?></td>
                        <td class="px-6 py-4 text-sm uppercase font-mono text-slate-500"><?php echo e($c['code']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-bold text-sm">
                                <?php echo e($c['currency']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="countries.php?action=edit&id=<?php echo e($c['id']); ?>" class="p-2 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="ویرایش">
                                    <iconify-icon icon="solar:pen-new-square-bold-duotone" class="text-xl"></iconify-icon>
                                </a>
                                <form method="POST" class="inline" onsubmit="return confirm('آیا از حذف این کشور اطمینان دارید؟')">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="حذف">
                                        <iconify-icon icon="solar:trash-bin-trash-bold-duotone" class="text-xl"></iconify-icon>
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

<?php elseif ($action === 'add' || $action === 'edit'):
    $defaultData = ['id' => '', 'name' => '', 'code' => '', 'flag' => '', 'currency' => ''];
    $editData = $defaultData;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($msg) && strpos($msg, 'خطا') !== false) {
        $editData = array_merge($defaultData, $_POST);
        $editData['flag'] = $flag_path ?? ($_POST['old_flag'] ?? '');
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $stmt = db()->prepare("SELECT * FROM countries WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) {
            $editData = array_merge($defaultData, $fetched);
        }
    }
?>
    <div class="admin-card max-w-2xl mx-auto">
        <h3 class="text-xl mb-8 flex items-center gap-2">
            <iconify-icon icon="<?php echo $action === 'add' ? 'solar:add-circle-bold-duotone' : 'solar:pen-new-square-bold-duotone'; ?>" class="text-primary text-2xl"></iconify-icon>
            <span><?php echo $action === 'add' ? 'افزودن کشور جدید' : 'ویرایش کشور'; ?></span>
        </h3>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">
            <input type="hidden" name="old_flag" value="<?php echo e($editData['flag']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">نام کشور</label>
                    <input type="text" name="name" value="<?php echo e($editData['name']); ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                           placeholder="مثلاً امارات متحده عربی">
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">کد کشور</label>
                    <input type="text" name="code" value="<?php echo e($editData['code']); ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                           placeholder="مثلاً uae">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">واحد پول (Currency)</label>
                    <input type="text" name="currency" value="<?php echo e($editData['currency']); ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                           placeholder="مثلاً AED">
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">تصویر پرچم</label>
                    <div class="flex items-start gap-4 p-4 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                        <div class="flex-1 overflow-hidden">
                            <input type="file" name="flag" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>
                                   class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-blue-600 cursor-pointer">
                        </div>
                        <?php if ($editData['flag']): ?>
                            <div class="w-12 h-8 rounded shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden shrink-0">
                                <img src="../<?php echo e($editData['flag']); ?>" alt="" class="w-full h-full object-cover">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="btn-primary flex-1 py-3">ذخیره اطلاعات</button>
                <a href="countries.php" class="px-6 py-3 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium text-center">انصراف</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
    if (document.getElementById('sortable-countries')) {
        new Sortable(document.getElementById('sortable-countries'), {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-primary/5',
            onEnd: function() {
                let ids = [];
                document.querySelectorAll('#sortable-countries tr').forEach(row => {
                    ids.push(row.dataset.id);
                });

                let formData = new FormData();
                formData.append('table', 'countries');
                ids.forEach(id => formData.append('ids[]', id));

                fetch('api_update_order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error updating order: ' + data.message);
                    }
                });
            }
        });
    }
</script>

<?php require_once 'layout_footer.php'; ?>
