<?php
$pageTitle = 'مدیریت محصولات';
require_once 'layout_header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = db()->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $msg = 'محصول با موفقیت حذف شد!';
    $action = 'list';
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = clean($_POST['brand']);
    $denomination = clean($_POST['denomination']);
    $pack_size = (int)($_POST['pack_size'] ?? 1);
    $country = clean($_POST['country']);
    $price_digital = (float)$_POST['price_digital'];
    $price_physical = (float)$_POST['price_physical'];
    $price = $price_digital; // Backward compatibility

    // Fetch currency from country
    $stmt = db()->prepare("SELECT currency FROM countries WHERE code = ?");
    $stmt->execute([$country]);
    $currency = $stmt->fetchColumn() ?: 'AED';

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $stmt = db()->prepare("UPDATE products SET brand=?, denomination=?, pack_size=?, country=?, price=?, price_digital=?, price_physical=?, currency=? WHERE id=?");
        $stmt->execute([$brand, $denomination, $pack_size, $country, $price, $price_digital, $price_physical, $currency, $_POST['id']]);
        $msg = 'محصول با موفقیت بروزرسانی شد!';
    } else {
        // Insert
        $stmt = db()->prepare("INSERT INTO products (brand, denomination, pack_size, country, price, price_digital, price_physical, currency) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$brand, $denomination, $pack_size, $country, $price, $price_digital, $price_physical, $currency]);
        $msg = 'محصول با موفقیت اضافه شد!';
    }
    $action = 'list';
}

?>

<div class="d-flex just-between align-center mb-30">
    <div>
        <?php if ($msg): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 20px; border-radius: 10px; margin-bottom: 20px;">
                <?php echo e($msg); ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="products.php?action=add" class="btn-primary radius-100">افزودن محصول جدید</a>
</div>

<?php if ($action === 'list'):
    $products = db()->query("SELECT products.*, countries.name as country_name, brands.name as brand_name FROM products LEFT JOIN countries ON products.country = countries.code LEFT JOIN brands ON products.brand = brands.code ORDER BY products.id DESC")->fetchAll();
?>
    <div class="admin-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>برند</th>
                        <th>مبلغ اعتبار</th>
                        <th>Pack Size</th>
                        <th>کشور</th>
                        <th>قیمت دیجیتال</th>
                        <th>قیمت فیزیکی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center">هیچ محصولی یافت نشد.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?php echo e($p['brand_name'] ?? strtoupper($p['brand'])); ?></td>
                        <td><?php echo e($p['denomination']); ?></td>
                        <td><?php echo e($p['pack_size']); ?></td>
                        <td><?php echo e($p['country_name'] ?? strtoupper($p['country'])); ?></td>
                        <td><?php echo e($p['price_digital']) . ' ' . e($p['currency']); ?></td>
                        <td><?php echo e($p['price_physical']) . ' ' . e($p['currency']); ?></td>
                        <td class="d-flex gap-10">
                            <a href="products.php?action=edit&id=<?php echo e($p['id']); ?>" class="btn-sm" style="color: var(--color-primary);">ویرایش</a>
                            <a href="products.php?action=delete&id=<?php echo e($p['id']); ?>" class="btn-sm" style="color: #ef4444;" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'):
    $countries = db()->query("SELECT * FROM countries ORDER BY name ASC")->fetchAll();
    $brands = db()->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
    $editData = ['id' => '', 'brand' => 'apple', 'denomination' => '', 'pack_size' => '1', 'country' => '', 'price_digital' => '', 'price_physical' => '', 'currency' => 'AED'];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) {
            $editData = $fetched;
        }
    }
