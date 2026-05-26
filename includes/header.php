<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Fleet Management System';
$pageSummary = $pageSummary ?? 'Manage fleet operations from one place.';
$activePage = $activePage ?? 'dashboard';
$pageActions = $pageActions ?? '';
$pageStyles = $pageStyles ?? [];
$navigationItems = app_navigation();

if (is_string($pageStyles)) {
    $pageStyles = [$pageStyles];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

    <link rel="stylesheet" href="<?= e(asset_url('assets/vendor/bootstrap/bootstrap.min.css')) ?>">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/sidebar.css')) ?>">

    <?php foreach ($pageStyles as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e(asset_url($stylesheet)) ?>">
    <?php endforeach; ?>
</head>

<body>

    <div class="sidebar-backdrop" data-sidebar-close></div>

    <div class="app-shell">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="appSidebar">

            <!-- BRAND -->
            <div class="sidebar-brand">

                <div class="sidebar-brand-main">

                    <button
                        class="brand-mark collapsed-sidebar-trigger"
                        type="button"
                        data-sidebar-desktop-toggle
                        aria-label="Expand Sidebar">

                        <span class="brand-mark-text">GSS</span>

                        <i class="bi bi-chevron-right brand-expand-icon"></i>

                    </button>

                    <div class="sidebar-brand-copy">
                        <span class="sidebar-kicker">v1.0</span>
                        <h2>Fleet Management</h2>
                    </div>

                </div>

                <!-- COLLAPSE BUTTON -->
                <!-- <button
                    class="sidebar-collapse-btn"
                    type="button"
                    data-sidebar-desktop-toggle
                    aria-label="Toggle Sidebar">

                    <i class="bi bi-chevron-left icon-expanded"></i>
                    <i class="bi bi-chevron-right icon-collapsed"></i>

                </button> -->

            </div>

            <!-- NAVIGATION -->
            <nav class="sidebar-nav">

                <?php foreach ($navigationItems as $item): ?>

                    <a class="sidebar-link <?= e(nav_is_active($activePage, $item['key'])) ?>"
                        href="<?= e($item['path']) ?>">

                        <span class="sidebar-link-icon">
                            <?= $item['icon'] ?>
                        </span>

                        <span class="sidebar-link-copy">
                            <strong><?= e($item['label']) ?></strong>
                        </span>

                        <span class="sidebar-link-tooltip" aria-hidden="true">
                            <strong><?= e($item['label']) ?></strong>
                        </span>

                    </a>

                <?php endforeach; ?>

            </nav>

            <!-- FOOTER -->
            <div class="sidebar-footer">


                <form
                    class="logout-form"
                    action="<?= e(asset_url('logout.php')) ?>"
                    method="POST">

                    <button
                        type="submit"
                        class="sidebar-link logout-link"
                        aria-label="Logout">

                        <span class="sidebar-link-icon">
                            <i class="bi bi-box-arrow-left"></i>
                        </span>

                        <span class="sidebar-link-copy">
                            <strong>Logout</strong>
                        </span>

                        <span class="sidebar-link-tooltip" aria-hidden="true">
                            <strong>Logout</strong>
                        </span>

                    </button>

                </form>

            </div>

        </aside>

        <!-- MAIN -->
        <div class="app-main">

            <!-- TOPBAR -->
            <header class="app-topbar">

                <div class="page-heading">

                    <button
                        class="sidebar-toggle"
                        type="button"
                        data-sidebar-toggle
                        aria-label="Open Menu">

                        <span></span>
                        <span></span>
                        <span></span>

                    </button>

                    <div>
                        <div class="page-kicker">
                            Web-Based Fleet Management System
                        </div>

                        <h1><?= e($pageTitle) ?></h1>
                    </div>

                </div>

                <div class="topbar-actions">

                    <span class="date-chip">
                        <?= e(date('d M Y')) ?>
                    </span>

                    <?= $pageActions ?>

                </div>

            </header>

            <!-- CONTENT -->
            <main class="app-content">
