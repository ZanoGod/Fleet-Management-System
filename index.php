<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/dashboard-data.php';

$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSummary = 'Monitor bookings, available cars, and driver readiness from one warm, simple control panel.';
$pageActions = '<a class="btn btn-accent" href="create.php">New Booking</a>';
$flash = get_flash();

$dashboardData = load_dashboard_data($db);
$summary = $dashboardData['summary'];
$recentBookings = $dashboardData['recentBookings'];
$bookingStatusCounts = $dashboardData['bookingStatusCounts'];
$carStatusCounts = $dashboardData['carStatusCounts'];
$driverStatusCounts = $dashboardData['driverStatusCounts'];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>


<?php require __DIR__ . '/includes/dashboard/summary-grid.php'; ?>

<section>
    <?php require __DIR__ . '/includes/dashboard/recent-bookings-card.php'; ?>
</section>

<br>

<section class="mini-grid mb-4">
    <?php require __DIR__ . '/includes/dashboard/insights-column.php'; ?>
</section>



<?php require __DIR__ . '/includes/footer.php'; ?>
