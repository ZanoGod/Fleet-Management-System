<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// If already logged in, go straight to the dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ==========================================
    // HARDCODED ADMIN CREDENTIALS 
    // Change these to whatever you want!
    // ==========================================
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fleet Management</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 3rem 2rem;
            margin: 1rem;
        }
        .login-brand-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-mark {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--gold), var(--amber));
            color: var(--cocoa);
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: 0.04em;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
        }
        .login-brand-wrapper h1 {
            font-size: 1.4rem;
            margin: 0 0 0.2rem 0;
            color: var(--cocoa);
        }
    </style>
</head>
<body>

    <div class="card-shell login-card">
        <div class="login-brand-wrapper">
            <div class="brand-mark">GSS</div>
            <h1>Fleet Management</h1>
            <p class="text-muted-soft small mb-0">Sign in to the admin panel</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger border-0 shadow-sm text-center py-2" style="font-size: 0.9rem;">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label text-muted small">Username</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="e.g. admin">
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label text-muted small">Password</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
            </div>

            <div class="d-grid mt-2">
                <button type="submit" class="btn btn-accent border-0 py-3 shadow-sm">Secure Login</button>
            </div>
        </form>
    </div>

</body>
</html>