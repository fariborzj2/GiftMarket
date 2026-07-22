<?php
ob_start();
require_once __DIR__ . '/../system/includes/functions.php';
require_once __DIR__ . '/../system/plugins/telegram-bot/TelegramBot.php';

$bot = new TelegramBot();
$msg = '';
$tab = $_GET['tab'] ?? 'settings';
$csrfToken = generateCsrfToken();

// Handle Actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    if (isset($_POST['save_settings'])) {
        updateSetting('telegram_bot_enabled', isset($_POST['enabled']) ? '1' : '0');
        updateSetting('telegram_bot_token', clean($_POST['token']));
        updateSetting('telegram_bot_username', clean($_POST['username']));
        updateSetting('telegram_publish_time', clean($_POST['publish_time']));
        updateSetting('telegram_message_template', $_POST['template']);
        updateSetting('telegram_use_emojis', isset($_POST['use_emojis']) ? '1' : '0');
        updateSetting('telegram_price_type', clean($_POST['price_type']));
        updateSetting('telegram_currency_symbols', clean($_POST['currency_symbols']));

        $msg = 'تنظیمات با موفقیت ذخیره شد!';
        header("Location: ?tab=settings&msg=" . urlencode($msg));
        exit;
    }

    if (isset($_POST['reset_templates'])) {
        $defaultTemplate = "{emoji} {brand} Gift Card – {currency} {denomination}\n\n" .
                          "Digital\n\n" .
                          "[DIGITAL_PACKS]\n" .
                          "• Pack {size} → {currency}{price}\n" .
                          "[/DIGITAL_PACKS]\n\n" .
                          "Physical\n\n" .
                          "[PHYSICAL_PACKS]\n" .
                          "• Pack {size} → {currency}{price}\n" .
                          "[/PHYSICAL_PACKS]\n\n" .
                          "🕒 Last update: {last_update_time}";

        updateSetting('telegram_message_template', $defaultTemplate);
        updateSetting('telegram_currency_symbols', '$, USD, AED, EUR, GBP, TL');
        $msg = 'قالب پیام به حالت پیش‌فرض بازنشانی شد.';
        // Reload settings safely
        header("Location: ?tab=settings&msg=" . urlencode($msg));
        exit;
    }

    if (isset($_POST['add_channel'])) {
        $channelId = clean($_POST['channel_id']);
        $channelName = clean($_POST['channel_name']);
        if ($channelId) {
            $stmt = db()->prepare("INSERT IGNORE INTO telegram_channels (channel_id, name) VALUES (?, ?)");
            $stmt->execute([$channelId, $channelName]);
            $msg = 'کانال با موفقیت اضافه شد!';
            header("Location: ?tab=channels&msg=" . urlencode($msg));
            exit;
        }
    }

    if (isset($_POST['publish_now'])) {
        if ($bot->publishPrices(true)) {
            $msg = 'انتشار با موفقیت انجام شد!';
        } else {
            $msg = 'خطا در انتشار. لطفاً لاگ‌ها را بررسی کنید.';
        }
        header("Location: ?tab=" . $tab . "&msg=" . urlencode($msg));
        exit;
    }

    if (isset($_POST['save_config'])) {
        $enabledConfigs = $_POST['config'] ?? [];
        $countryEmojis = $_POST['country_emojis'] ?? [];

        db()->beginTransaction();
        try {
            // Update Emojis
            $stmtEmoji = db()->prepare("UPDATE countries SET telegram_emoji = ? WHERE code = ?");
            foreach ($countryEmojis as $code => $emoji) {
                $stmtEmoji->execute([$emoji, $code]);
            }

            // Update Enabled Toggles
            db()->exec("UPDATE telegram_config SET enabled = 0");
            $stmt = db()->prepare("INSERT INTO telegram_config (brand_code, country_code, enabled) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE enabled = 1");
            foreach ($enabledConfigs as $cfg) {
                if (strpos($cfg, '|') !== false) {
                    list($b, $c) = explode('|', $cfg);
                    $stmt->execute([$b, $c]);
                }
            }
            db()->commit();
            $msg = 'پیکربندی و ایموجی‌ها با موفقیت ذخیره شد!';
        } catch (Exception $e) {
            db()->rollBack();
            $msg = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
        }
        header("Location: ?tab=config&msg=" . urlencode($msg));
        exit;
    }

    if (isset($_POST['clear_logs'])) {
        db()->exec("DELETE FROM telegram_logs");
        $msg = 'لاگ‌ها با موفقیت حذف شدند!';
        header("Location: ?tab=logs&msg=" . urlencode($msg));
        exit;
    }

    if (isset($_POST['delete_channel'])) {
        $stmt = db()->prepare("DELETE FROM telegram_channels WHERE id = ?");
        $stmt->execute([$_POST['channel_db_id']]);
        $msg = 'کانال حذف شد!';
        header("Location: ?tab=channels&msg=" . urlencode($msg));
        exit;
    }
}

