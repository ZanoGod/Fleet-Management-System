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
    <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
</head>
<body>
    <div class="sidebar-backdrop" data-sidebar-close></div>
    <div class="app-shell">
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-main">
                    <div class="brand-mark">GSS</div>
                    <div class="sidebar-brand-copy">
                        <span class="sidebar-kicker">GSS</span>
                        <h2>Fleet Management</h2>
                    </div>
                </div>
                <!-- Removed text, kept only icon -->
                <button class="sidebar-desktop-toggle" type="button" data-sidebar-desktop-toggle aria-label="Toggle sidebar" aria-pressed="false">
                    <span class="sidebar-desktop-toggle-icon" aria-hidden="true"></span>
                </button>
            </div>

            <div class="sidebar-section-label">Navigation</div>
            <nav class="sidebar-nav">
                <?php foreach ($navigationItems as $item): ?>
                    <a class="sidebar-link <?= e(nav_is_active($activePage, $item['key'])) ?>" href="<?= e($item['path']) ?>">
                        <span class="sidebar-link-icon"><?= $item['icon'] ?></span>
                        <span class="sidebar-link-copy">
                            <!-- Removed description -->
                            <strong><?= e($item['label']) ?></strong>
                        </span>
                        <!-- Removed description from tooltip -->
                        <span class="sidebar-link-tooltip" aria-hidden="true">
                            <strong><?= e($item['label']) ?></strong>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="app-main">
            <header class="app-topbar">
                <div class="page-heading">
                    <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-label="Open menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <!-- Removed text, kept only icon -->
                    <button class="sidebar-expand-toggle" type="button" data-sidebar-desktop-toggle aria-label="Toggle sidebar" aria-pressed="false">
                        <span class="sidebar-expand-toggle-icon" aria-hidden="true"></span>
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