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
    $search = clean($_GET['search'] ?? '');
    $f_brand = clean($_GET['brand'] ?? '');
    $f_country = clean($_GET['country'] ?? '');
    $f_pack_size = clean($_GET['pack_size'] ?? '');

    $query = "SELECT products.*, countries.name as country_name, countries.flag as country_flag, brands.name as brand_name, brands.logo as brand_logo
              FROM products
              LEFT JOIN countries ON products.country = countries.code
              LEFT JOIN brands ON products.brand = brands.code
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND products.denomination LIKE ?";
        $params[] = "%$search%";
    }
    if ($f_brand) {
        $query .= " AND products.brand = ?";
        $params[] = $f_brand;
    }
    if ($f_country) {
        $query .= " AND products.country = ?";
        $params[] = $f_country;
    }
    if ($f_pack_size) {
        $query .= " AND products.pack_size = ?";
        $params[] = (int)$f_pack_size;
    }

    $query .= " ORDER BY brands.sort_order ASC, brand_name ASC, countries.sort_order ASC, country_name ASC, pack_size ASC, denomination ASC";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
    $countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
    $pack_sizes = db()->query("SELECT DISTINCT pack_size FROM products ORDER BY pack_size ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

    <div class="admin-card mb-30">
        <form method="GET" class="d-flex-wrap gap-15 align-end">
            <div class="input-item grow-1" style="min-width: 200px;">
                <div class="input-label">جستجو (مبلغ اعتبار)</div>
                <div class="input">
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="مثلاً 100 AED">
                </div>
            </div>

            <div class="input-item" style="min-width: 150px;">
                <div class="input-label">برند</div>
                <select name="brand" class="input" style="height: 48px; border: 1px solid var(--color-border); border-radius: 12px; padding: 0 15px; width: 100%; background: var(--color-body); color: var(--color-text);">
                    <option value="">همه برندها</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?php echo e($b['code']); ?>" <?php echo $f_brand == $b['code'] ? 'selected' : ''; ?>><?php echo e($b['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-item" style="min-width: 150px;">
                <div class="input-label">کشور</div>
                <select name="country" class="input" style="height: 48px; border: 1px solid var(--color-border); border-radius: 12px; padding: 0 15px; width: 100%; background: var(--color-body); color: var(--color-text);">
                    <option value="">همه کشورها</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?php echo e($c['code']); ?>" <?php echo $f_country == $c['code'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-item" style="min-width: 120px;">
                <div class="input-label">Pack Size</div>
                <select name="pack_size" class="input" style="height: 48px; border: 1px solid var(--color-border); border-radius: 12px; padding: 0 15px; width: 100%; background: var(--color-body); color: var(--color-text);">
                    <option value="">همه سایزها</option>
                    <?php foreach ($pack_sizes as $size): ?>
                        <option value="<?php echo e($size); ?>" <?php echo $f_pack_size == $size ? 'selected' : ''; ?>>Pack Of <?php echo e($size); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex gap-10 d-flex-wrap">
                <button type="submit" class="btn-primary radius-100" style="height: 48px;">اعمال فیلتر</button>
                <a href="products.php" class="btn radius-100 d-flex align-center just-center" style="height: 48px; border: 1px solid var(--color-border);">حذف فیلتر</a>
            </div>
        </form>
    </div>

<?php
    $grouped = [];
    foreach ($products as $p) {
        $brandName = $p['brand_name'] ?? strtoupper($p['brand']);
        $countryName = $p['country_name'] ?? strtoupper($p['country']);
        $packSize = "Pack Of " . $p['pack_size'];

        if (!isset($grouped[$brandName])) {
            $grouped[$brandName] = ['logo' => $p['brand_logo'], 'countries' => []];
        }
        if (!isset($grouped[$brandName]['countries'][$countryName])) {
            $grouped[$brandName]['countries'][$countryName] = ['flag' => $p['country_flag'], 'pack_sizes' => []];
        }
        $grouped[$brandName]['countries'][$countryName]['pack_sizes'][$packSize][] = $p;
    }
?>
    <?php if (empty($products)): ?>
    <div class="admin-card">
        <div class="text-center">هیچ محصولی یافت نشد.</div>
    </div>
    <?php endif; ?>

    <?php foreach ($grouped as $brandName => $brandData): ?>
    <div class="brand-section mb-50">
        <h2 class="color-title mb-20 mt-40 d-flex align-center gap-15 font-size-1-5">
            <?php if ($brandData['logo']): ?>
                <img src="../<?php echo e($brandData['logo']); ?>" alt="" style="width: 38px; height: 38px; object-fit: contain; background: var(--color-surface); padding: 5px; border-radius: 8px; border: 1px solid var(--color-border);">
            <?php else: ?>
                <span style="width: 12px; height: 12px; background: var(--color-primary); border-radius: 50%;"></span>
            <?php endif; ?>
            <?php echo e($brandName); ?>
        </h2>

        <?php foreach ($brandData['countries'] as $countryName => $countryData): ?>
        <div class="admin-card mb-30" style="padding: 0; overflow: hidden; border-radius: 15px;">
            <div style="background: var(--color-body); padding: 15px 25px; border-bottom: 1px solid var(--color-border);" class="d-flex align-center just-between">
                <h3 class="color-text d-flex align-center gap-10 font-size-1-1 m-0">
                    <?php if ($countryData['flag']): ?>
                        <img src="../<?php echo e($countryData['flag']); ?>" alt="" style="width: 24px; border-radius: 4px;">
                    <?php else: ?>
                        <span class="icon" style="color: var(--color-primary);">🌍</span>
                    <?php endif; ?>
                    <?php echo e($countryName); ?>
                </h3>
                <span class="font-size-0-8 color-bright"><?php echo count($countryData['pack_sizes']); ?> سایز پکیج</span>
            </div>

            <div style="padding: 20px;">
                <?php foreach ($countryData['pack_sizes'] as $packSizeName => $items): ?>
                <div class="pack-size-group <?php echo $packSizeName !== array_key_last($countryData['pack_sizes']) ? 'mb-30' : ''; ?>">
                    <h4 class="color-primary mb-15 font-size-0-9 d-flex align-center gap-5">
                        <span class="icon icon-size-14">📦</span> <?php echo e($packSizeName); ?>
                    </h4>
                    <div class="table-wrap" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px;">
                        <table style="margin: 0;">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.02);">
                                    <th>مبلغ اعتبار</th>
                                    <th>قیمت دیجیتال</th>
                                    <th>قیمت فیزیکی</th>
                                    <th style="width: 120px;">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $p): ?>
                                <tr>
                                    <td data-label="مبلغ اعتبار" class="font-bold"><?php echo e($p['denomination']); ?></td>
                                    <td data-label="قیمت دیجیتال"><?php echo e($p['price_digital']) . ' ' . e($p['currency']); ?></td>
                                    <td data-label="قیمت فیزیکی"><?php echo e($p['price_physical']) . ' ' . e($p['currency']); ?></td>
                                    <td data-label="عملیات">
                                        <div class="d-flex gap-10">
                                            <a href="products.php?action=edit&id=<?php echo e($p['id']); ?>" class="btn-sm" style="color: var(--color-primary); border-color: var(--color-primary); background: var(--color-surface); width: auto;">ویرایش</a>
                                            <a href="products.php?action=delete&id=<?php echo e($p['id']); ?>" class="btn-sm" style="color: #ef4444; border-color: #fca5a5; background: var(--color-surface); width: auto;" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