$pageTitle = 'مدیریت ربات تلگرام';
require_once 'layout_header.php';

// Fetch Data
$st_enabled = getSetting('telegram_bot_enabled', '0');
$st_token = getSetting('telegram_bot_token', '');
$st_username = getSetting('telegram_bot_username', '');
$st_publish_time = getSetting('telegram_publish_time', '09:00');
$st_template = getSetting('telegram_message_template', "{emoji} {brand} Gift Card – {currency} {denomination}\n\nDigital\n\n[DIGITAL_PACKS]\n• Pack {size} → {currency}{price}\n[/DIGITAL_PACKS]\n\nPhysical\n\n[PHYSICAL_PACKS]\n• Pack {size} → {currency}{price}\n[/PHYSICAL_PACKS]\n\n🕒 Last update: {last_update_time}");
$st_use_emojis = getSetting('telegram_use_emojis', '1');
$st_price_type = getSetting('telegram_price_type', 'both');
$st_currency_symbols = getSetting('telegram_currency_symbols', '$, USD, AED, EUR, GBP, TL');

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

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <?php
        $displayMsg = $msg ?: ($_GET['msg'] ?? '');
        if ($displayMsg): ?>
            <div class="<?php echo (strpos($displayMsg, 'خطا') !== false || strpos($displayMsg, 'حذف') !== false) ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30' : 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30'; ?> px-6 py-3 rounded-xl border text-sm flex items-center gap-2">
                <iconify-icon icon="<?php echo (strpos($displayMsg, 'خطا') !== false || strpos($displayMsg, 'حذف') !== false) ? 'lucide:triangle-alert' : 'lucide:circle-check'; ?>" class="text-xl"></iconify-icon>
                <?php echo e($displayMsg); ?>
            </div>
        <?php endif; ?>
    </div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <button type="submit" name="publish_now" class="btn-primary " onclick="return confirm('آیا از انتشار دستی قیمت‌ها اطمینان دارید؟')">
            <iconify-icon icon="lucide:rocket" class="text-xl"></iconify-icon>
            <span>ارسال به کانال</span>
        </button>
    </form>
</div>

