<?php
$pageTitle = 'پیام‌های کاربران';
require_once 'layout_header.php';

$msg = '';
$csrfToken = generateCsrfToken();

// Handle Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $action = $_POST['action'];
    $id = $_POST['id'] ?? null;

    if ($action === 'delete' && $id) {
        try {
            $stmt = db()->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'پیام با موفقیت حذف شد!';
        } catch (PDOException $e) {
            $msg = 'خطا در حذف پیام: ' . $e->getMessage();
        }
        header("Location: messages.php?msg=" . urlencode($msg));
        exit;
    }

    if ($action === 'status' && $id && isset($_POST['to'])) {
        try {
            $stmt = db()->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['to'], $id]);
            // For background fetch, we might want to exit early
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => true]);
                exit;
            }
            $msg = 'وضعیت پیام بروزرسانی شد!';
        } catch (PDOException $e) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $msg = 'خطا در بروزرسانی وضعیت: ' . $e->getMessage();
        }
        header("Location: messages.php?msg=" . urlencode($msg));
        exit;
    }

    if ($action === 'mark_all_read') {
        try {
            db()->query("UPDATE contact_messages SET status = 'read' WHERE status = 'unread'");
            $msg = 'همه پیام‌ها به عنوان خوانده شده علامت‌گذاری شدند.';
        } catch (PDOException $e) {
            $msg = 'خطا: ' . $e->getMessage();
        }
        header("Location: messages.php?msg=" . urlencode($msg));
        exit;
    }
}

$unreadCount = db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
    <div>
        <?php
        $displayMsg = $msg ?: ($_GET['msg'] ?? '');
        if ($displayMsg): ?>
            <div class="<?php echo (strpos($displayMsg, 'خطا') === false) ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border-green-100 dark:border-green-900/30' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-100 dark:border-red-900/30'; ?> px-6 py-3 rounded-xl border text-sm flex items-center gap-3 transition-all duration-300">
                <iconify-icon icon="<?php echo (strpos($displayMsg, 'خطا') === false) ? 'solar:check-circle-bold-duotone' : 'solar:danger-bold-duotone'; ?>" class="text-xl"></iconify-icon>
                <?php echo e($displayMsg); ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="flex items-center gap-3">
        <?php if ($unreadCount > 0): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium text-sm flex items-center gap-2">
                    <iconify-icon icon="solar:check-read-bold-duotone" class="text-xl"></iconify-icon>
                    <span>خواندن همه</span>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
    $messages = db()->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
