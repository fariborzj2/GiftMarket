<?php
/**
 * Renders a single gift-card tile used by browse.php and watchlist.php.
 * @param array $p       product row incl. 'packs' array, brand, country, denomination...
 * @param array|null $brandInfo   brand row (name, logo)
 * @param string $countryName     localized country name
 * @param bool $saved             whether it's in the customer's watchlist
 */
function acc_render_card($p, $brandInfo, $countryName, $saved) {
    $logo = $brandInfo['logo'] ?? 'assets/images/brand/default.png';
    $brandName = __("brand_{$p['brand']}", $brandInfo['name'] ?? ucfirst($p['brand']));
    $symbol = !empty($p['display_symbol']) ? $p['display_symbol'] : getCurrencySymbol($p['currency']);

    $prices = array_filter(array_map(fn($pk) => (float)$pk['price_digital'], $p['packs'] ?? []), fn($v) => $v > 0);
    $minPrice = !empty($prices) ? min($prices) : 0;
    ?>
    <div class="acc-card p-4 relative flex flex-col">
        <button type="button" class="wl-btn absolute top-3 <?php echo (getLanguage() === 'ar') ? 'left-3' : 'right-3'; ?> w-9 h-9 rounded-lg flex items-center justify-center transition-colors <?php echo $saved ? 'bg-rose-50 dark:bg-rose-900/20 text-rose-500' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500'; ?>"
                data-id="<?php echo (int)$p['id']; ?>" data-saved="<?php echo $saved ? '1' : '0'; ?>" aria-label="watchlist">
            <iconify-icon icon="lucide:heart" class="text-xl"></iconify-icon>
        </button>

        <div class="w-14 h-14 rounded-xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-2 flex items-center justify-center mb-3 overflow-hidden">
            <img src="../<?php echo e($logo); ?>" alt="<?php echo e($brandName); ?>" class="max-w-full max-h-full object-contain">
        </div>

        <div class="font-bold text-slate-900 dark:text-white leading-tight"><?php echo e($brandName); ?></div>
        <div class="text-xs text-slate-400 mb-3"><?php echo e($countryName); ?></div>

        <div class="mt-auto flex items-end justify-between gap-2">
            <div>
                <div class="text-[11px] text-slate-400"><?php echo e($p['denomination']); ?> <?php echo e($symbol); ?></div>
                <?php if ($minPrice > 0): ?>
                    <div class="text-sm font-bold text-slate-900 dark:text-white"><span class="text-[11px] font-normal text-slate-400"><?php echo e(__('acc_from')); ?></span> $<?php echo e(number_format($minPrice, 2)); ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($p['packs']) && count($p['packs']) > 1): ?>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-primary/10 text-primary"><?php echo count($p['packs']); ?> <?php echo e(__('pack_size')); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
