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
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-page {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-body);
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: var(--color-surface);
            border-radius: 20px;
            border: 1px solid var(--color-border);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .error-msg {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body class="dark-theme">
    <div class="login-page">
        <div class="login-card">
            <div class="text-center mb-30">
                <img src="../assets/images/logo.svg" alt="Logo" class="m-auto mb-20">
                <h2 class="color-title">Admin Login</h2>
                <p>Enter your credentials to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="contact-form" style="box-shadow: none; padding: 0;">
                <div class="input-item mb-20">
                    <div class="input-label">Username</div>
                    <div class="input">
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                </div>

                <div class="input-item mb-30">
                    <div class="input-label">Password</div>
                    <div class="input">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary radius-100 full-width">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