?>
    <div class="admin-card max-w600">
        <h3 class="color-title mb-30"><?php echo $action === 'add' ? 'افزودن محصول جدید' : 'ویرایش محصول'; ?></h3>
        <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">

            <div class="input-item mb-20">
                <div class="input-label">برند</div>
                <div class="drop-down w-full">
                    <?php
                    $selectedBrand = null;
                    foreach ($brands as $b) {
                        if ($b['code'] == $editData['brand']) {
                            $selectedBrand = $b;
                            break;
                        }
                    }
                    ?>
                    <div class="drop-down-btn d-flex align-center gap-10 pointer" style="border: 1px solid var(--color-border); padding: 10px 15px; border-radius: 12px; background: var(--color-body);">
                        <div class="drop-down-img">
                            <img class="selected-img" src="../<?php echo e($selectedBrand['logo'] ?? ''); ?>" alt="" style="width: 24px; <?php echo !$selectedBrand['logo'] ? 'display:none;' : ''; ?>">
                        </div>
                        <div class="selected-text"><?php echo e($selectedBrand['name'] ?? 'انتخاب برند'); ?></div>
                        <span class="icon icon-arrow-down icon-size-16 lt-auto"></span>
                    </div>

                    <input type="hidden" class="selected-option" name="brand" value="<?php echo e($editData['brand']); ?>" required>

                    <div class="drop-down-list" style="width: 100%; top: 100%;">
                        <?php foreach ($brands as $b): ?>
                            <div class="drop-option d-flex gap-10 align-center <?php echo $editData['brand'] == $b['code'] ? 'active' : ''; ?>" data-option="<?php echo e($b['code']); ?>">
                                <div class="drop-option-img"><img src="../<?php echo e($b['logo']); ?>" alt="" style="width: 24px;"></div>
                                <span><?php echo e($b['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($brands)): ?>
                            <div class="pd-10 text-center color-bright font-size-0-9">ابتدا <a href="brands.php?action=add">یک برند اضافه کنید</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">مبلغ اعتبار</div>
                <div class="input">
                    <input type="text" name="denomination" value="<?php echo e($editData['denomination']); ?>" required placeholder="مثلاً 100 AED, $50">
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">Pack Size</div>
                <div class="input">
                    <input type="number" name="pack_size" value="<?php echo e($editData['pack_size']); ?>" required min="1">
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">کشور</div>
                <div class="drop-down w-full">
                    <?php
                    $selectedCountry = null;
                    foreach ($countries as $c) {
                        if ($c['code'] == $editData['country']) {
                            $selectedCountry = $c;
                            break;
                        }
                    }
                    ?>
                    <div class="drop-down-btn d-flex align-center gap-10 pointer" style="border: 1px solid var(--color-border); padding: 10px 15px; border-radius: 12px; background: var(--color-body);">
                        <div class="drop-down-img">
                            <img class="selected-img" src="../<?php echo e($selectedCountry['flag'] ?? ''); ?>" alt="" style="width: 24px; <?php echo !$selectedCountry['flag'] ? 'display:none;' : ''; ?>">
                        </div>
                        <div class="selected-text"><?php echo e($selectedCountry['name'] ?? 'انتخاب کشور'); ?></div>
                        <span class="icon icon-arrow-down icon-size-16 lt-auto"></span>
                    </div>

                    <input type="hidden" class="selected-option" name="country" value="<?php echo e($editData['country']); ?>" required>

                    <div class="drop-down-list" style="width: 100%; top: 100%;">
                        <?php foreach ($countries as $c): ?>
                            <div class="drop-option d-flex gap-10 align-center <?php echo $editData['country'] == $c['code'] ? 'active' : ''; ?>" data-option="<?php echo e($c['code']); ?>">
                                <div class="drop-option-img"><img src="../<?php echo e($c['flag']); ?>" alt="" style="width: 24px;"></div>
                                <span><?php echo e($c['name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($countries)): ?>
                            <div class="pd-10 text-center color-bright font-size-0-9">ابتدا <a href="countries.php?action=add">یک کشور اضافه کنید</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex-wrap gap-20 mb-30">
                <div class="input-item grow-1">
                    <div class="input-label">قیمت نسخه دیجیتال</div>
                    <div class="input">
                        <input type="number" step="0.01" name="price_digital" value="<?php echo e($editData['price_digital']); ?>" required>
                    </div>
                </div>
                <div class="input-item grow-1">
                    <div class="input-label">قیمت نسخه فیزیکی</div>
                    <div class="input">
                        <input type="number" step="0.01" name="price_physical" value="<?php echo e($editData['price_physical']); ?>" required>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-10">
                <button type="submit" class="btn-primary radius-100">ذخیره محصول</button>
                <a href="products.php" class="btn radius-100" style="height: 48px;">انصراف</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once 'layout_footer.php'; ?>
