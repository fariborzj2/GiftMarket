<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_card.php';
$pageTitle = __('acc_nav_browse');

$grouped   = getGroupedProducts();
$savedIds  = customerWatchlistIds();
$csrfToken = generateCsrfToken();
$lang = getLanguage();

$brands = db()->query("SELECT * FROM brands")->fetchAll();
$brandMap = [];
foreach ($brands as $b) $brandMap[$b['code']] = $b;

$countries = db()->query("SELECT * FROM countries")->fetchAll();
$countryNames = [];
foreach ($countries as $co) $countryNames[$co['code']] = __("country_{$co['code']}", $co['name']);

require __DIR__ . '/layout_header.php';
?>
<div class="mb-6">
    <h2 class="text-xl mb-1"><?php echo e(__('acc_browse_title')); ?></h2>
    <p class="text-slate-500 dark:text-slate-400 text-sm"><?php echo e(__('acc_browse_sub')); ?></p>
</div>

<?php
$hasProducts = false;
foreach ($grouped as $countries2) { foreach ($countries2 as $products) { if (!empty($products)) { $hasProducts = true; break 2; } } }
?>

<?php if (!$hasProducts): ?>
    <div class="acc-card p-10 text-center text-slate-400">
        <iconify-icon icon="lucide:package-open" class="text-5xl mb-3 opacity-30"></iconify-icon>
        <div class="text-sm"><?php echo e(__('no_products', 'No products available.')); ?></div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($grouped as $brandCode => $countriesArr): ?>
            <?php foreach ($countriesArr as $countryCode => $products): ?>
                <?php foreach ($products as $p): ?>
                    <?php acc_render_card($p, $brandMap[$brandCode] ?? null, $countryNames[$countryCode] ?? $countryCode, in_array((int)$p['id'], $savedIds, true)); ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

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
                if (data.ok) {
                    const saved = data.saved;
                    btn.dataset.saved = saved ? '1' : '0';
                    btn.classList.toggle('bg-rose-50', saved);
                    btn.classList.toggle('dark:bg-rose-900/20', saved);
                    btn.classList.toggle('text-rose-500', saved);
                    btn.classList.toggle('bg-slate-100', !saved);
                    btn.classList.toggle('dark:bg-slate-800', !saved);
                    btn.classList.toggle('text-slate-400', !saved);
                }
            } catch (e) {}
            btn.disabled = false;
        });
    });
</script>
<?php require __DIR__ . '/layout_footer.php'; ?>