<div class="admin-card !p-0 overflow-hidden">
    <!-- Tabs Header -->
    <div class="flex flex-wrap border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50">
        <a href="?tab=settings" class="px-6 py-4 text-sm font-bold transition-all border-b-2 <?php echo $tab === 'settings' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'; ?>">تنظیمات کلی</a>
        <a href="?tab=channels" class="px-6 py-4 text-sm font-bold transition-all border-b-2 <?php echo $tab === 'channels' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'; ?>">مدیریت کانال‌ها</a>
        <a href="?tab=config" class="px-6 py-4 text-sm font-bold transition-all border-b-2 <?php echo $tab === 'config' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'; ?>">پیکربندی برند/کشور</a>
        <a href="?tab=logs" class="px-6 py-4 text-sm font-bold transition-all border-b-2 <?php echo $tab === 'logs' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'; ?>">لاگ‌ها</a>
    </div>

    <div class="p-6 md:p-8 lg:p-10">
        <?php if ($tab === 'settings'): ?>
            <form method="POST" class="space-y-8 max-w-3xl">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="enabled" value="1" <?php echo $st_enabled === '1' ? 'checked' : ''; ?>
                               class="w-5 h-5 rounded border-slate-300 text-primary focus:ring-primary">
                        <span class="font-bold text-slate-900 dark:text-white">فعالسازی ربات تلگرام</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">توکن ربات (Bot Token)</label>
                        <input type="text" name="token" value="<?php echo e($st_token); ?>" placeholder="123456789:ABCDE..."
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm focus:border-primary outline-none font-mono">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">نام کاربری ربات (@Username)</label>
                        <input type="text" name="username" value="<?php echo e($st_username); ?>" placeholder="@my_price_bot"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm focus:border-primary outline-none font-mono" dir="ltr">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">زمان انتشار خودکار (روزانه)</label>
                        <input type="time" name="publish_time" value="<?php echo e($st_publish_time); ?>"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm focus:border-primary outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">نوع قیمت برای انتشار</label>
                        <select name="price_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm focus:border-primary outline-none">
                            <option value="both" <?php echo $st_price_type === 'both' ? 'selected' : ''; ?>>هر دو (دیجیتال و فیزیکی)</option>
                            <option value="digital" <?php echo $st_price_type === 'digital' ? 'selected' : ''; ?>>فقط دیجیتال</option>
                            <option value="physical" <?php echo $st_price_type === 'physical' ? 'selected' : ''; ?>>فقط فیزیکی</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="use_emojis" id="use_emojis" value="1" <?php echo $st_use_emojis === '1' ? 'checked' : ''; ?>
                               class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                        <label for="use_emojis" class="text-sm text-slate-600 dark:text-slate-400 cursor-pointer">استفاده از ایموجی پرچم کشورها</label>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">حذف نمادهای ارزی (جدا شده با کاما)</label>
                        <input type="text" name="currency_symbols" value="<?php echo e($st_currency_symbols); ?>"
                               class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm outline-none focus:border-primary">
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-4">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300">قالب پیام (Message Template)</label>

                            <!-- Markdown Toolbar -->
                            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg">
                                <button type="button" onclick="insertMarkdown('*', '*')" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded transition-all text-slate-600 dark:text-slate-400 flex items-center" title="Bold">
                                    <iconify-icon icon="lucide:bold" class="text-lg"></iconify-icon>
                                </button>
                                <button type="button" onclick="insertMarkdown('_', '_')" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded transition-all text-slate-600 dark:text-slate-400 flex items-center" title="Italic">
                                    <iconify-icon icon="lucide:italic" class="text-lg"></iconify-icon>
                                </button>
                                <button type="button" onclick="insertMarkdown('[', '](url)')" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded transition-all text-slate-600 dark:text-slate-400 flex items-center" title="Link">
                                    <iconify-icon icon="lucide:link" class="text-lg"></iconify-icon>
                                </button>
                                <button type="button" onclick="insertMarkdown('`', '`')" class="p-1.5 hover:bg-white dark:hover:bg-slate-700 rounded transition-all text-slate-600 dark:text-slate-400 flex items-center" title="Code">
                                    <iconify-icon icon="lucide:code" class="text-lg"></iconify-icon>
                                </button>
                            </div>
                        </div>
                        <textarea id="template_textarea" name="template" rows="10"
                                  class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm focus:border-primary outline-none font-mono leading-relaxed" dir="ltr"><?php echo e($st_template); ?></textarea>
                    </div>

                    <script>
                    function insertMarkdown(prefix, suffix) {
                        const textarea = document.getElementById('template_textarea');
                        const start = textarea.selectionStart;
                        const end = textarea.selectionEnd;
                        const text = textarea.value;
                        const selectedText = text.substring(start, end);
                        const replacement = prefix + selectedText + suffix;

                        textarea.value = text.substring(0, start) + replacement + text.substring(end);
                        textarea.focus();
                        textarea.setSelectionRange(start + prefix.length, start + prefix.length + selectedText.length);
                    }
                    </script>

                    <!-- Variable Guide Table -->
                    <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-right text-xs">
                            <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 font-bold">
                                <tr>
                                    <th class="px-4 py-2 border-b border-slate-200 dark:border-slate-800">متغیر</th>
                                    <th class="px-4 py-2 border-b border-slate-200 dark:border-slate-800">توضیح</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr class="bg-slate-50/30 dark:bg-slate-900/30"><td colspan="2" class="px-4 py-1.5 font-bold text-[10px] text-slate-400 uppercase tracking-widest text-center">متغیرهای کلی</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-primary">{emoji}</td><td class="px-4 py-2">پرچم کشور</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-primary">{brand}</td><td class="px-4 py-2">نام برند (مثلاً Apple)</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-primary">{country_name}</td><td class="px-4 py-2">نام کشور</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-primary">{currency}</td><td class="px-4 py-2">واحد ارزی محصول</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-primary">{denomination}</td><td class="px-4 py-2">مبلغ اعتبار (بدون نماد ارز)</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-primary">{last_update_time}</td><td class="px-4 py-2">زمان بروزرسانی</td></tr>

                                <tr class="bg-slate-50/30 dark:bg-slate-900/30"><td colspan="2" class="px-4 py-1.5 font-bold text-[10px] text-slate-400 uppercase tracking-widest text-center">بلاک‌های تکرار شونده</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-indigo-500" dir="ltr">[DIGITAL_PACKS] ... [/DIGITAL_PACKS]</td><td class="px-4 py-2">بلاک مربوط به قیمت‌های دیجیتال</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-indigo-500" dir="ltr">[PHYSICAL_PACKS] ... [/PHYSICAL_PACKS]</td><td class="px-4 py-2">بلاک مربوط به قیمت‌های فیزیکی</td></tr>

                                <tr class="bg-slate-50/30 dark:bg-slate-900/30"><td colspan="2" class="px-4 py-1.5 font-bold text-[10px] text-slate-400 uppercase tracking-widest text-center">متغیرهای داخل بلاک</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-indigo-500">{size}</td><td class="px-4 py-2">تعداد در پکیج</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-indigo-500">{price}</td><td class="px-4 py-2">قیمت به ارز اصلی محصول</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-indigo-500">{converted_price}</td><td class="px-4 py-2">قیمت تبدیل شده (AED)</td></tr>
                                <tr class="bg-slate-50/30 dark:bg-slate-900/30"><td colspan="2" class="px-4 py-1.5 font-bold text-[10px] text-slate-400 uppercase tracking-widest text-center">فرمت‌دهی متن (Markdown)</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-slate-500">*bold text*</td><td class="px-4 py-2 text-bold italic font-bold">متن ضخیم (Bold)</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-slate-500">_italic text_</td><td class="px-4 py-2 italic font-serif">متن کج (Italic)</td></tr>
                                <tr><td class="px-4 py-2 font-mono text-slate-500">[Text](URL)</td><td class="px-4 py-2 underline text-primary">ایجاد لینک (Link)</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/20 text-[11px] text-blue-700 dark:text-blue-400 leading-relaxed space-y-2">
                    <p><span class="font-bold">ℹ️ راهنما:</span> مقادیر بالا در ساخت پیام‌های تلگرام استفاده می‌شوند. شما می‌توانید از تگ‌های Markdown برای ضخیم یا کج کردن متن و همچنین لینک‌دار کردن عبارات استفاده کنید.</p>
                    <p class="opacity-80">💡 سیستم به طور خودکار کاراکترهای خاص در متغیرها و همچنین آیدی‌های تلگرام (مثل @UAE_GIFT_PRICE) را اصلاح می‌کند تا از تغییر فرمت ناخواسته جلوگیری شود.</p>
                    <p class="opacity-80">برای داشتن خروجی استاندارد، از دکمه <span class="font-bold">بازنشانی به پیش‌فرض</span> استفاده کنید.</p>
                </div>

                <div class="pt-4 flex flex-wrap gap-4">
                    <button type="submit" name="save_settings" class="btn-primary px-10 py-3">ذخیره تنظیمات</button>
                    <button type="submit" name="reset_templates" class="bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-6 py-3 rounded-xl font-bold hover:bg-slate-300 dark:hover:bg-slate-700 transition-all text-sm" onclick="return confirm('آیا از بازنشانی قالب‌ها و برچسب‌ها به حالت پیش‌فرض اطمینان دارید؟')">بازنشانی به پیش‌فرض</button>
                </div>
            </form>

        <?php elseif ($tab === 'channels'): ?>
            <div class="bg-slate-50 dark:bg-slate-950/50 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 mb-8">
                <h4 class="text-sm font-bold mb-4 flex items-center gap-2">
                    <iconify-icon icon="lucide:circle-plus" class="text-primary text-xl"></iconify-icon>
                    <span>افزودن کانال جدید</span>
                </h4>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ms-1">Channel ID</label>
                        <input type="text" name="channel_id" placeholder="مثلاً -100123456789" required dir="ltr"
                               class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm focus:border-primary outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ms-1">نام کانال</label>
                        <input type="text" name="channel_name" placeholder="نام نمایشی..."
                               class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-sm focus:border-primary outline-none">
                    </div>
                    <button type="submit" name="add_channel" class="btn-primary !py-2.5 text-sm">افزودن کانال</button>
                </form>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-right border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 font-bold">Channel ID</th>
                            <th class="px-6 py-4 font-bold">نام کانال</th>
                            <th class="px-6 py-4 font-bold">تاریخ افزودن</th>
                            <th class="px-6 py-4 font-bold w-24">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($channels as $c): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                            <td class="px-6 py-4 font-mono text-xs" dir="ltr"><?php echo e($c['channel_id']); ?></td>
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white"><?php echo e($c['name']); ?></td>
                            <td class="px-6 py-4 text-slate-500"><?php echo date('Y-m-d H:i', strtotime($c['created_at'])); ?></td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline" onsubmit="return confirm('آیا از حذف این کانال اطمینان دارید؟')">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="delete_channel" value="1">
                                    <input type="hidden" name="channel_db_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 px-3 py-1.5 rounded-lg transition-all font-bold">حذف</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($channels)): ?>
                            <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">هیچ کانالی ثبت نشده است.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($tab === 'config'): ?>
            <form method="POST" class="space-y-12">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div>
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <iconify-icon icon="lucide:flag" class="text-primary text-2xl"></iconify-icon>
                        <span>تنظیمات ایموجی کشورها</span>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php foreach ($countries as $country): ?>
                            <div class="space-y-1.5 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-950/30">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block truncate"><?php echo e($country['name']); ?></label>
                                <input type="text" name="country_emojis[<?php echo e($country['code']); ?>]" value="<?php echo e($country['telegram_emoji']); ?>" placeholder="ایموجی"
                                       class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-lg focus:border-primary outline-none transition-all">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="h-px bg-slate-200 dark:bg-slate-800"></div>

                <div>
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                        <iconify-icon icon="lucide:send" class="text-primary text-2xl"></iconify-icon>
                        <span>فعالسازی برند/کشور برای ربات</span>
                    </h3>
                    <div class="space-y-8 max-h-[600px] overflow-y-auto pr-4 scroll-smooth">
                        <?php foreach ($brands as $brand): ?>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <?php if($brand['logo']): ?><img src="../<?php echo $brand['logo']; ?>" class="w-6 h-6 object-contain"><?php endif; ?>
                                    <h4 class="font-bold text-slate-900 dark:text-white"><?php echo e($brand['name']); ?></h4>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach ($countries as $country): ?>
                                        <label class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 hover:border-primary cursor-pointer transition-all bg-white dark:bg-slate-900 has-[:checked]:bg-primary/5 has-[:checked]:border-primary">
                                            <input type="checkbox" name="config[]" value="<?php echo $brand['code'] . '|' . $country['code']; ?>" <?php echo isset($configMap[$brand['code']][$country['code']]) ? 'checked' : ''; ?>
                                                   class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary">
                                            <span class="text-xs font-medium"><?php echo e($country['name']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" name="save_config" class="btn-primary px-10 py-4 shadow-xl shadow-primary/30">ذخیره پیکربندی</button>
                </div>
            </form>

        <?php elseif ($tab === 'logs'): ?>
            <div class="flex justify-end mb-4">
                <form method="POST" onsubmit="return confirm('آیا از حذف تمامی لاگ‌ها اطمینان دارید؟')">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <button type="submit" name="clear_logs" class="flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl transition-all font-bold text-sm">
                        <iconify-icon icon="lucide:trash-2" class="text-xl"></iconify-icon>
                        <span>حذف تمامی لاگ‌ها</span>
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-right border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold border-b border-slate-200 dark:border-slate-800">
                            <th class="px-6 py-4 font-bold">زمان</th>
                            <th class="px-6 py-4 font-bold">وضعیت</th>
                            <th class="px-6 py-4 font-bold">پیام</th>
                            <th class="px-6 py-4 font-bold">پاسخ سرور</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20">
                            <td class="px-6 py-4 text-slate-500 whitespace-nowrap"><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-widest <?php echo $log['status'] === 'success' ? 'bg-green-100 text-green-700' : ($log['status'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'); ?>">
                                    <?php echo e($log['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-700 dark:text-slate-300"><?php echo e($log['message']); ?></td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs overflow-hidden text-ellipsis whitespace-nowrap font-mono text-[10px] text-slate-400" title="<?php echo e($log['response']); ?>" dir="ltr">
                                    <?php echo e($log['response']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">هیچ لاگی یافت نشد.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
