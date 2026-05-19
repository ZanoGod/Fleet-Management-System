<?php

declare(strict_types=1);

$flash = $flash ?? get_flash();
?>

<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($dbError !== null): ?>
    <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
        <strong>Database connection not ready.</strong>
        Import the SQL file and update <code>config/database.php</code> if your MySQL username or password is different.
        <div class="small text-muted mt-2"><?= e($dbError) ?></div>
    </div>
<?php endif; ?>
