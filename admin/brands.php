<?php
$pageTitle = 'مدیریت برندها';
require_once 'layout_header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        // Get logo path to delete file
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
                // Delete old logo if exists
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
            // Update
            $stmt = db()->prepare("UPDATE brands SET name=?, code=?, logo=? WHERE id=?");
            $stmt->execute([$name, $code, $logo_path, $id]);
            $msg = 'برند با موفقیت بروزرسانی شد!';
        } else {
            // Insert
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

<div class="d-flex just-between align-center mb-30">
    <div>
        <?php if ($msg): ?>
            <div style="background: <?php echo (strpos($msg, 'خطا') === false) ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo (strpos($msg, 'خطا') === false) ? '#166534' : '#991b1b'; ?>; padding: 10px 20px; border-radius: 10px; margin-bottom: 20px;">
                <?php echo e($msg); ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="brands.php?action=add" class="btn-primary radius-100">افزودن برند جدید</a>
</div>

<?php if ($action === 'list'):
    $search = clean($_GET['search'] ?? '');
    $query = "SELECT * FROM brands WHERE 1=1";
    $params = [];
    if ($search) {
        $query .= " AND (name LIKE ? OR code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $query .= " ORDER BY name ASC";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $brands = $stmt->fetchAll();
?>
    <div class="admin-card mb-30">
        <form method="GET" class="d-flex-wrap gap-15 align-end">
            <div class="input-item grow-1" style="min-width: 200px;">
                <div class="input-label">جستجو (نام یا کد برند)</div>
                <div class="input">
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="مثلاً Apple, apple">
                </div>
            </div>
            <div class="d-flex gap-10">
                <button type="submit" class="btn-primary radius-100" style="height: 48px;">اعمال فیلتر</button>
                <a href="brands.php" class="btn radius-100 d-flex align-center just-center" style="height: 48px; border: 1px solid var(--color-border);">حذف فیلتر</a>
            </div>
        </form>
    </div>

    <div class="admin-card" style="padding: 0; overflow: hidden; border-radius: 15px;">
        <div style="background: var(--color-body); padding: 15px 25px; border-bottom: 1px solid var(--color-border);" class="d-flex align-center just-between">
            <h3 class="color-text d-flex align-center gap-10 font-size-1-1 m-0">
                <span class="icon" style="color: var(--color-primary);">🏷️</span> لیست برندها
            </h3>
            <span class="font-size-0-8 color-bright"><?php echo count($brands); ?> برند</span>
        </div>
        <div class="table-wrap" style="border: none; border-radius: 0;">
            <table style="margin: 0;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.02);">
                        <th style="width: 80px; text-align: center;">لوگو</th>
                        <th>نام برند</th>
                        <th>کد</th>
                        <th style="width: 150px;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($brands)): ?>
                        <tr><td colspan="4" class="text-center">هیچ برندی یافت نشد.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($brands as $b): ?>
                    <tr>
                        <td style="text-align: center;">
                            <?php if ($b['logo']): ?>
                                <img src="../<?php echo e($b['logo']); ?>" alt="" style="width: 38px; height: 38px; object-fit: contain; background: var(--color-surface); padding: 5px; border-radius: 8px; border: 1px solid var(--color-border); margin: auto;">
                            <?php else: ?>
                                <div style="width: 38px; height: 38px; background: var(--color-body); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: auto; color: var(--color-border);">?</div>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold"><?php echo e($b['name']); ?></td>
                        <td><code><?php echo strtoupper(e($b['code'])); ?></code></td>
                        <td class="d-flex gap-10">
                            <a href="brands.php?action=edit&id=<?php echo e($b['id']); ?>" class="btn-sm" style="color: var(--color-primary); border-color: var(--color-primary); background: var(--color-surface); width: auto;">ویرایش</a>
                            <a href="brands.php?action=delete&id=<?php echo e($b['id']); ?>" class="btn-sm" style="color: #ef4444; border-color: #fca5a5; background: var(--color-surface); width: auto;" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
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
    <div class="admin-card max-w600">
        <h3 class="color-title mb-30"><?php echo $action === 'add' ? 'افزودن برند جدید' : 'ویرایش برند'; ?></h3>
        <form method="POST" enctype="multipart/form-data" class="contact-form" style="box-shadow: none; padding: 0;">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">
            <input type="hidden" name="old_logo" value="<?php echo e($editData['logo']); ?>">

            <div class="input-item mb-20">
                <div class="input-label">نام برند</div>
                <div class="input">
                    <input type="text" name="name" value="<?php echo e($editData['name']); ?>" required placeholder="مثلاً Apple, PlayStation, Xbox">
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">کد برند</div>
                <div class="input">
                    <input type="text" name="code" value="<?php echo e($editData['code']); ?>" required placeholder="مثلاً apple, psn, xbox">
                </div>
            </div>

            <div class="input-item mb-30">
                <div class="input-label">لوگو برند</div>
                <div class="input" style="height: auto; padding: 10px;">
                    <input type="file" name="logo" accept="image/*" <?php echo $action === 'add' ? 'required' : ''; ?>>
                </div>
                <?php if ($editData['logo']): ?>
                    <div class="mt-10">
                        <img src="../<?php echo e($editData['logo']); ?>" alt="" style="width: 64px; border-radius: 4px;">
                    </div>
                <?php endif; ?>
            </div>

            <div class="d-flex gap-10">
                <button type="submit" class="btn-primary radius-100">ذخیره برند</button>
                <a href="brands.php" class="btn radius-100" style="height: 48px;">انصراف</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once 'layout_footer.php'; ?>
