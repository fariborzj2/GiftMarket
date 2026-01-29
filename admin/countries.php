<?php
$pageTitle = 'مدیریت کشورها';
require_once 'layout_header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        // Get flag path to delete file
        $stmt = db()->prepare("SELECT flag FROM countries WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $flag = $stmt->fetchColumn();
        if ($flag && file_exists(__DIR__ . '/../' . $flag)) {
            unlink(__DIR__ . '/../' . $flag);
        }

        $stmt = db()->prepare("DELETE FROM countries WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $msg = 'کشور با موفقیت حذف شد!';
    } catch (PDOException $e) {
        $msg = 'خطا: امکان حذف کشور وجود ندارد. ممکن است در حال استفاده باشد.';
    }
    $action = 'list';
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                // Delete old flag if exists
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
            // Update
            $stmt = db()->prepare("UPDATE countries SET name=?, code=?, flag=?, currency=? WHERE id=?");
            $stmt->execute([$name, $code, $flag_path, $currency, $id]);
            $msg = 'کشور با موفقیت بروزرسانی شد!';
        } else {
            // Insert
            $stmt = db()->prepare("INSERT INTO countries (name, code, flag, currency) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $code, $flag_path, $currency]);
            $msg = 'کشور با موفقیت اضافه شد!';
        }
        $action = 'list';
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $msg = 'خطا: کد کشور باید یکتا باشد (این کد قبلاً ثبت شده است).';
        } else {
            $msg = 'خطا در پایگاه داده: ' . $e->getMessage();
        }
        // Keep the action as add/edit if there's an error
        $action = (!empty($id)) ? 'edit' : 'add';
    }
}

?>

<div class="d-flex just-between align-center mb-30">
    <div>
        <?php if ($msg): ?>
            <div style="background: <?php echo (strpos($msg, 'خطا') === false && strpos($msg, 'Error') === false) ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo (strpos($msg, 'خطا') === false && strpos($msg, 'Error') === false) ? '#166534' : '#991b1b'; ?>; padding: 10px 20px; border-radius: 10px; margin-bottom: 20px;">
                <?php echo e($msg); ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="countries.php?action=add" class="btn-primary radius-100">افزودن کشور جدید</a>
</div>

<?php if ($action === 'list'):
    $countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
?>

    <div class="admin-card" style="padding: 0; overflow: hidden; border-radius: 15px;">
        <div style="background: var(--color-body); padding: 15px 25px; border-bottom: 1px solid var(--color-border);" class="d-flex align-center just-between">
            <h3 class="color-text d-flex align-center gap-10 font-size-1-1 m-0">
                <span class="icon" style="color: var(--color-primary);">🌍</span> لیست کشورها
            </h3>
            <span class="font-size-0-8 color-bright"><?php echo count($countries); ?> کشور</span>
        </div>
        <div class="table-wrap" style="border: none; border-radius: 0;">
            <table style="margin: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02);">
                        <th style="width: 40px;"></th>
                        <th style="width: 80px; text-align: center;">پرچم</th>
                        <th>نام کشور</th>
                        <th>کد</th>
                        <th>واحد پول</th>
                        <th style="width: 150px;">عملیات</th>
                    </tr>
                </thead>
                <tbody id="sortable-countries">
                    <?php if (empty($countries)): ?>
                        <tr><td colspan="6" class="text-center">هیچ کشوری یافت نشد.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($countries as $c): ?>
                    <tr data-id="<?php echo $c['id']; ?>">
                        <td data-label="جابجایی" style="cursor: move;" class="drag-handle">☰</td>
                        <td data-label="پرچم" style="text-align: center;">
                            <?php if ($c['flag']): ?>
                                <img src="../<?php echo e($c['flag']); ?>" alt="" style="width: 32px; height: auto; border-radius: 4px; border: 1px solid var(--color-border); margin: auto;">
                            <?php else: ?>
                                <div style="width: 32px; height: 24px; background: var(--color-body); border-radius: 4px; display: flex; align-items: center; justify-content: center; margin: auto; color: var(--color-border);">?</div>
                            <?php endif; ?>
                        </td>
                        <td data-label="نام کشور" class="font-bold"><?php echo e($c['name']); ?></td>
                        <td data-label="کد"><code><?php echo strtoupper(e($c['code'])); ?></code></td>
                        <td data-label="واحد پول">
                            <span class="color-primary font-bold"><?php echo e($c['currency']); ?></span>
                        </td>
                        <td data-label="عملیات">
                            <div class="d-flex gap-10">
                                <a href="countries.php?action=edit&id=<?php echo e($c['id']); ?>" class="btn-sm" style="color: var(--color-primary); border-color: var(--color-primary); background: var(--color-surface); width: auto;">ویرایش</a>
                                <a href="countries.php?action=delete&id=<?php echo e($c['id']); ?>" class="btn-sm" style="color: #ef4444; border-color: #fca5a5; background: var(--color-surface); width: auto;" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
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

    // If we're coming from a failed POST, try to preserve inputs
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($msg) && strpos($msg, 'خطا') !== false) {
        $editData = array_merge($defaultData, $_POST);
        // Special case for flag, we use the flag_path we already processed
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
    <div class="admin-card max-w600">
        <h3 class="color-title mb-30"><?php echo $action === 'add' ? 'افزودن کشور جدید' : 'ویرایش کشور'; ?></h3>
        <form method="POST" enctype="multipart/form-data" class="contact-form" style="box-shadow: none; padding: 0;">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">
            <input type="hidden" name="old_flag" value="<?php echo e($editData['flag']); ?>">

            <div class="input-item mb-20">
                <div class="input-label">نام کشور</div>
                <div class="input">
                    <input type="text" name="name" value="<?php echo e($editData['name']); ?>" required placeholder="مثلاً امارات متحده عربی، ایالات متحده">
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">کد کشور</div>
                <div class="input">
                    <input type="text" name="code" value="<?php echo e($editData['code']); ?>" required placeholder="مثلاً uae, usa, uk">
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">واحد پول</div>
                <div class="input">
                    <input type="text" name="currency" value="<?php echo e($editData['currency']); ?>" required placeholder="مثلاً AED, USD, IRT">
                </div>
            </div>

            <div class="input-item mb-30">
                <div class="input-label">تصویر پرچم</div>
                <div class="input" style="height: auto; padding: 10px;">
                    <input type="file" name="flag" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                </div>
                <?php if ($editData['flag']): ?>
                    <div class="mt-10">
                        <img src="../<?php echo e($editData['flag']); ?>" alt="" style="width: 64px; border-radius: 4px;">
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-10">
                <button type="submit" class="btn-primary radius-100">ذخیره کشور</button>
                <a href="countries.php" class="btn radius-100" style="height: 48px;">انصراف</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
    if (document.getElementById('sortable-countries')) {
        new Sortable(document.getElementById('sortable-countries'), {
            handle: '.drag-handle',
            animation: 150,
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
