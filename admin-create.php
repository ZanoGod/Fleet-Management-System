<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// Ensure the user is logged in before creating an admin account
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$activePage  = 'admins';
$pageTitle   = 'Create Admin Account';
$pageSummary = 'Add a new administrative user to the fleet management panel.';

$error    = '';
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim((string) ($_POST['username'] ?? ''));
    $email     = trim((string) ($_POST['email'] ?? ''));
    $password  = (string) ($_POST['password'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($username === '') {
        $error = 'Please enter a username.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!($db instanceof mysqli)) {
        $error = 'Database connection error.';
    } else {
        // Check if username or email already exists
        $checkStmt = $db->prepare('SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1');
        if ($checkStmt instanceof mysqli_stmt) {
            $checkStmt->bind_param('ss', $username, $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $error = 'Username or email address is already in use.';
            }
            $checkStmt->close();
        }

        // Insert new admin record if valid
        if (empty($error)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $db->prepare(
                'INSERT INTO admins (username, email, password_hash, is_active, created_at, updated_at) 
                 VALUES (?, ?, ?, 1, NOW(), NOW())'
            );

            if ($insertStmt instanceof mysqli_stmt) {
                $insertStmt->bind_param('sss', $username, $email, $passwordHash);
                
                if ($insertStmt->execute()) {
                    set_flash('success', 'Admin account created successfully.');
                    header('Location: admin-create.php');
                    exit;
                } else {
                    $error = 'Failed to create admin account. Please try again.';
                }
                $insertStmt->close();
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<style>
    .form-card {
        max-width: 580px;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 24px;
        box-shadow: 0 15px 35px rgba(74, 52, 38, 0.05);
    }

    .form-label-modern {
        display: block;
        margin-bottom: .5rem;
        font-size: .85rem;
        font-weight: 600;
        color: #6c5a4e;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #a0958d;
        font-size: 1rem;
        z-index: 2;
    }

    .form-control-modern {
        width: 100%;
        height: 52px;
        padding-left: 48px;
        padding-right: 48px;
        border-radius: 14px;
        border: 1px solid #e2dad3;
        background: #ffffff;
        font-size: .95rem;
        font-weight: 500;
        color: #4a3426;
        transition: all .25s ease;
    }

    .form-control-modern::placeholder {
        color: #bfae9e;
        font-weight: 400;
    }

    .form-control-modern:focus {
        outline: none;
        border-color: #e9b63d;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(233, 182, 61, 0.18);
    }

    .toggle-password {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: transparent;
        color: #a0958d;
        cursor: pointer;
        z-index: 2;
    }

    .toggle-password:hover {
        color: #4a3426;
    }

    .btn-brand-primary {
        height: 50px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #f6c64d, #e9b63d);
        color: #4a3426;
        font-size: .95rem;
        font-weight: 600;
        padding: 0 24px;
        transition: all .25s ease;
        box-shadow: 0 8px 20px rgba(233, 182, 61, 0.25);
    }

    .btn-brand-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 25px rgba(233, 182, 61, 0.35);
        color: #2e1f16;
    }

    .btn-brand-secondary {
        height: 50px;
        border: 1px solid #dcd1c8;
        border-radius: 14px;
        background: #ffffff;
        color: #6c5a4e;
        font-size: .95rem;
        font-weight: 600;
        padding: 0 24px;
        transition: all .25s ease;
    }

    .btn-brand-secondary:hover {
        background: #f7f4ef;
        color: #4a3426;
        border-color: #cbbeb3;
    }
</style>

<div class="row justify-content-center my-4">
    <div class="col-12 col-md-10 col-lg-8 d-flex justify-content-center">
        <div class="form-card w-100 p-4 p-sm-5">
            
            <div class="mb-4">
                <h4 class="fw-bold mb-1" style="color: #4a3426;">New Admin Details</h4>
                <p class="text-muted small m-0">Fill in the credentials to grant system administration access.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="admin-create.php" autocomplete="off">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label class="form-label-modern" for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-control-modern" 
                            value="<?= e($username) ?>" 
                            placeholder="e.g. jondoe"
                            required
                        >
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label-modern" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control-modern" 
                            value="<?= e($email) ?>" 
                            placeholder="e.g. admin@example.com"
                            required
                        >
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label-modern" for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-shield-lock input-icon"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control-modern" 
                            placeholder="••••••••"
                            required
                        >
                        <button type="button" id="togglePassword" class="toggle-password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-5">
                    <a href="index.php" class="btn btn-brand-secondary d-inline-flex align-items-center">Cancel</a>
                    <button type="submit" class="btn btn-brand-primary d-inline-flex align-items-center">
                        <i class="bi bi-person-plus-fill me-2"></i>Create Account
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePasswordButton && passwordInput) {
        togglePasswordButton.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            
            const icon = togglePasswordButton.querySelector('i');
            icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>