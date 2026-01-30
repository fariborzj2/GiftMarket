<?php
$pageTitle = 'مدیریت برندها';
require_once 'layout_header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = db()->prepare("SELECT logo FROM brands WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $logo = $stmt->fetchColumn();
        if ($logo && file_exists(__DIR__ . '/../' . $logo)) {
            unlink(__DIR__ . '/../' . $logo);
        }

        $stmt = db()->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $msg = 'برند با موفقیت حذف شد!';
    } catch (PDOException $e) {
        $msg = 'خطا: امکان حذف برند وجود ندارد. ممکن است در حال استفاده باشد.';
    }
    $action = 'list';
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $code = strtolower(clean($_POST['code']));
    $id = $_POST['id'] ?? '';

    $logo_path = $_POST['old_logo'] ?? '';

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $upload_dir = '../assets/images/brand/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_exts)) {
            $file_name = $code . '_' . time() . '.' . $file_ext;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $file_name)) {
                if ($logo_path && file_exists(__DIR__ . '/../' . $logo_path)) {
                    unlink(__DIR__ . '/../' . $logo_path);
                }
                $logo_path = 'assets/images/brand/' . $file_name;
            }
        } else {
            $msg = 'خطا: پسوند فایل مجاز نیست. (فقط تصاویر مجاز هستند)';
        }
    }

    try {
        if (!empty($id)) {
            $stmt = db()->prepare("UPDATE brands SET name=?, code=?, logo=? WHERE id=?");
            $stmt->execute([$name, $code, $logo_path, $id]);
            $msg = 'برند با موفقیت بروزرسانی شد!';
        } else {
            $stmt = db()->prepare("INSERT INTO brands (name, code, logo) VALUES (?, ?, ?)");
            $stmt->execute([$name, $code, $logo_path]);
            $msg = 'برند با موفقیت اضافه شد!';
        }
        $action = 'list';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $msg = 'خطا: کد برند باید یکتا باشد (این کد قبلاً ثبت شده است).';
        } else {
            $msg = 'خطا در پایگاه داده: ' . $e->getMessage();
        }
        $action = (!empty($id)) ? 'edit' : 'add';
    }
}
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <?php if ($msg): ?>
            <div class="<?php echo (strpos($msg, 'خطا') === false) ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30'; ?> px-6 py-3 rounded-xl border text-sm flex items-center gap-3">
                <span><?php echo (strpos($msg, 'خطا') === false) ? '✅' : '❌'; ?></span>
                <?php echo e($msg); ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="brands.php?action=add" class="btn-primary shadow-lg shadow-primary/30">
        <span>➕</span>
        <span>افزودن برند جدید</span>
    </a>
</div>

<?php if ($action === 'list'):
    $brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
?>

    <div class="admin-card !p-0 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
            <h3 class="text-lg flex items-center gap-2 m-0">
                <span class="text-primary">🏷️</span>
                <span>لیست برندها</span>
            </h3>
            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                <?php echo count($brands); ?> برند
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="text-slate-400 text-xs uppercase bg-slate-50/30 dark:bg-slate-800/30">
                        <th class="px-6 py-4 font-medium w-12"></th>
                        <th class="px-6 py-4 font-medium w-24 text-center">لوگو</th>
                        <th class="px-6 py-4 font-medium">نام برند</th>
                        <th class="px-6 py-4 font-medium">کد شناسایی</th>
                        <th class="px-6 py-4 font-medium w-32">عملیات</th>
                    </tr>
                </thead>
                <tbody id="sortable-brands" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php if (empty($brands)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                <div class="text-4xl mb-4">📭</div>
                                هیچ برندی یافت نشد.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($brands as $b): ?>
                    <tr data-id="<?php echo $b['id']; ?>" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                        <td class="px-6 py-4 cursor-move drag-handle text-slate-300 group-hover:text-slate-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                        </td>
                        <td class="px-6 py-4">
                            <div class="w-12 h-12 mx-auto rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-1.5 flex items-center justify-center overflow-hidden">
                                <?php if ($b['logo']): ?>
                                    <img src="../<?php echo e($b['logo']); ?>" alt="" class="max-w-full max-h-full object-contain">
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">NO LOGO</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-bold text-slate-900 dark:text-white"><?php echo e($b['name']); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 font-mono text-slate-600 dark:text-slate-400">
                                <?php echo strtoupper(e($b['code'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <a href="brands.php?action=edit&id=<?php echo e($b['id']); ?>" class="p-2 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="ویرایش">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <a href="brands.php?action=delete&id=<?php echo e($b['id']); ?>" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" onclick="return confirm('آیا از حذف این برند اطمینان دارید؟')" title="حذف">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'):
    $defaultData = ['id' => '', 'name' => '', 'code' => '', 'logo' => ''];
    $editData = $defaultData;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($msg) && strpos($msg, 'خطا') !== false) {
        $editData = array_merge($defaultData, $_POST);
        $editData['logo'] = $logo_path ?? ($_POST['old_logo'] ?? '');
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $stmt = db()->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) {
            $editData = array_merge($defaultData, $fetched);
        }
    }
?>
    <div class="admin-card max-w-2xl mx-auto">
        <h3 class="text-xl mb-8 flex items-center gap-2">
            <span class="text-primary"><?php echo $action === 'add' ? '➕' : '📝'; ?></span>
            <span><?php echo $action === 'add' ? 'افزودن برند جدید' : 'ویرایش برند'; ?></span>
        </h3>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">
            <input type="hidden" name="old_logo" value="<?php echo e($editData['logo']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">نام برند</label>
                    <input type="text" name="name" value="<?php echo e($editData['name']); ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                           placeholder="مثلاً Apple, PlayStation">
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">کد برند</label>
                    <input type="text" name="code" value="<?php echo e($editData['code']); ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                           placeholder="مثلاً apple, psn">
                </div>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">لوگو برند</label>
                <div class="flex items-start gap-4 p-4 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                    <div class="flex-1">
                        <input type="file" name="logo" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>
                               class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-blue-600 cursor-pointer">
                        <p class="text-xs text-slate-400 mt-2">فرمت‌های مجاز: JPG, PNG, SVG, WEBP. حداکثر ۲ مگابایت.</p>
                    </div>
                    <?php if ($editData['logo']): ?>
                        <div class="w-16 h-16 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-2 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                            <img src="../<?php echo e($editData['logo']); ?>" alt="" class="max-w-full max-h-full object-contain">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="btn-primary flex-1 py-3">ذخیره اطلاعات</button>
                <a href="brands.php" class="px-6 py-3 rounded-full border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium text-center">انصراف</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
    if (document.getElementById('sortable-brands')) {
        new Sortable(document.getElementById('sortable-brands'), {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-primary/5',
            onEnd: function() {
                let ids = [];
                document.querySelectorAll('#sortable-brands tr').forEach(row => {
                    ids.push(row.dataset.id);
                });

                let formData = new FormData();
                formData.append('table', 'brands');
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
