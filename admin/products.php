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
    $db = db();
    $db->beginTransaction();
    try {
        $brand = clean($_POST['brand']);
        $denomination = clean($_POST['denomination']);
        $country = clean($_POST['country']);
        $pack_sizes = $_POST['pack_sizes'] ?? [];
        $prices_digital = $_POST['prices_digital'] ?? [];
        $prices_physical = $_POST['prices_physical'] ?? [];

        // Fetch currency from country
        $stmt = $db->prepare("SELECT currency FROM countries WHERE code = ?");
        $stmt->execute([$country]);
        $currency = $stmt->fetchColumn() ?: 'AED';

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $productId = $_POST['id'];
            $stmt = $db->prepare("UPDATE products SET brand=?, denomination=?, country=?, currency=? WHERE id=?");
            $stmt->execute([$brand, $denomination, $country, $currency, $productId]);
        } else {
            $stmt = $db->prepare("INSERT INTO products (brand, denomination, country, currency) VALUES (?, ?, ?, ?)");
            $stmt->execute([$brand, $denomination, $country, $currency]);
            $productId = $db->lastInsertId();
        }

        // Handle packs: delete existing and re-insert
        $stmt = $db->prepare("DELETE FROM product_packs WHERE product_id = ?");
        $stmt->execute([$productId]);

        $stmt = $db->prepare("INSERT INTO product_packs (product_id, pack_size, price_digital, price_physical) VALUES (?, ?, ?, ?)");
        foreach ($pack_sizes as $i => $size) {
            $stmt->execute([$productId, (int)$size, (float)$prices_digital[$i], (float)$prices_physical[$i]]);
        }

        $db->commit();
        $msg = 'محصول با موفقیت ذخیره شد!';
    } catch (Exception $e) {
        $db->rollBack();
        $msg = 'خطا در ذخیره محصول: ' . $e->getMessage();
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

    $query = "SELECT p.*, pk.id as pack_id, pk.pack_size, pk.price_digital, pk.price_physical,
                     countries.name as country_name, countries.flag as country_flag,
                     brands.name as brand_name, brands.logo as brand_logo
              FROM products p
              LEFT JOIN product_packs pk ON p.id = pk.product_id
              LEFT JOIN countries ON p.country = countries.code
              LEFT JOIN brands ON p.brand = brands.code
              WHERE 1=1";
    $params = [];

    if ($search) {
        $query .= " AND p.denomination LIKE ?";
        $params[] = "%$search%";
    }
    if ($f_brand) {
        $query .= " AND p.brand = ?";
        $params[] = $f_brand;
    }
    if ($f_country) {
        $query .= " AND p.country = ?";
        $params[] = $f_country;
    }
    if ($f_pack_size) {
        // Show products that have at least one pack of this size
        $query .= " AND p.id IN (SELECT product_id FROM product_packs WHERE pack_size = ?)";
        $params[] = (int)$f_pack_size;
    }

    $query .= " ORDER BY brands.sort_order ASC, brand_name ASC, countries.sort_order ASC, country_name ASC, p.denomination ASC, pk.pack_size ASC";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Grouping logic for the list view
    $products = [];
    foreach ($results as $row) {
        $pid = $row['id'];
        if (!isset($products[$pid])) {
            $products[$pid] = $row;
            $products[$pid]['packs'] = [];
        }
        if ($row['pack_id']) {
            $products[$pid]['packs'][] = [
                'id' => $row['pack_id'],
                'pack_size' => $row['pack_size'],
                'price_digital' => $row['price_digital'],
                'price_physical' => $row['price_physical']
            ];
        }
    }

    $brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
    $countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
    $pack_sizes = db()->query("SELECT DISTINCT pack_size FROM product_packs ORDER BY pack_size ASC")->fetchAll(PDO::FETCH_COLUMN);
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

        if (!isset($grouped[$brandName])) {
            $grouped[$brandName] = ['logo' => $p['brand_logo'], 'countries' => []];
        }
        if (!isset($grouped[$brandName]['countries'][$countryName])) {
            $grouped[$brandName]['countries'][$countryName] = ['flag' => $p['country_flag'], 'products' => []];
        }
        $grouped[$brandName]['countries'][$countryName]['products'][] = $p;
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
                <span class="font-size-0-8 color-bright"><?php echo count($countryData['products']); ?> محصول</span>
            </div>

            <div style="padding: 20px;">
                <div class="table-wrap" style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 10px;">
                    <table style="margin: 0;">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.02);">
                                <th>نام محصول (اعتبار)</th>
                                <th>پک‌های موجود</th>
                                <th style="width: 120px;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($countryData['products'] as $p): ?>
                            <tr>
                                <td data-label="نام محصول" class="font-bold">
                                    <?php echo e($p['denomination']); ?>
                                    <div class="font-size-0-8 color-bright font-normal"><?php echo e($p['currency']); ?></div>
                                </td>
                                <td data-label="پک‌ها">
                                    <div class="d-flex-wrap gap-10">
                                        <?php foreach ($p['packs'] as $pk): ?>
                                            <span style="background: var(--color-body); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--color-border); font-size: 0.85rem;">
                                                <strong><?php echo e($pk['pack_size']); ?> عددی:</strong>
                                                <span class="color-primary">$<?php echo e($pk['price_digital']); ?></span> /
                                                <span class="color-bright">$<?php echo e($pk['price_physical']); ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if (empty($p['packs'])): ?>
                                            <span class="color-bright font-size-0-8">بدون پک</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
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
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <style>
        .pack-row-grid {
            display: grid;
            grid-template-columns: 120px 1fr 1fr auto;
            gap: 20px;
            align-items: flex-end;
            background: var(--color-surface);
            padding: 20px;
            border: 1px solid var(--color-border);
            border-radius: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        @media (max-width: 768px) {
            .pack-row-grid {
                grid-template-columns: 1fr 1fr;
            }
            .pack-row-grid > div:last-child {
                grid-column: span 2;
            }
        }
        @media (max-width: 480px) {
            .pack-row-grid {
                grid-template-columns: 1fr;
            }
            .pack-row-grid > div:last-child {
                grid-column: span 1;
            }
        }
        .pack-label {
            display: block;
            font-size: 13px;
            color: var(--color-title);
            margin-bottom: 8px;
            font-weight: 600;
        }
        .pack-input {
            width: 100%;
            height: 48px;
            border: 1px solid var(--color-border);
            border-radius: 10px;
            padding: 0 15px;
            background: var(--color-body);
            color: var(--color-text);
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .pack-input:focus {
            border-color: var(--color-primary);
            outline: none;
        }
        .remove-pack-btn {
            height: 48px;
            padding: 0 20px;
            color: #ef4444;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            background: #fff5f5;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .remove-pack-btn:hover {
            background: #ef4444;
            color: #fff;
        }
    </style>
<?php
    $countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
    $brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
    $editData = ['id' => '', 'brand' => 'apple', 'denomination' => '', 'country' => '', 'currency' => 'AED', 'packs' => []];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $fetched = $stmt->fetch();
        if ($fetched) {
            $editData = $fetched;
            $stmt = db()->prepare("SELECT * FROM product_packs WHERE product_id = ? ORDER BY pack_size ASC");
            $stmt->execute([$_GET['id']]);
            $editData['packs'] = $stmt->fetchAll();
        }
    }
?>
    <div class="admin-card max-w800">
        <h3 class="color-title mb-30"><?php echo $action === 'add' ? 'افزودن محصول جدید' : 'ویرایش محصول'; ?></h3>
        <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">

            <div class="d-flex-wrap gap-20 mb-20">
                <div class="input-item grow-1">
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
                                <img class="selected-img" src="../<?php echo e($selectedBrand['logo'] ?? ''); ?>" alt="" style="width: 28px; <?php echo empty($selectedBrand['logo']) ? 'display:none;' : ''; ?>">
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
                        </div>
                    </div>
                </div>

                <div class="input-item grow-1">
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
                                <img class="selected-img" src="../<?php echo e($selectedCountry['flag'] ?? ''); ?>" alt="" style="width: 28px; <?php echo empty($selectedCountry['flag']) ? 'display:none;' : ''; ?>">
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-item mb-30">
                <div class="input-label">مبلغ اعتبار (نام محصول)</div>
                <div class="input">
                    <input type="text" name="denomination" value="<?php echo e($editData['denomination']); ?>" required placeholder="مثلاً 100 AED, $50">
                </div>
            </div>

            <div class="mb-30">
                <div class="d-flex align-center just-between mb-20" style="background: var(--color-body); padding: 15px 20px; border-radius: 12px; border: 1px solid var(--color-border);">
                    <h4 class="color-title m-0 d-flex align-center gap-10">
                        <span class="icon icon-size-22 color-primary">📦</span>
                        پک‌های محصول
                    </h4>
                    <button type="button" class="btn-primary" id="add-pack-btn" style="height: 40px; border-radius: 8px; font-size: 14px;">افزودن پک جدید +</button>
                </div>

                <div style="margin-bottom: 20px; padding: 15px; background: #eff6ff; border-radius: 12px; border: 1px solid #bfdbfe; color: #1e40af; font-size: 0.9rem;">
                    <span class="icon">ℹ️</span> تمام قیمت‌ها باید به <strong>دلار آمریکا (USD)</strong> وارد شوند. سیستم به صورت خودکار معادل درهم امارات (AED) را با نرخ ثابت <strong>3.673</strong> در سایت نمایش می‌دهد.
                </div>

                <div id="packs-container">
                    <?php if (empty($editData['packs'])): ?>
                        <div class="pack-row-grid">
                            <div>
                                <label class="pack-label">تعداد (پک)</label>
                                <input type="number" class="pack-input" name="pack_sizes[]" value="1" required min="1">
                            </div>
                            <div>
                                <label class="pack-label">قیمت دیجیتال (دلار USD)</label>
                                <input type="number" step="0.01" class="pack-input" name="prices_digital[]" required>
                            </div>
                            <div>
                                <label class="pack-label">قیمت فیزیکی (دلار USD)</label>
                                <input type="number" step="0.01" class="pack-input" name="prices_physical[]" required>
                            </div>
                            <div>
                                <button type="button" class="remove-pack-btn">حذف</button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($editData['packs'] as $pk): ?>
                            <div class="pack-row-grid">
                                <div>
                                    <label class="pack-label">تعداد (پک)</label>
                                    <input type="number" class="pack-input" name="pack_sizes[]" value="<?php echo e($pk['pack_size']); ?>" required min="1">
                                </div>
                                <div>
                                    <label class="pack-label">قیمت دیجیتال (دلار USD)</label>
                                    <input type="number" step="0.01" class="pack-input" name="prices_digital[]" value="<?php echo e($pk['price_digital']); ?>" required>
                                </div>
                                <div>
                                    <label class="pack-label">قیمت فیزیکی (دلار USD)</label>
                                    <input type="number" step="0.01" class="pack-input" name="prices_physical[]" value="<?php echo e($pk['price_physical']); ?>" required>
                                </div>
                                <div>
                                    <button type="button" class="remove-pack-btn">حذف</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-10">
                <button type="submit" class="btn-primary radius-100">ذخیره محصول</button>
                <a href="products.php" class="btn radius-100" style="height: 48px;">انصراف</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('add-pack-btn').addEventListener('click', function() {
        const container = document.getElementById('packs-container');
        const newRow = document.createElement('div');
        newRow.className = 'pack-row-grid';
        newRow.innerHTML = `
            <div>
                <label class="pack-label">تعداد (پک)</label>
                <input type="number" class="pack-input" name="pack_sizes[]" value="1" required min="1">
            </div>
            <div>
                <label class="pack-label">قیمت دیجیتال (دلار USD)</label>
                <input type="number" step="0.01" class="pack-input" name="prices_digital[]" required>
            </div>
            <div>
                <label class="pack-label">قیمت فیزیکی (دلار USD)</label>
                <input type="number" step="0.01" class="pack-input" name="prices_physical[]" required>
            </div>
            <div>
                <button type="button" class="remove-pack-btn">حذف</button>
            </div>
        `;
        container.appendChild(newRow);
        attachRemoveEvent(newRow.querySelector('.remove-pack-btn'));
    });

    function attachRemoveEvent(btn) {
        btn.addEventListener('click', function() {
            const rows = document.querySelectorAll('.pack-row-grid');
            if (rows.length > 1) {
                this.closest('.pack-row-grid').remove();
            } else {
                alert('حداقل یک پک باید وجود داشته باشد.');
            }
        });
    }

    document.querySelectorAll('.remove-pack-btn').forEach(attachRemoveEvent);
    </script>
<?php endif; ?>

<?php require_once 'layout_footer.php'; ?>
