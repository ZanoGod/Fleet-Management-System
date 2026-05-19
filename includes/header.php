<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Fleet Management System';
$pageSummary = $pageSummary ?? 'Manage fleet operations from one place.';
$activePage = $activePage ?? 'dashboard';
$pageActions = $pageActions ?? '';
$navigationItems = app_navigation();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <div class="app-shell">
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="brand-mark">FM</div>
                <div>
                    <span class="sidebar-kicker">GSS</span>
                    <h2>Fleet Management</h2>
                </div>
            </div>

            <div class="sidebar-section-label">Navigation</div>
            <nav class="sidebar-nav">
                <?php foreach ($navigationItems as $item): ?>
                    <a class="sidebar-link <?= e(nav_is_active($activePage, $item['key'])) ?>" href="<?= e($item['path']) ?>">
                        <span class="sidebar-link-icon"><?= e($item['icon']) ?></span>
                        <span>
                            <strong><?= e($item['label']) ?></strong>
                            <small><?= e($item['description']) ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="sidebar-cta">
                <span class="sidebar-section-label">Quick Action</span>
                <h3>Need a new trip assignment?</h3>
                <p>Create a booking and connect it with the right car and driver.</p>
                <a class="btn btn-accent w-100" href="create.php">Add Booking</a>
            </div>
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <div class="page-heading">
                    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Open menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <div>
                        <div class="page-kicker">Web-Based Fleet System</div>
                        <h1><?= e($pageTitle) ?></h1>
                        <p><?= e($pageSummary) ?></p>
                    </div>
                </div>

                <div class="topbar-actions">
                    <span class="date-chip"><?= e(date('d M Y')) ?></span>
                    <?= $pageActions ?>
                </div>
            </header>

            <main class="app-content">
