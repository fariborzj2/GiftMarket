<?php
$pageTitle = 'مدیریت محصولات';
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
        $stmt = db()->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $msg = 'محصول با موفقیت حذف شد!';
        header("Location: products.php?msg=" . urlencode($msg));
        exit;
    }

    // Handle Toggle Status
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['id'])) {
        $stmt = db()->prepare("UPDATE products SET status = 1 - status WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $msg = 'وضعیت محصول با موفقیت تغییر کرد!';
        header("Location: products.php?msg=" . urlencode($msg));
        exit;
    }

    // Handle Add/Edit
    if (isset($_POST['brand'])) {
    $db = db();
    $db->beginTransaction();
    try {
        $brand = clean($_POST['brand']);
        $denomination = clean($_POST['denomination']);
        $country = clean($_POST['country']);
        $status = isset($_POST['status']) ? 1 : 0;
        $pack_sizes = $_POST['pack_sizes'] ?? [];
        $prices_digital = $_POST['prices_digital'] ?? [];
        $prices_physical = $_POST['prices_physical'] ?? [];

        $stmt = $db->prepare("SELECT currency FROM countries WHERE code = ?");
        $stmt->execute([$country]);
        $currency = $stmt->fetchColumn() ?: 'AED';

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $productId = $_POST['id'];
            $stmt = $db->prepare("UPDATE products SET brand=?, denomination=?, country=?, currency=?, status=? WHERE id=?");
            $stmt->execute([$brand, $denomination, $country, $currency, $status, $productId]);
        } else {
            $stmt = $db->prepare("INSERT INTO products (brand, denomination, country, currency, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$brand, $denomination, $country, $currency, $status]);
            $productId = $db->lastInsertId();
        }

        $stmt = $db->prepare("DELETE FROM product_packs WHERE product_id = ?");
        $stmt->execute([$productId]);

        $stmt = $db->prepare("INSERT INTO product_packs (product_id, pack_size, price_digital, price_physical) VALUES (?, ?, ?, ?)");
        foreach ($pack_sizes as $i => $size) {
            $stmt->execute([$productId, (int)$size, (float)$prices_digital[$i], (float)$prices_physical[$i]]);
        }

        $db->commit();
        $msg = 'محصول با موفقیت ذخیره شد!';
        header("Location: products.php?msg=" . urlencode($msg));
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $msg = 'خطا در ذخیره محصول: ' . $e->getMessage();
    }
    }
}
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <?php
        $displayMsg = $msg ?: ($_GET['msg'] ?? '');
        if ($displayMsg): ?>
            <div class="<?php echo (strpos($displayMsg, 'خطا') === false) ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/30' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/30'; ?> px-6 py-3 rounded-xl text-sm flex items-center gap-2">
                <iconify-icon icon="<?php echo (strpos($displayMsg, 'خطا') === false) ? 'solar:check-circle-bold-duotone' : 'solar:danger-bold-duotone'; ?>" class="text-xl"></iconify-icon>
                <?php echo e($displayMsg); ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="products.php?action=add" class="btn-primary ">
        <iconify-icon icon="solar:add-circle-bold-duotone" class="text-xl"></iconify-icon>
        <span>افزودن محصول</span>
    </a>
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
        $query .= " AND p.id IN (SELECT product_id FROM product_packs WHERE pack_size = ?)";
        $params[] = (int)$f_pack_size;
    }

    $query .= " ORDER BY brands.sort_order ASC, brand_name ASC, countries.sort_order ASC, country_name ASC, p.denomination ASC, pk.pack_size ASC";
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

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

    <!-- Filters -->
    <div class="admin-card mb-8 !p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
            <div class="space-y-1.5 lg:col-span-1">
                <label class="text-xs font-bold text-slate-400 uppercase ms-1 flex items-center gap-1">
                    <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
                    جستجو
                </label>
                <input type="text" name="search" value="<?php echo e($search); ?>"
                       class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:border-primary outline-none text-sm"
                       placeholder="مبلغ اعتبار...">
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-400 uppercase ms-1 flex items-center gap-1">
                    <iconify-icon icon="solar:tag-bold-duotone"></iconify-icon>
                    برند
                </label>
                <select name="brand" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:border-primary outline-none text-sm">
                    <option value="">همه برندها</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?php echo e($b['code']); ?>" <?php echo $f_brand == $b['code'] ? 'selected' : ''; ?>><?php echo e($b['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-400 uppercase ms-1 flex items-center gap-1">
                    <iconify-icon icon="solar:globus-bold-duotone"></iconify-icon>
                    کشور
                </label>
                <select name="country" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:border-primary outline-none text-sm">
                    <option value="">همه کشورها</option>
                    <?php foreach ($countries as $c): ?>
                        <option value="<?php echo e($c['code']); ?>" <?php echo $f_country == $c['code'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-400 uppercase ms-1 flex items-center gap-1">
                    <iconify-icon icon="solar:box-bold-duotone"></iconify-icon>
                    سایز پک
                </label>
                <select name="pack_size" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 focus:border-primary outline-none text-sm">
                    <option value="">همه سایزها</option>
                    <?php foreach ($pack_sizes as $size): ?>
                        <option value="<?php echo e($size); ?>" <?php echo $f_pack_size == $size ? 'selected' : ''; ?>>Pack Of <?php echo e($size); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary !px-4 !py-2 text-sm flex-1">اعمال</button>
                <a href="products.php" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-sm">حذف</a>
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
        <div class="admin-card text-center py-20 text-slate-400">
            <iconify-icon icon="solar:magnifer-bold-duotone" class="text-6xl mb-4 opacity-20"></iconify-icon>
            <div>هیچ محصولی با این مشخصات یافت نشد.</div>
        </div>
    <?php endif; ?>

    <?php foreach ($grouped as $brandName => $brandData): ?>
    <div class="mb-12">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-1.5 flex items-center justify-center shrink-0 shadow-sm">
                <?php if ($brandData['logo']): ?>
                    <img src="../<?php echo e($brandData['logo']); ?>" alt="" class="max-w-full max-h-full object-contain">
                <?php else: ?>
                    <div class="w-2 h-2 bg-primary rounded-xl"></div>
                <?php endif; ?>
            </div>
            <h2 class="text-xl md:text-2xl font-black text-slate-900 dark:text-white"><?php echo e($brandName); ?></h2>
            <div class="h-px flex-1 bg-gradient-to-l from-transparent via-slate-200 dark:via-slate-800 to-transparent"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php foreach ($brandData['countries'] as $countryName => $countryData): ?>
            <div class="flex flex-col">
                <div class="flex items-center justify-between mb-4 px-2">
                    <div class="flex items-center gap-2">
                        <?php if ($countryData['flag']): ?>
                            <img src="../<?php echo e($countryData['flag']); ?>" alt="" class="w-4 h-4 rounded shadow-sm">
                        <?php else: ?>
                            <span class="text-lg">🌍</span>
                        <?php endif; ?>
                        <h3 class="text-sm font-bold text-slate-600 dark:text-slate-400"><?php echo e($countryName); ?></h3>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500">
                        <?php echo count($countryData['products']); ?> محصول
                    </span>
                </div>

                <div class="admin-card !p-0 overflow-hidden border-primary/20">
                    <div class="overflow-x-auto">
                        <table class="w-full text-right border-collapse text-sm">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold border-b border-slate-100 dark:border-slate-800">
                                    <th class="px-6 py-3 font-bold">محصول (اعتبار)</th>
                                    <th class="px-6 py-3 font-bold text-center">پک‌های موجود</th>
                                    <th class="px-6 py-3 font-bold text-center">وضعیت</th>
                                    <th class="px-6 py-3 font-bold w-24">عملیات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <?php foreach ($countryData['products'] as $p): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors <?php echo $p['status'] == 0 ? 'opacity-60 grayscale-[0.5]' : ''; ?>">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900 dark:text-white"><?php echo e($p['denomination']); ?></div>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?php echo e($p['currency']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if (empty($p['packs'])): ?>
                                            <span class="text-xs text-slate-400 italic">بدون پک</span>
                                        <?php else: ?>
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs">
                                                <iconify-icon icon="solar:box-bold-duotone" class="text-lg opacity-50"></iconify-icon>
                                                <span><?php echo count($p['packs']); ?> پک</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 rounded-xl text-[10px] font-bold transition-all <?php echo $p['status'] == 1 ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'; ?>">
                                                <iconify-icon icon="<?php echo $p['status'] == 1 ? 'solar:check-circle-bold' : 'solar:close-circle-bold'; ?>"></iconify-icon>
                                                <?php echo $p['status'] == 1 ? 'فعال' : 'غیرفعال'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-1">
                                            <a href="products.php?action=edit&id=<?php echo e($p['id']); ?>" class="p-1.5 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="ویرایش">
                                                <iconify-icon icon="solar:pen-new-square-bold-duotone" class="text-xl"></iconify-icon>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('حذف محصول؟')">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="حذف">
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
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<?php
    $countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
    $brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
    $editData = ['id' => '', 'brand' => 'apple', 'denomination' => '', 'country' => '', 'currency' => 'AED', 'status' => 1, 'packs' => []];
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
    <div class="admin-card max-w-4xl mx-auto">
        <h3 class="text-xl mb-8 flex items-center gap-2">
            <iconify-icon icon="<?php echo $action === 'add' ? 'solar:add-circle-bold-duotone' : 'solar:pen-new-square-bold-duotone'; ?>" class="text-primary text-2xl"></iconify-icon>
            <span><?php echo $action === 'add' ? 'افزودن محصول' : 'ویرایش محصول'; ?></span>
        </h3>

        <form method="POST" class="space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="id" value="<?php echo e($editData['id']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Custom Dropdown for Brand -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">برند</label>
                    <div class="drop-down relative">
                        <?php
                        $selectedBrand = null;
                        foreach ($brands as $b) {
                            if ($b['code'] == $editData['brand']) { $selectedBrand = $b; break; }
                        }
                        ?>
                        <button type="button" class="drop-down-btn w-full flex items-center gap-3 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-right transition-all">
                            <div class="w-6 h-6 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                <img class="selected-img max-w-full max-h-full object-contain" src="../<?php echo e($selectedBrand['logo'] ?? ''); ?>" alt="" style="<?php echo empty($selectedBrand['logo']) ? 'display:none;' : ''; ?>">
                            </div>
                            <span class="selected-text flex-1 text-sm font-medium"><?php echo e($selectedBrand['name'] ?? 'انتخاب برند'); ?></span>
                            <iconify-icon icon="solar:alt-arrow-down-bold-duotone" class="text-slate-400"></iconify-icon>
                        </button>
                        <input type="hidden" class="selected-option" name="brand" value="<?php echo e($editData['brand']); ?>" required>
                        <div class="drop-down-list hidden absolute z-50 w-full mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                            <?php foreach ($brands as $b): ?>
                                <div class="drop-option flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition-colors <?php echo $editData['brand'] == $b['code'] ? 'bg-primary/5 text-primary' : ''; ?>" data-option="<?php echo e($b['code']); ?>">
                                    <div class="w-6 h-6 rounded-lg bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden shrink-0">
                                        <img src="../<?php echo e($b['logo']); ?>" alt="" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <span class="text-sm font-medium"><?php echo e($b['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Custom Dropdown for Country -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">کشور</label>
                    <div class="drop-down relative">
                        <?php
                        $selectedCountry = null;
                        foreach ($countries as $c) {
                            if ($c['code'] == $editData['country']) { $selectedCountry = $c; break; }
                        }
                        ?>
                        <button type="button" class="drop-down-btn w-full flex items-center gap-3 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-right transition-all">
                            <div class="w-6 h-6 rounded shadow-sm overflow-hidden shrink-0">
                                <img class="selected-img w-full h-full object-cover" src="../<?php echo e($selectedCountry['flag'] ?? ''); ?>" alt="" style="<?php echo empty($selectedCountry['flag']) ? 'display:none;' : ''; ?>">
                            </div>
                            <span class="selected-text flex-1 text-sm font-medium"><?php echo e($selectedCountry['name'] ?? 'انتخاب کشور'); ?></span>
                            <iconify-icon icon="solar:alt-arrow-down-bold-duotone" class="text-slate-400"></iconify-icon>
                        </button>
                        <input type="hidden" class="selected-option" name="country" value="<?php echo e($editData['country']); ?>" required>
                        <div class="drop-down-list hidden absolute z-50 w-full mt-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                            <?php foreach ($countries as $c): ?>
                                <div class="drop-option flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer transition-colors <?php echo $editData['country'] == $c['code'] ? 'bg-primary/5 text-primary' : ''; ?>" data-option="<?php echo e($c['code']); ?>">
                                    <div class="w-6 h-6 rounded shadow-sm overflow-hidden shrink-0">
                                        <img src="../<?php echo e($c['flag']); ?>" alt="" class="w-full h-full object-cover">
                                    </div>
                                    <span class="text-sm font-medium"><?php echo e($c['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                <div class="space-y-2 md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">مبلغ اعتبار (نام محصول)</label>
                    <input type="text" name="denomination" value="<?php echo e($editData['denomination']); ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all font-bold"
                           placeholder="مثلاً 100 AED, $50">
                </div>
                <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="status" class="sr-only peer" <?php echo $editData['status'] == 1 ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-xl peer dark:bg-slate-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-xl after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                    </label>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">محصول فعال باشد</span>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <iconify-icon icon="solar:box-bold-duotone" class="text-xl"></iconify-icon>
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white">پک‌های محصول</h4>
                    </div>
                    <button type="button" id="add-pack-btn" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-md shadow-primary/20">
                        افزودن پک +
                    </button>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/20 rounded-xl p-4 flex gap-3 text-sm text-blue-700 dark:text-blue-300">
                    <iconify-icon icon="solar:info-circle-bold-duotone" class="text-xl shrink-0"></iconify-icon>
                    <p>تمام قیمت‌ها باید به <strong>دلار آمریکا (USD)</strong> وارد شوند. سیستم به صورت خودکار معادل درهم امارات (AED) را با نرخ <strong><?php echo e(getSetting('usd_to_aed', '3.673')); ?></strong> در سایت نمایش می‌دهد.</p>
                </div>

                <div id="packs-container" class="space-y-3">
                    <?php
                    $packsToShow = !empty($editData['packs']) ? $editData['packs'] : [['pack_size' => 1, 'price_digital' => '', 'price_physical' => '']];
                    foreach ($packsToShow as $pk):
                    ?>
                        <div class="pack-row grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative group">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">تعداد (پک)</label>
                                <input type="number" name="pack_sizes[]" value="<?php echo e($pk['pack_size']); ?>" required min="1"
                                       class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:border-primary outline-none transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">قیمت دیجیتال ($)</label>
                                <input type="number" step="0.01" name="prices_digital[]" value="<?php echo e($pk['price_digital']); ?>" required
                                       class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:border-primary outline-none transition-all">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">قیمت فیزیکی ($)</label>
                                <input type="number" step="0.01" name="prices_physical[]" value="<?php echo e($pk['price_physical']); ?>" required
                                       class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:border-primary outline-none transition-all">
                            </div>
                            <div class="flex items-end">
                                <button type="button" class="remove-pack-btn w-full py-4 rounded-xl border border-red-100 dark:border-red-900/30 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 text-xs font-bold transition-all">
                                    حذف پک
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                <button type="submit" class="btn-primary flex-1 py-3">ذخیره محصول</button>
                <a href="products.php" class="px-8 py-3 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-bold">انصراف</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('add-pack-btn').addEventListener('click', function() {
        const container = document.getElementById('packs-container');
        const newRow = document.createElement('div');
        newRow.className = 'pack-row grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm relative group';
        newRow.innerHTML = `
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">تعداد (پک)</label>
                <input type="number" name="pack_sizes[]" value="1" required min="1"
                       class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">قیمت دیجیتال ($)</label>
                <input type="number" step="0.01" name="prices_digital[]" required
                       class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:border-primary outline-none transition-all">
            </div>
            <div class="space-y-1.5">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">قیمت فیزیکی ($)</label>
                <input type="number" step="0.01" name="prices_physical[]" required
                       class="w-full px-3 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-sm focus:border-primary outline-none transition-all">
            </div>
            <div class="flex items-end">
                <button type="button" class="remove-pack-btn w-full py-4 rounded-xl border border-red-100 dark:border-red-900/30 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 text-xs font-bold transition-all">
                    حذف پک
                </button>
            </div>
        `;
        container.appendChild(newRow);
        attachRemoveEvent(newRow.querySelector('.remove-pack-btn'));
    });

    function attachRemoveEvent(btn) {
        btn.addEventListener('click', function() {
            const rows = document.querySelectorAll('.pack-row');
            if (rows.length > 1) {
                this.closest('.pack-row').remove();
            } else {
                alert('حداقل یک پک باید وجود داشته باشد.');
            }
        });
    }

    document.querySelectorAll('.remove-pack-btn').forEach(attachRemoveEvent);
    </script>
<?php endif; ?>

<?php require_once 'layout_footer.php'; ?>
