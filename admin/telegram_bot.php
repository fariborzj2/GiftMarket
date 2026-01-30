<?php
$pageTitle = 'مدیریت ربات تلگرام';
require_once 'layout_header.php';
require_once __DIR__ . '/../system/plugins/telegram-bot/TelegramBot.php';

$bot = new TelegramBot();
$msg = '';
$tab = $_GET['tab'] ?? 'settings';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        updateSetting('telegram_bot_enabled', isset($_POST['enabled']) ? '1' : '0');
        updateSetting('telegram_bot_token', clean($_POST['token']));
        updateSetting('telegram_bot_username', clean($_POST['username']));
        updateSetting('telegram_publish_time', clean($_POST['publish_time']));
        updateSetting('telegram_message_template', $_POST['template']);
        updateSetting('telegram_use_emojis', isset($_POST['use_emojis']) ? '1' : '0');
        updateSetting('telegram_price_type', clean($_POST['price_type']));
        $msg = 'تنظیمات با موفقیت ذخیره شد!';
    }

    if (isset($_POST['add_channel'])) {
        $channelId = clean($_POST['channel_id']);
        $channelName = clean($_POST['channel_name']);
        if ($channelId) {
            $stmt = db()->prepare("INSERT IGNORE INTO telegram_channels (channel_id, name) VALUES (?, ?)");
            $stmt->execute([$channelId, $channelName]);
            $msg = 'کانال با موفقیت اضافه شد!';
            $tab = 'channels';
        }
    }

    if (isset($_POST['publish_now'])) {
        if ($bot->publishPrices(true)) {
            $msg = 'انتشار با موفقیت انجام شد!';
        } else {
            $msg = 'خطا در انتشار. لطفاً لاگ‌ها را بررسی کنید.';
        }
    }

    if (isset($_POST['save_config'])) {
        $enabledConfigs = $_POST['config'] ?? [];
        // Reset all to disabled first for the submitted brands/countries or just handle incrementally
        // Better: handle via checkboxes in the form
        db()->exec("UPDATE telegram_config SET enabled = 0");
        $stmt = db()->prepare("INSERT INTO telegram_config (brand_code, country_code, enabled) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE enabled = 1");
        foreach ($enabledConfigs as $cfg) {
            list($b, $c) = explode('|', $cfg);
            $stmt->execute([$b, $c]);
        }
        $msg = 'پیکربندی با موفقیت ذخیره شد!';
        $tab = 'config';
    }
}

if (isset($_GET['delete_channel'])) {
    $stmt = db()->prepare("DELETE FROM telegram_channels WHERE id = ?");
    $stmt->execute([$_GET['delete_channel']]);
    $msg = 'کانال حذف شد!';
    $tab = 'channels';
}

// Fetch Data
$st_enabled = getSetting('telegram_bot_enabled', '0');
$st_token = getSetting('telegram_bot_token', '');
$st_username = getSetting('telegram_bot_username', '');
$st_publish_time = getSetting('telegram_publish_time', '09:00');
$st_template = getSetting('telegram_message_template', "*{brand}* {country} ({denomination})\n{type}: {price}{currency} → {converted_price} {target_currency}\nLast update: {last_update}");
$st_use_emojis = getSetting('telegram_use_emojis', '1');
$st_price_type = getSetting('telegram_price_type', 'both');

$channels = db()->query("SELECT * FROM telegram_channels ORDER BY created_at DESC")->fetchAll();
$logs = db()->query("SELECT * FROM telegram_logs ORDER BY created_at DESC LIMIT 50")->fetchAll();

$brands = db()->query("SELECT * FROM brands ORDER BY sort_order ASC, name ASC")->fetchAll();
$countries = db()->query("SELECT * FROM countries ORDER BY sort_order ASC, name ASC")->fetchAll();
$configs = db()->query("SELECT * FROM telegram_config WHERE enabled = 1")->fetchAll();

$configMap = [];
foreach ($configs as $c) {
    $configMap[$c['brand_code']][$c['country_code']] = true;
}
?>

