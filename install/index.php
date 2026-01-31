<?php
/**
 * UAE.GIFT | Installation Wizard
 */
session_start();

$configFile = __DIR__ . '/../system/includes/config.php';

// If config exists and is already configured, don't allow re-install
if (file_exists($configFile)) {
    require_once $configFile;
    if (defined('INSTALLED') && INSTALLED === true) {
        die("System is already installed. For security, please delete the 'install' directory.");
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Requirements Check
$requirements = [
    'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'Config Directory Writable' => is_writable(__DIR__ . '/../system/includes/'),
];

$allMet = !in_array(false, $requirements);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // Handle DB Config
        $host = $_POST['db_host'];
        $name = $_POST['db_name'];
        $user = $_POST['db_user'];
        $pass = $_POST['db_pass'];

        try {
            $pdo = new PDO("mysql:host=$host", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if database exists or try to create it
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            $_SESSION['db'] = ['host' => $host, 'name' => $name, 'user' => $user, 'pass' => $pass];
            header("Location: index.php?step=3");
            exit;
        } catch (PDOException $e) {
            $error = "Connection failed: " . $e->getMessage();
        }
    } elseif ($step == 3) {
        // Handle Site & Admin Config
        $_SESSION['site_name'] = $_POST['site_name'];
        $_SESSION['admin_user'] = $_POST['admin_user'];
        $_SESSION['admin_pass'] = $_POST['admin_pass'];

        if (strlen($_POST['admin_pass']) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            header("Location: index.php?step=4");
            exit;
        }
    } elseif ($step == 4) {
        // Execute Installation
        try {
            $db = $_SESSION['db'];
            $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1. Create Tables
            $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                brand VARCHAR(100) NOT NULL,
                denomination VARCHAR(100) NOT NULL,
                country VARCHAR(50) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                currency VARCHAR(10) NOT NULL,
                stock INT DEFAULT 0,
                type VARCHAR(20) DEFAULT 'digital',
                status INT DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $pdo->exec($sql);

            // 2. Insert Admin
            $passHash = password_hash($_SESSION['admin_pass'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$_SESSION['admin_user'], $passHash]);

            // 3. Write Config File
            $db_host = addslashes($db['host']);
            $db_name = addslashes($db['name']);
            $db_user = addslashes($db['user']);
            $db_pass = addslashes($db['pass']);
            $site_name = addslashes($_SESSION['site_name']);

            $basePath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $basePath = str_replace('\\', '/', $basePath);
            if ($basePath === '/' || $basePath === '.') $basePath = '';
            $basePath .= '/';

            $configContent = "<?php
// Database Configuration
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

// System Configuration
define('SITE_NAME', '$site_name');
define('BASE_URL', (isset(\$_SERVER['HTTPS']) && \$_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . \$_SERVER['HTTP_HOST'] . '$basePath');
define('INSTALLED', true);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
";
            file_put_contents($configFile, $configContent);

            header("Location: index.php?step=5");
            exit;
        } catch (Exception $e) {
            $error = "Installation failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation | UAE.GIFT</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/grid.css">
    <style>
        body { background: #f4f7fe; color: #1c274c; }
        .install-box { max-width: 600px; margin: 50px auto; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 40px; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { width: 30px; height: 30px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
        .step.active { background: var(--color-primary); color: white; }
        .step.done { background: #179364; color: white; }
        .req-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
        .status-ok { color: #179364; font-weight: bold; }
        .status-fail { color: #ef4444; font-weight: bold; }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="install-box">
        <div class="text-center mb-30">
            <img src="../assets/images/logo.svg" alt="Logo" class="m-auto mb-10" style="width: 150px;">
            <h2 class="color-title">System Installation</h2>
        </div>

        <div class="step-indicator">
            <div class="step <?php echo $step == 1 ? 'active' : ($step > 1 ? 'done' : ''); ?>">1</div>
            <div class="step <?php echo $step == 2 ? 'active' : ($step > 2 ? 'done' : ''); ?>">2</div>
            <div class="step <?php echo $step == 3 ? 'active' : ($step > 3 ? 'done' : ''); ?>">3</div>
            <div class="step <?php echo $step == 4 ? 'active' : ($step > 4 ? 'done' : ''); ?>">4</div>
            <div class="step <?php echo $step == 5 ? 'active' : ''; ?>">5</div>
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <h3 class="mb-20">Step 1: Check Requirements</h3>
            <div class="mb-30">
                <?php foreach ($requirements as $label => $met): ?>
                    <div class="req-item">
                        <span><?php echo $label; ?></span>
                        <span class="<?php echo $met ? 'status-ok' : 'status-fail'; ?>">
                            <?php echo $met ? 'OK' : 'Fail'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($allMet): ?>
                <a href="index.php?step=2" class="btn-primary radius-100 full-width">Next Step</a>
            <?php else: ?>
                <button class="btn-primary radius-100 full-width" style="opacity: 0.5; cursor: not-allowed;" disabled>Please fix requirements</button>
            <?php endif; ?>

        <?php elseif ($step == 2): ?>
            <h3 class="mb-20">Step 2: Database Configuration</h3>
            <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
                <div class="input-item mb-20">
                    <div class="input-label">DB Host</div>
                    <div class="input"><input type="text" name="db_host" value="localhost" required></div>
                </div>
                <div class="input-item mb-20">
                    <div class="input-label">DB Name</div>
                    <div class="input"><input type="text" name="db_name" placeholder="e.g. uae_gift_db" required></div>
                </div>
                <div class="input-item mb-20">
                    <div class="input-label">DB User</div>
                    <div class="input"><input type="text" name="db_user" placeholder="MySQL Username" required></div>
                </div>
                <div class="input-item mb-20">
                    <div class="input-label">DB Password</div>
                    <div class="input"><input type="password" name="db_pass" placeholder="MySQL Password"></div>
                </div>
                <button type="submit" class="btn-primary radius-100 full-width">Test & Continue</button>
            </form>

        <?php elseif ($step == 3): ?>
            <h3 class="mb-20">Step 3: Admin Configuration</h3>
            <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
                <div class="input-item mb-20">
                    <div class="input-label">Site Name</div>
                    <div class="input"><input type="text" name="site_name" value="UAE.GIFT" required></div>
                </div>
                <div class="input-item mb-20">
                    <div class="input-label">Admin Username</div>
                    <div class="input"><input type="text" name="admin_user" value="admin" required></div>
                </div>
                <div class="input-item mb-20">
                    <div class="input-label">Admin Password</div>
                    <div class="input"><input type="password" name="admin_pass" placeholder="At least 6 characters" required></div>
                </div>
                <button type="submit" class="btn-primary radius-100 full-width">Finalize Setup</button>
            </form>

        <?php elseif ($step == 4): ?>
            <h3 class="mb-20 text-center">Step 4: Installation Ready</h3>
            <p class="text-center mb-30">Everything is ready! Click the button below to start the installation process.</p>
            <form method="POST">
                <input type="hidden" name="execute" value="1">
                <button type="submit" class="btn-primary radius-100 full-width">Install Now</button>
            </form>

        <?php elseif ($step == 5): ?>
            <div class="text-center">
                <h1 style="font-size: 60px;">🎉</h1>
                <h2 class="color-title mb-10">Installation Successful!</h2>
                <p class="mb-30">The system has been installed and configured successfully.</p>

                <div style="background: #fffbeb; border: 1px solid #fde68a; padding: 15px; border-radius: 10px; color: #92400e; font-size: 14px; margin-bottom: 30px;">
                    <strong>IMPORTANT:</strong> For security reasons, please delete the <code>/install</code> directory from your server immediately.
                </div>

                <div class="d-flex gap-10">
                    <a href="../index.php" class="btn radius-100 grow-1">View Website</a>
                    <a href="../admin/index.php" class="btn-primary radius-100 grow-1">Admin Panel</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
