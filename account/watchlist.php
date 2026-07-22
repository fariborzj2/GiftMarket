<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_card.php';
$pageTitle = __('acc_nav_watchlist');

$csrfToken = generateCsrfToken();
$c = currentCustomer();
$lang = getLanguage();

// Fetch saved products with their packs, newest first
$sql = "SELECT p.*, w.created_at AS saved_at, pk.pack_size, pk.price_digital, pk.price_physical
        FROM customer_watchlist w
        JOIN products p ON p.id = w.product_id
        LEFT JOIN product_packs pk ON pk.product_id = p.id
        WHERE w.customer_id = ? AND p.status = 1
        ORDER BY w.created_at DESC, pk.pack_size ASC";
$stmt = db()->prepare($sql);
$stmt->execute([$c['id']]);
$rows = $stmt->fetchAll();

$products = [];
foreach ($rows as $row) {
    $id = $row['id'];
    if (!isset($products[$id])) {
        $products[$id] = $row;
        $products[$id]['packs'] = [];
    }
    if (!empty($row['pack_size'])) {
        $products[$id]['packs'][] = [
            'pack_size'      => $row['pack_size'],
            'price_digital'  => $row['price_digital'],
            'price_physical' => $row['price_physical'],
        ];
    }
}

$brandMap = [];
foreach (db()->query("SELECT * FROM brands")->fetchAll() as $b) $brandMap[$b['code']] = $b;
$countryNames = [];
foreach (db()->query("SELECT * FROM countries")->fetchAll() as $co) $countryNames[$co['code']] = __("country_{$co['code']}", $co['name']);

require __DIR__ . '/layout_header.php';
?>
<div class="mb-6">
    <h2 class="text-xl mb-1"><?php echo e(__('acc_watchlist_title')); ?></h2>
    <p class="text-slate-500 dark:text-slate-400 text-sm"><?php echo e(__('acc_watchlist_sub')); ?></p>
</div>

<div id="wl-empty" class="acc-card p-10 text-center text-slate-400 <?php echo empty($products) ? '' : 'hidden'; ?>">
    <iconify-icon icon="lucide:heart-off" class="text-5xl mb-3 opacity-30"></iconify-icon>
    <div class="text-sm mb-4"><?php echo e(__('acc_watchlist_empty')); ?></div>
    <a href="browse.php" class="btn-primary inline-flex"><iconify-icon icon="lucide:gift" class="text-lg"></iconify-icon><?php echo e(__('acc_watchlist_empty_cta')); ?></a>
</div>

<div id="wl-grid" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 <?php echo empty($products) ? 'hidden' : ''; ?>">
    <?php foreach ($products as $p): ?>
        <?php acc_render_card($p, $brandMap[$p['brand']] ?? null, $countryNames[$p['country']] ?? $p['country'], true); ?>
    <?php endforeach; ?>
</div>

<script>
    const WL_CSRF = '<?php echo $csrfToken; ?>';
    document.querySelectorAll('.wl-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                const res = await fetch('wishlist_toggle.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=' + encodeURIComponent(btn.dataset.id) + '&csrf_token=' + encodeURIComponent(WL_CSRF)
                });
                const data = await res.json();
                if (data.ok && !data.saved) {
                    const card = btn.closest('.acc-card');
                    if (card) card.remove();
                    if (data.count === 0) {
                        document.getElementById('wl-grid').classList.add('hidden');
                        document.getElementById('wl-empty').classList.remove('hidden');
                    }
                }
            } catch (e) {}
            btn.disabled = false;
        });
    });
</script>
<?php require __DIR__ . '/layout_footer.php'; ?>
