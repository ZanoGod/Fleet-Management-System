<?php
require_once __DIR__ . '/includes/bootstrap.php';

// If already logged in
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Demo credentials
    if ($username === 'admin' && $password === 'admin123') {

        $_SESSION['admin_logged_in'] = true;

        header('Location: index.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fleet Management Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --gold: #f6c64d;
            --amber: #e9b63d;
            --cocoa: #4a3426;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;

            display: flex;
            justify-content: center;
            align-items: center;

            font-family:
                "Inter",
                "Segoe UI",
                sans-serif;

            background:
                radial-gradient(circle at top left,
                    rgba(255, 213, 90, 0.22),
                    transparent 30%),
                radial-gradient(circle at bottom right,
                    rgba(255, 183, 0, 0.15),
                    transparent 35%),
                #f7f4ef;
        }

        .login-card {
            width: 100%;
            max-width: 460px;

            padding: 42px;

            background: rgba(255, 255, 255, .82);

            backdrop-filter: blur(12px);

            border-radius: 28px;

            border: 1px solid rgba(255, 255, 255, .7);

            box-shadow:
                0 20px 60px rgba(0, 0, 0, .08);
        }

        .login-brand-wrapper {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-mark {
            width: 68px;
            height: 68px;

            margin: 0 auto 18px;

            border-radius: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(135deg,
                    var(--gold),
                    var(--amber));

            color: var(--cocoa);

            font-size: 1.35rem;
            font-weight: 700;

            box-shadow:
                0 10px 25px rgba(233, 182, 61, .25);
        }

        .login-brand-wrapper h1 {
            margin: 0;

            font-size: 1.85rem;
            font-weight: 500;

            color: var(--cocoa);

            letter-spacing: -.02em;
        }

        .subtitle {
            margin-top: .4rem;

            color: #8c8178;

            font-size: .95rem;
            font-weight: 400;
        }

        .form-label-modern {
            display: block;

            margin-bottom: .55rem;

            font-size: .85rem;
            font-weight: 500;

            color: #7c7c7c;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;

            left: 18px;
            top: 50%;

            transform: translateY(-50%);

            color: #b0b0b0;

            font-size: .95rem;

            z-index: 2;
        }

        .form-control-modern {
            width: 100%;

            height: 58px;

            padding-left: 48px;
            padding-right: 48px;

            border-radius: 16px;

            border: 1px solid #e7e7e7;

            background: rgba(255, 255, 255, .95);

            font-size: .95rem;
            font-weight: 400;

            color: #444;

            transition: all .25s ease;
        }

        .form-control-modern::placeholder {
            color: #b9b9b9;
            font-weight: 400;
        }

        .form-control-modern:focus {
            outline: none;

            border-color: #f0c14b;

            background: #fff;

            box-shadow:
                0 0 0 4px rgba(240, 193, 75, .14);
        }

        .toggle-password {
            position: absolute;

            right: 16px;
            top: 50%;

            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #9a9a9a;

            cursor: pointer;

            z-index: 2;
        }

        .toggle-password:hover {
            color: #666;
        }

        .btn-login {
            width: 100%;
            height: 58px;

            border: none;

            border-radius: 16px;

            background:
                linear-gradient(135deg,
                    #ffd76a,
                    #f0b534);

            color: #4a3426;

            font-size: .98rem;
            font-weight: 600;

            letter-spacing: .01em;

            transition: all .25s ease;

            box-shadow:
                0 10px 25px rgba(240, 181, 52, .20);
        }

        .btn-login:hover {
            transform: translateY(-1px);

            box-shadow:
                0 14px 30px rgba(240, 181, 52, .28);
        }

        .alert {
            border-radius: 14px;

            font-size: .92rem;

            margin-bottom: 1.25rem;
        }

        @media (max-width: 576px) {

            .login-card {
                margin: 1rem;
                padding: 30px;
            }

            .login-brand-wrapper h1 {
                font-size: 1.6rem;
            }

            .brand-mark {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>

<body>

    <div class="login-card">

        <div class="login-brand-wrapper">

            <div class="brand-mark">
                GSS
            </div>

            <h1>Fleet Management</h1>

            <div class="subtitle">
                Sign in to the admin panel
            </div>

        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <div class="mb-4">

                <label class="form-label-modern">
                    Username
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-person input-icon"></i>

                    <input
                        type="text"
                        name="username"
                        class="form-control form-control-modern"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($username) ?>"
                        autocomplete="username"
                        required>

                </div>

            </div>

            <div class="mb-4">

                <label class="form-label-modern">
                    Password
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-lock input-icon"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control form-control-modern"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required>

                    <button
                        type="button"
                        id="togglePassword"
                        class="toggle-password">

                        <i class="bi bi-eye"></i>

                    </button>

                </div>

            </div>

            <div class="d-grid">

                <button
                    type="submit"
                    class="btn btn-login">

                    Secure Login

                </button>

            </div>

        </form>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const togglePasswordButton =
                document.getElementById('togglePassword');

            const passwordInput =
                document.getElementById('password');

            togglePasswordButton.addEventListener(
                'click',
                function() {

                    const isPassword =
                        passwordInput.type === 'password';

                    passwordInput.type =
                        isPassword ?
                        'text' :
                        'password';

                    const icon =
                        togglePasswordButton.querySelector('i');

                    icon.className =
                        isPassword ?
                        'bi bi-eye-slash' :
                        'bi bi-eye';
                }
            );

        });
    </script>

</body>

</html>