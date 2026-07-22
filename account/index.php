<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = __('acc_nav_overview');
require __DIR__ . '/layout_header.php';

$memberSince = !empty($customer['created_at']) ? date('Y/m/d', strtotime($customer['created_at'])) : '—';
?>

<div class="mb-8">
    <h2 class="text-2xl mb-1"><?php echo e(__('acc_welcome')); ?>، <?php echo e($customer['name']); ?> 👋</h2>
    <p class="text-slate-500 dark:text-slate-400 text-sm"><?php echo e(__('acc_overview_sub')); ?></p>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-8">
    <div class="acc-card p-6 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-900/20 text-rose-500 flex items-center justify-center">
            <iconify-icon icon="lucide:heart" class="text-3xl"></iconify-icon>
        </div>
        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400"><?php echo e(__('acc_stat_watchlist')); ?></div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $watchCount; ?></div>
        </div>
    </div>
    <div class="acc-card p-6 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
            <iconify-icon icon="lucide:message-square" class="text-3xl"></iconify-icon>
        </div>
        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400"><?php echo e(__('acc_stat_requests')); ?></div>
            <div class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $requestCount; ?></div>
        </div>
    </div>
    <div class="acc-card p-6 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center">
            <iconify-icon icon="lucide:calendar-check" class="text-3xl"></iconify-icon>
        </div>
        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400"><?php echo e(__('acc_stat_member_since')); ?></div>
            <div class="text-lg font-bold text-slate-900 dark:text-white" dir="ltr"><?php echo e($memberSince); ?></div>
        </div>
    </div>
</div>

<!-- Quick actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <a href="browse.php" class="acc-card p-5 hover:border-primary dark:hover:border-primary transition-colors group">
        <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-3 group-hover:bg-primary group-hover:text-white transition-colors">
            <iconify-icon icon="lucide:gift" class="text-2xl"></iconify-icon>
        </div>
        <div class="font-bold text-slate-900 dark:text-white mb-0.5"><?php echo e(__('acc_quick_browse')); ?></div>
        <div class="text-xs text-slate-500 dark:text-slate-400"><?php echo e(__('acc_quick_browse_sub')); ?></div>
    </a>
    <a href="requests.php" class="acc-card p-5 hover:border-primary dark:hover:border-primary transition-colors group">
        <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-3 group-hover:bg-primary group-hover:text-white transition-colors">
            <iconify-icon icon="lucide:message-square-plus" class="text-2xl"></iconify-icon>
        </div>
        <div class="font-bold text-slate-900 dark:text-white mb-0.5"><?php echo e(__('acc_quick_request')); ?></div>
        <div class="text-xs text-slate-500 dark:text-slate-400"><?php echo e(__('acc_quick_request_sub')); ?></div>
    </a>
    <a href="profile.php" class="acc-card p-5 hover:border-primary dark:hover:border-primary transition-colors group">
        <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-3 group-hover:bg-primary group-hover:text-white transition-colors">
            <iconify-icon icon="lucide:user-round-cog" class="text-2xl"></iconify-icon>
        </div>
        <div class="font-bold text-slate-900 dark:text-white mb-0.5"><?php echo e(__('acc_quick_profile')); ?></div>
        <div class="text-xs text-slate-500 dark:text-slate-400"><?php echo e(__('acc_quick_profile_sub')); ?></div>
    </a>
</div>

<?php require __DIR__ . '/layout_footer.php'; ?>
