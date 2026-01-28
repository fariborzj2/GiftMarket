<?php
$pageTitle = 'مدیریت کشورها';
require_once 'layout_header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    try {
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

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $stmt = db()->prepare("UPDATE countries SET name=?, code=? WHERE id=?");
        $stmt->execute([$name, $code, $_POST['id']]);
        $msg = 'کشور با موفقیت بروزرسانی شد!';
    } else {
        // Insert
        try {
            $stmt = db()->prepare("INSERT INTO countries (name, code) VALUES (?, ?)");
            $stmt->execute([$name, $code]);
            $msg = 'کشور با موفقیت اضافه شد!';
        } catch (PDOException $e) {
            $msg = 'خطا: کد کشور باید یکتا باشد.';
        }
    }
    $action = 'list';
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
    $countries = db()->query("SELECT * FROM countries ORDER BY name ASC")->fetchAll();
?>
    <div class="admin-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>کد</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($countries)): ?>
                        <tr><td colspan="3" class="text-center">هیچ کشوری یافت نشد.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($countries as $c): ?>
                    <tr>
                        <td><?php echo e($c['name']); ?></td>
                        <td><?php echo strtoupper(e($c['code'])); ?></td>
                        <td class="d-flex gap-10">
                            <a href="countries.php?action=edit&id=<?php echo e($c['id']); ?>" class="btn-sm" style="color: var(--color-primary);">ویرایش</a>
                            <a href="countries.php?action=delete&id=<?php echo e($c['id']); ?>" class="btn-sm" style="color: #ef4444;" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'):
    $editData = ['id' => '', 'name' => '', 'code' => ''];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = db()->prepare("SELECT * FROM countries WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $editData = $stmt->fetch();
    }
?>
    <div class="admin-card max-w600">
        <h3 class="color-title mb-30"><?php echo $action === 'add' ? 'افزودن کشور جدید' : 'ویرایش کشور'; ?></h3>
        <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">

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

            <div class="d-flex gap-10">
                <button type="submit" class="btn-primary radius-100">ذخیره کشور</button>
                <a href="countries.php" class="btn radius-100" style="height: 48px;">انصراف</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once 'layout_footer.php'; ?>
