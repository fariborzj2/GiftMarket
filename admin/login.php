<?php
require_once '../system/includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];

    $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        redirect('index.php');
    } else {
        $error = 'نام کاربری یا رمز عبور اشتباه است';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود | پنل مدیریت</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#497FFF',
                    },
                    fontFamily: {
                        vazir: ['Vazirmatn', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply font-vazir bg-slate-50 text-slate-600 dark:bg-slate-950 dark:text-slate-400;
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl p-8 md:p-10">
            <div class="text-center mb-10">
                <img src="../assets/images/logo.svg" alt="Logo" class="h-12 mx-auto mb-6 dark:invert dark:hue-rotate-180 dark:brightness-[1.5]">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">ورود به مدیریت</h2>
                <p class="text-slate-500 dark:text-slate-400">برای ادامه اطلاعات خود را وارد کنید</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 text-red-600 dark:text-red-400 p-4 rounded-xl text-sm text-center mb-6">
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">نام کاربری</label>
                    <input type="text" name="username" required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                           placeholder="نام کاربری خود را وارد کنید">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">رمز عبور</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"
                           placeholder="••••••••">
                </div>

                <button type="submit"
                        class="w-full bg-primary hover:bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 transition-all duration-200 active:transform active:scale-[0.98]">
                    ورود به پنل
                </button>
            </form>
        </div>

        <p class="text-center mt-8 text-sm text-slate-500">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. تمامی حقوق محفوظ است.
        </p>
    </div>
</body>
</html>
