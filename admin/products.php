<?php
$pageTitle = 'Manage Products';
require_once 'layout_header.php';

$action = $_GET['action'] ?? 'list';
$msg = '';

// Handle Delete
if ($action === 'delete' && isset($_GET['id'])) {
    $stmt = db()->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $msg = 'Product deleted successfully!';
    $action = 'list';
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = clean($_POST['brand']);
    $denomination = clean($_POST['denomination']);
    $country = clean($_POST['country']);
    $price = (float)$_POST['price'];
    $currency = clean($_POST['currency']);

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $stmt = db()->prepare("UPDATE products SET brand=?, denomination=?, country=?, price=?, currency=? WHERE id=?");
        $stmt->execute([$brand, $denomination, $country, $price, $currency, $_POST['id']]);
        $msg = 'Product updated successfully!';
    } else {
        // Insert
        $stmt = db()->prepare("INSERT INTO products (brand, denomination, country, price, currency) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$brand, $denomination, $country, $price, $currency]);
        $msg = 'Product added successfully!';
    }
    $action = 'list';
}

?>

<div class="d-flex just-between align-center mb-30">
    <div>
        <?php if ($msg): ?>
            <div style="background: #dcfce7; color: #166534; padding: 10px 20px; border-radius: 10px; margin-bottom: 20px;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="products.php?action=add" class="btn-primary radius-100">Add New Product</a>
</div>

<?php if ($action === 'list'):
    $products = db()->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
    <div class="admin-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Brand</th>
                        <th>Denomination</th>
                        <th>Country</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?php echo strtoupper($p['brand']); ?></td>
                        <td><?php echo $p['denomination']; ?></td>
                        <td><?php echo strtoupper($p['country']); ?></td>
                        <td><?php echo $p['price'] . ' ' . $p['currency']; ?></td>
                        <td class="d-flex gap-10">
                            <a href="products.php?action=edit&id=<?php echo $p['id']; ?>" class="btn-sm" style="color: var(--color-primary);">Edit</a>
                            <a href="products.php?action=delete&id=<?php echo $p['id']; ?>" class="btn-sm" style="color: #ef4444;" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'):
    $editData = ['id' => '', 'brand' => 'apple', 'denomination' => '', 'country' => 'uae', 'price' => '', 'currency' => 'AED'];
    if ($action === 'edit' && isset($_GET['id'])) {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $editData = $stmt->fetch();
    }
?>
    <div class="admin-card max-w600">
        <h3 class="color-title mb-30"><?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h3>
        <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
            <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">

            <div class="input-item mb-20">
                <div class="input-label">Brand</div>
                <div class="input">
                    <input type="text" name="brand" value="<?php echo $editData['brand']; ?>" required placeholder="e.g. apple, psn, xbox">
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">Denomination</div>
                <div class="input">
                    <input type="text" name="denomination" value="<?php echo $editData['denomination']; ?>" required placeholder="e.g. 100 AED, $50">
                </div>
            </div>

            <div class="input-item mb-20">
                <div class="input-label">Country</div>
                <div class="input">
                    <input type="text" name="country" value="<?php echo $editData['country']; ?>" required placeholder="e.g. uae, usa, uk">
                </div>
            </div>

            <div class="d-flex-wrap gap-20 mb-30">
                <div class="input-item grow-1">
                    <div class="input-label">Price</div>
                    <div class="input">
                        <input type="number" step="0.01" name="price" value="<?php echo $editData['price']; ?>" required>
                    </div>
                </div>
                <div class="input-item grow-1">
                    <div class="input-label">Currency</div>
                    <div class="input">
                        <input type="text" name="currency" value="<?php echo $editData['currency']; ?>" required placeholder="e.g. AED, USD">
                    </div>
                </div>
            </div>

            <div class="d-flex gap-10">
                <button type="submit" class="btn-primary radius-100">Save Product</button>
                <a href="products.php" class="btn radius-100" style="height: 48px;">Cancel</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once 'layout_footer.php'; ?>