?>

    <div class="admin-card !p-0 overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
            <h3 class="text-lg flex items-center gap-2 m-0">
                <iconify-icon icon="solar:letter-bold-duotone" class="text-primary text-2xl"></iconify-icon>
                <span>پیام‌های دریافتی</span>
            </h3>
            <div class="flex gap-2">
                <span class="text-xs font-medium px-2.5 py-0.5 rounded-xl bg-primary/10 text-primary">
                    <?php echo $unreadCount; ?> جدید
                </span>
                <span class="text-xs font-medium px-2.5 py-0.5 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                    کل: <?php echo count($messages); ?>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="text-slate-400 text-xs uppercase bg-slate-50/30 dark:bg-slate-800/30">
                        <th class="px-6 py-4 font-medium">فرستنده</th>
                        <th class="px-6 py-4 font-medium">موضوع</th>
                        <th class="px-6 py-4 font-medium text-center">وضعیت</th>
                        <th class="px-6 py-4 font-medium">تاریخ</th>
                        <th class="px-6 py-4 font-medium w-32">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <?php if (empty($messages)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                <iconify-icon icon="solar:mailbox-bold-duotone" class="text-5xl mb-4 opacity-20"></iconify-icon>
                                <div>هیچ پیامی یافت نشد.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($messages as $m): ?>
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group <?php echo $m['status'] === 'unread' ? 'bg-primary/5 dark:bg-primary/5' : ''; ?>">
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900 dark:text-white"><?php echo e($m['name']); ?></div>
                            <div class="text-xs text-slate-400"><?php echo e($m['email']); ?></div>
                            <div class="text-xs text-slate-400"><?php echo e($m['mobile']); ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1"><?php echo e($m['subject'] ?: '(بدون موضوع)'); ?></div>
                            <div class="text-xs text-slate-400 truncate max-w-xs"><?php echo e($m['message']); ?></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if ($m['status'] === 'unread'): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                    جدید
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    خوانده شده
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-400 font-mono">
                            <?php echo date('Y-m-d H:i', strtotime($m['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <button onclick="viewMessage(<?php echo htmlspecialchars(json_encode($m)); ?>)" class="p-2 text-primary hover:bg-primary/10 rounded-lg transition-colors" title="مشاهده">
                                    <iconify-icon icon="solar:eye-bold-duotone" class="text-xl"></iconify-icon>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('آیا از حذف این پیام اطمینان دارید؟')">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="حذف">
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

    <!-- Message Detail Modal -->
    <div id="messageModal" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="absolute inset-y-0 left-0 right-0 md:left-auto md:w-[600px] bg-white dark:bg-slate-900 shadow-2xl transform transition-transform duration-300 translate-x-full" id="modalContent">
            <div class="h-full flex flex-col">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-xl flex items-center gap-3">
                        <iconify-icon icon="solar:letter-opened-bold-duotone" class="text-primary text-2xl"></iconify-icon>
                        <span>جزئیات پیام</span>
                    </h3>
                    <button onclick="closeModal()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">
                        <iconify-icon icon="solar:close-circle-bold-duotone" class="text-2xl text-slate-400"></iconify-icon>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-8 space-y-8">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="text-xs text-slate-400 mb-1 uppercase tracking-wider">نام فرستنده</div>
                            <div class="font-bold text-slate-900 dark:text-white" id="m-name"></div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 mb-1 uppercase tracking-wider">تاریخ ارسال</div>
                            <div class="font-mono text-sm text-slate-600 dark:text-slate-400" id="m-date"></div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 mb-1 uppercase tracking-wider">ایمیل</div>
                            <div class="text-slate-600 dark:text-slate-400" id="m-email"></div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 mb-1 uppercase tracking-wider">شماره تماس</div>
                            <div class="text-slate-600 dark:text-slate-400" id="m-mobile"></div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-xs text-slate-400 mb-3 uppercase tracking-wider">موضوع</div>
                        <div class="text-lg font-bold text-slate-900 dark:text-white" id="m-subject"></div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-950/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <div class="text-xs text-slate-400 mb-4 uppercase tracking-wider">متن پیام</div>
                        <div class="text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed" id="m-message"></div>
                    </div>
                </div>
                <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 flex gap-3">
                    <a href="" id="m-reply-email" class="btn-primary flex-1">
                        <iconify-icon icon="solar:forward-bold-duotone" class="text-xl"></iconify-icon>
                        <span>پاسخ با ایمیل</span>
                    </a>
                    <button onclick="changeStatus('unread')" id="m-unread-btn" class="px-6 py-3 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors font-medium">
                        علامت به عنوان نخوانده
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentMessageId = null;
        const CSRF_TOKEN = '<?php echo $csrfToken; ?>';

        function viewMessage(m) {
            currentMessageId = m.id;
            document.getElementById('m-name').textContent = m.name;
            document.getElementById('m-email').textContent = m.email;
            document.getElementById('m-mobile').textContent = m.mobile;
            document.getElementById('m-subject').textContent = m.subject || '(بدون موضوع)';
            document.getElementById('m-message').textContent = m.message;
            document.getElementById('m-date').textContent = m.created_at;
            document.getElementById('m-reply-email').href = 'mailto:' + m.email + '?subject=Re: ' + (m.subject || '');

            if (m.status === 'read') {
                document.getElementById('m-unread-btn').style.display = 'block';
            } else {
                document.getElementById('m-unread-btn').style.display = 'none';
                // Mark as read in DB via background fetch
                const formData = new FormData();
                formData.append('action', 'status');
                formData.append('to', 'read');
                formData.append('id', m.id);
                formData.append('csrf_token', CSRF_TOKEN);
                formData.append('ajax', '1');
                fetch('messages.php', { method: 'POST', body: formData });
            }

            const modal = document.getElementById('messageModal');
            const content = document.getElementById('modalContent');
            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('translate-x-full');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('messageModal');
            const content = document.getElementById('modalContent');
            content.classList.add('translate-x-full');
            setTimeout(() => {
                modal.classList.add('hidden');
                window.location.href = 'messages.php';
            }, 300);
        }

        function changeStatus(to) {
            if (currentMessageId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="to" value="${to}">
                    <input type="hidden" name="id" value="${currentMessageId}">
                    <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

<?php require_once 'layout_footer.php'; ?>