<div class="d-flex just-between align-center mb-30">
    <div>
        <?php if ($msg): ?>
            <div style="background: <?php echo (strpos($msg, 'خطا') !== false || strpos($msg, 'حذف') !== false) ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo (strpos($msg, 'خطا') !== false || strpos($msg, 'حذف') !== false) ? '#991b1b' : '#166534'; ?>; padding: 10px 20px; border-radius: 10px; margin-bottom: 20px;">
                <?php echo e($msg); ?>
            </div>
        <?php endif; ?>
    </div>
    <form method="POST">
        <button type="submit" name="publish_now" class="btn-primary radius-100" onclick="return confirm('آیا از انتشار دستی قیمت‌ها اطمینان دارید؟')">انتشار همزمان (Publish Now) 🚀</button>
    </form>
</div>

<div class="admin-card mb-30" style="padding: 0;">
    <div class="d-flex border-bottom" style="background: var(--color-body); border-radius: 15px 15px 0 0;">
        <a href="?tab=settings" class="p-20 color-title font-bold <?php echo $tab === 'settings' ? 'border-bottom-primary' : ''; ?>" style="text-decoration: none;">تنظیمات کلی</a>
        <a href="?tab=channels" class="p-20 color-title font-bold <?php echo $tab === 'channels' ? 'border-bottom-primary' : ''; ?>" style="text-decoration: none;">مدیریت کانال‌ها</a>
        <a href="?tab=config" class="p-20 color-title font-bold <?php echo $tab === 'config' ? 'border-bottom-primary' : ''; ?>" style="text-decoration: none;">پیکربندی برند/کشور</a>
        <a href="?tab=logs" class="p-20 color-title font-bold <?php echo $tab === 'logs' ? 'border-bottom-primary' : ''; ?>" style="text-decoration: none;">لاگ‌ها</a>
    </div>

    <div class="p-30">
        <?php if ($tab === 'settings'): ?>
            <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
                <div class="mb-30">
                    <label class="d-flex align-center gap-10 pointer">
                        <input type="checkbox" name="enabled" value="1" <?php echo $st_enabled === '1' ? 'checked' : ''; ?>>
                        <span class="font-bold color-title">فعالسازی ربات تلگرام</span>
                    </label>
                </div>

                <div class="d-flex-wrap gap-20 mb-20">
                    <div class="input-item grow-1">
                        <div class="input-label">توکن ربات (Bot Token)</div>
                        <div class="input">
                            <input type="text" name="token" value="<?php echo e($st_token); ?>" placeholder="123456789:ABCDE...">
                        </div>
                    </div>
                    <div class="input-item grow-1">
                        <div class="input-label">نام کاربری ربات (Bot Username)</div>
                        <div class="input">
                            <input type="text" name="username" value="<?php echo e($st_username); ?>" placeholder="@my_price_bot">
                        </div>
                    </div>
                </div>

                <div class="d-flex-wrap gap-20 mb-20">
                    <div class="input-item grow-1">
                        <div class="input-label">زمان انتشار خودکار (روزانه)</div>
                        <div class="input">
                            <input type="time" name="publish_time" value="<?php echo e($st_publish_time); ?>">
                        </div>
                    </div>
                    <div class="input-item grow-1">
                        <div class="input-label">نوع قیمت برای انتشار</div>
                        <select name="price_type" class="input" style="height: 54px; border: 1px solid var(--color-border); border-radius: 10px; padding: 0 15px; width: 100%; background: var(--color-body); color: var(--color-text);">
                            <option value="both" <?php echo $st_price_type === 'both' ? 'selected' : ''; ?>>هر دو (دیجیتال و فیزیکی)</option>
                            <option value="digital" <?php echo $st_price_type === 'digital' ? 'selected' : ''; ?>>فقط دیجیتال</option>
                            <option value="physical" <?php echo $st_price_type === 'physical' ? 'selected' : ''; ?>>فقط فیزیکی</option>
                        </select>
                    </div>
                </div>

                <div class="input-item mb-20">
                    <label class="d-flex align-center gap-10 pointer">
                        <input type="checkbox" name="use_emojis" value="1" <?php echo $st_use_emojis === '1' ? 'checked' : ''; ?>>
                        <span>استفاده از ایموجی پرچم کشورها</span>
                    </label>
                </div>

                <div class="input-item mb-20">
                    <div class="input-label">قالب پیام (Message Template)</div>
                    <textarea name="template" rows="6" style="font-family: monospace; direction: ltr;"><?php echo e($st_template); ?></textarea>
                    <div class="font-size-0-8 color-bright mt-10">
                        <span class="color-primary">⚠️ در حال حاضر از قالب دسته‌بندی شده پیش‌فرض استفاده می‌شود. این تنظیمات در نسخه‌های بعدی اعمال خواهد شد.</span><br>
                        متغیرهای مجاز: {brand}, {country}, {denomination}, {price}, {currency}, {converted_price}, {target_currency}, {type}, {last_update}
                    </div>
                </div>

                <button type="submit" name="save_settings" class="btn-primary radius-100">ذخیره تنظیمات</button>
            </form>

        <?php elseif ($tab === 'channels'): ?>
            <form method="POST" class="d-flex-wrap gap-15 align-end mb-30">
                <div class="input-item grow-1" style="min-width: 250px;">
                    <div class="input-label">Channel ID</div>
                    <div class="input">
                        <input type="text" name="channel_id" placeholder="مثلاً 100123456789- یا @mychannel" required dir="ltr">
                    </div>
                </div>
                <div class="input-item grow-1" style="min-width: 200px;">
                    <div class="input-label">نام کانال</div>
                    <div class="input">
                        <input type="text" name="channel_name" placeholder="نام نمایشی برای مدیریت">
                    </div>
                </div>
                <button type="submit" name="add_channel" class="btn-primary radius-100" style="height: 54px; white-space: nowrap;">افزودن کانال +</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Channel ID</th>
                            <th>نام کانال</th>
                            <th>تاریخ افزودن</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($channels as $c): ?>
                        <tr>
                            <td dir="ltr"><?php echo e($c['channel_id']); ?></td>
                            <td><?php echo e($c['name']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($c['created_at'])); ?></td>
                            <td>
                                <a href="?tab=channels&delete_channel=<?php echo $c['id']; ?>" class="color-danger" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($channels)): ?>
                        <tr><td colspan="4" class="text-center">هیچ کانالی ثبت نشده است.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($tab === 'config'): ?>
            <form method="POST">
                <div style="max-height: 600px; overflow-y: auto; border: 1px solid var(--color-border); border-radius: 10px; padding: 20px;" class="mb-30">
                    <?php foreach ($brands as $brand): ?>
                        <div class="mb-20">
                            <h4 class="color-primary border-bottom pb-5 mb-10 d-flex align-center gap-10">
                                <?php if($brand['logo']): ?><img src="../<?php echo $brand['logo']; ?>" style="width:20px;"><?php endif; ?>
                                <?php echo e($brand['name']); ?>
                            </h4>
                            <div class="d-flex-wrap gap-15">
                                <?php foreach ($countries as $country): ?>
                                    <label class="d-flex align-center gap-5 pointer" style="background: var(--color-body); padding: 5px 10px; border-radius: 5px; border: 1px solid var(--color-border);">
                                        <input type="checkbox" name="config[]" value="<?php echo $brand['code'] . '|' . $country['code']; ?>" <?php echo isset($configMap[$brand['code']][$country['code']]) ? 'checked' : ''; ?>>
                                        <span class="font-size-0-9"><?php echo e($country['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="save_config" class="btn-primary radius-100">ذخیره پیکربندی</button>
            </form>

        <?php elseif ($tab === 'logs'): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>زمان</th>
                            <th>وضعیت</th>
                            <th>پیام</th>
                            <th>پاسخ سرور</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                            <td>
                                <span style="background: <?php echo $log['status'] === 'success' ? '#dcfce7' : ($log['status'] === 'error' ? '#fee2e2' : '#fef9c3'); ?>; color: <?php echo $log['status'] === 'success' ? '#166534' : ($log['status'] === 'error' ? '#991b1b' : '#854d0e'); ?>; padding: 3px 8px; border-radius: 5px; font-size: 0.8rem;">
                                    <?php echo e($log['status']); ?>
                                </span>
                            </td>
                            <td><?php echo e($log['message']); ?></td>
                            <td><small dir="ltr"><?php echo e(mb_strimwidth($log['response'], 0, 100, '...')); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                        <tr><td colspan="4" class="text-center">هیچ لاگی یافت نشد.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.border-bottom-primary { border-bottom: 3px solid var(--color-primary); color: var(--color-primary) !important; }
.color-danger { color: #ef4444; }
.p-20 { padding: 20px; }
.p-30 { padding: 30px; }
.border-bottom { border-bottom: 1px solid var(--color-border); }
</style>

<?php require_once 'layout_footer.php'; ?>
