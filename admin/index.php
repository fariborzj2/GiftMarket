<?php
$pageTitle = 'Dashboard';
require_once 'layout_header.php';

// Fetch some stats
$totalProducts = db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
?>

<div class="d-flex-wrap gap-20">
    <div class="admin-card basis200 grow-1">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div>
                <div class="color-text font-size-0-9">Total Products</div>
                <div class="color-title font-size-1-5"><?php echo $totalProducts; ?></div>
            </div>
        </div>
    </div>
    <div class="admin-card basis200 grow-1">
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div>
                <div class="color-text font-size-0-9">Active Orders</div>
                <div class="color-title font-size-1-5">0</div>
            </div>
        </div>
    </div>
    <div class="admin-card basis200 grow-1">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div>
                <div class="color-text font-size-0-9">Customers</div>
                <div class="color-title font-size-1-5">0</div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <h3 class="color-title mb-20">Recent Activity</h3>
    <p class="color-text">Welcome to the UAE.GIFT admin panel. System is ready for management.</p>
</div>

<?php require_once 'layout_footer.php'; ?>