<?php elseif ($action === 'add' || $action === 'edit'):
    $countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
    $brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
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
                            <img class="selected-img" src="../<?php echo e($selectedBrand['logo'] ?? ''); ?>" alt="" style="width: 28px; <?php echo !$selectedBrand['logo'] ? 'display:none;' : ''; ?>">
                        </div>
                        <div class="selected-text"><?php echo e($selectedBrand['name'] ?? 'انتخاب برند'); ?></div>
                        <span class="icon icon-arrow-down icon-size-16 lt-auto"></span>
                    </div>

                    <input type="hidden" class="selected-option" name="brand" value="<?php echo e($editData['brand']); ?>" required>

                    <div class="drop-down-list" style="width: 100%; top: 100%;">
                        <?php foreach ($brands as $b): ?>
                            <div class="drop-option d-flex gap-10 align-center <?php echo $editData['brand'] == $b['code'] ? 'active' : ''; ?>" data-option="<?php echo e($b['code']); ?>">
                                <div class="drop-option-img"><img src="../<?php echo e($b['logo']); ?>" alt="" style="width: 28px;"></div>
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
                            <img class="selected-img" src="../<?php echo e($selectedCountry['flag'] ?? ''); ?>" alt="" style="width: 28px; <?php echo !$selectedCountry['flag'] ? 'display:none;' : ''; ?>">
                        </div>
                        <div class="selected-text"><?php echo e($selectedCountry['name'] ?? 'انتخاب کشور'); ?></div>
                        <span class="icon icon-arrow-down icon-size-16 lt-auto"></span>
                    </div>

                    <input type="hidden" class="selected-option" name="country" value="<?php echo e($editData['country']); ?>" required>

                    <div class="drop-down-list" style="width: 100%; top: 100%;">
                        <?php foreach ($countries as $c): ?>
                            <div class="drop-option d-flex gap-10 align-center <?php echo $editData['country'] == $c['code'] ? 'active' : ''; ?>" data-option="<?php echo e($c['code']); ?>">
                                <div class="drop-option-img"><img src="../<?php echo e($c['flag']); ?>" alt="" style="width: 28px;"></div>
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
