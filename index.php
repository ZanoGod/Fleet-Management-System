<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$pageSummary = 'Monitor bookings, available cars, and driver readiness from one warm, simple control panel.';
$pageActions = '<a class="btn btn-accent" href="create.php">New Booking</a>';
$flash = get_flash();

$summary = [
    'total_bookings' => 0,
    'active_bookings' => 0,
    'total_cars' => 0,
    'available_cars' => 0,
    'total_drivers' => 0,
    'available_drivers' => 0,
];

$recentBookings = [];
$bookingStatusCounts = array_fill_keys(booking_statuses(), 0);
$carStatusCounts = array_fill_keys(car_statuses(), 0);
$driverStatusCounts = array_fill_keys(driver_statuses(), 0);

if ($db instanceof mysqli) {
    $summaryResult = $db->query(
        "SELECT
            (SELECT COUNT(*) FROM bookings) AS total_bookings,
            (SELECT COUNT(*) FROM bookings WHERE status IN ('Confirm', 'In Service') AND end_date >= CURDATE()) AS active_bookings,
            (SELECT COUNT(*) FROM cars) AS total_cars,
            (SELECT COUNT(*) FROM cars WHERE availability_status = 'Available') AS available_cars,
            (SELECT COUNT(*) FROM drivers) AS total_drivers,
            (SELECT COUNT(*) FROM drivers WHERE driver_status = 'Available') AS available_drivers"
    );

    if ($summaryResult instanceof mysqli_result) {
        $summary = array_merge($summary, $summaryResult->fetch_assoc() ?: []);
    }

    $recentResult = $db->query(
        "SELECT
            b.id,
            b.guest_company_name,
            b.operator_name,
            b.start_date,
            b.end_date,
            b.status,
            c.car_type,
            c.plate_no,
            d.full_name AS driver_name
         FROM bookings AS b
         INNER JOIN cars AS c ON c.id = b.car_id
         INNER JOIN drivers AS d ON d.id = b.driver_id
         ORDER BY b.start_date DESC, b.id DESC
         LIMIT 6"
    );

    if ($recentResult instanceof mysqli_result) {
        while ($row = $recentResult->fetch_assoc()) {
            $recentBookings[] = $row;
        }
    }

    $bookingStatusResult = $db->query(
        "SELECT status AS label, COUNT(*) AS total
         FROM bookings
         GROUP BY status"
    );

    if ($bookingStatusResult instanceof mysqli_result) {
        while ($row = $bookingStatusResult->fetch_assoc()) {
            $bookingStatusCounts[$row['label']] = (int) $row['total'];
        }
    }

    $carStatusResult = $db->query(
        "SELECT availability_status AS label, COUNT(*) AS total
         FROM cars
         GROUP BY availability_status"
    );

    if ($carStatusResult instanceof mysqli_result) {
        while ($row = $carStatusResult->fetch_assoc()) {
            $carStatusCounts[$row['label']] = (int) $row['total'];
        }
    }

    $driverStatusResult = $db->query(
        "SELECT driver_status AS label, COUNT(*) AS total
         FROM drivers
         GROUP BY driver_status"
    );

    if ($driverStatusResult instanceof mysqli_result) {
        while ($row = $driverStatusResult->fetch_assoc()) {
            $driverStatusCounts[$row['label']] = (int) $row['total'];
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<section class="card-shell hero-banner mb-4">
    <div class="row g-4 align-items-center position-relative">
        <div class="col-xl-8">
            <span class="eyebrow">Warm Theme Layout</span>
            <h2 class="display-6 mb-3">The system now uses your cream, gold, amber, and dark brown palette.</h2>
            <p class="text-muted-soft mb-0">The old sheet-style booking table is still here, but the layout now has a sidebar and dedicated modules for bookings, cars, drivers, and reports.</p>
        </div>
        <div class="col-xl-4">
            <div class="card-shell overview-card">
                <span>Live Availability</span>
                <strong><?= e((string) $summary['available_cars']) ?> cars</strong>
                <small><?= e((string) $summary['available_drivers']) ?> drivers ready for assignment today</small>
            </div>
        </div>
    </div>
</section>

<section class="overview-grid">
    <div class="card-shell overview-card">
        <span>Total Bookings</span>
        <strong><?= e((string) $summary['total_bookings']) ?></strong>
        <small>All trip records in the system</small>
    </div>
    <div class="card-shell overview-card">
        <span>Active Trips</span>
        <strong><?= e((string) $summary['active_bookings']) ?></strong>
        <small>Confirmed or in-service bookings</small>
    </div>
    <div class="card-shell overview-card">
        <span>Total Cars</span>
        <strong><?= e((string) $summary['total_cars']) ?></strong>
        <small><?= e((string) $summary['available_cars']) ?> currently available</small>
    </div>
    <div class="card-shell overview-card">
        <span>Total Drivers</span>
        <strong><?= e((string) $summary['total_drivers']) ?></strong>
        <small><?= e((string) $summary['available_drivers']) ?> currently available</small>
    </div>
</section>

<section class="mini-grid">
    <div class="card-shell section-card">
        <div class="section-title">
            <div>
                <h2>Recent Bookings</h2>
                <p>Latest assignments across vehicles and drivers.</p>
            </div>
            <a class="btn btn-shell" href="bookings.php">View All</a>
        </div>

        <div class="table-responsive">
            <table class="table data-table dashboard-table">
                <thead>
                    <tr>
                        <th>Guest / Company</th>
                        <th>Car</th>
                        <th>Driver</th>
                        <th>Dates</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentBookings === []): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted-soft">No bookings available yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e($booking['guest_company_name']) ?></div>
                                    <div class="soft-note"><?= e($booking['operator_name']) ?></div>
                                </td>
                                <td>
                                    <span class="table-pill car-pill"><?= e($booking['car_type']) ?></span>
                                    <div class="soft-note mt-2"><?= e($booking['plate_no']) ?></div>
                                </td>
                                <td><span class="table-pill driver-pill"><?= e($booking['driver_name']) ?></span></td>
                                <td><?= e(format_display_date($booking['start_date'])) ?><br><span class="soft-note"><?= e(format_display_date($booking['end_date'])) ?></span></td>
                                <td><span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-grid gap-4">
        <div class="card-shell stack-card">
            <div class="section-title">
                <div>
                    <h2>Booking Status</h2>
                    <p>Overall trip distribution.</p>
                </div>
            </div>
            <?php $bookingTotal = max(1, array_sum($bookingStatusCounts)); ?>
            <div class="list-clean">
                <?php foreach ($bookingStatusCounts as $label => $count): ?>
                    <div class="metric-row">
                        <div>
                            <strong><?= e($label) ?></strong>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: <?= e((string) round(($count / $bookingTotal) * 100, 1)) ?>%"></div>
                            </div>
                        </div>
                        <span><?= e((string) $count) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card-shell stack-card">
            <div class="section-title">
                <div>
                    <h2>Resource Readiness</h2>
                    <p>Cars and drivers at a glance.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="soft-note mb-2">Cars</div>
                    <?php $carTotal = max(1, array_sum($carStatusCounts)); ?>
                    <?php foreach ($carStatusCounts as $label => $count): ?>
                        <div class="metric-row">
                            <div>
                                <strong><?= e($label) ?></strong>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?= e((string) round(($count / $carTotal) * 100, 1)) ?>%"></div>
                                </div>
                            </div>
                            <span><?= e((string) $count) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-12">
                    <div class="soft-note mb-2">Drivers</div>
                    <?php $driverTotal = max(1, array_sum($driverStatusCounts)); ?>
                    <?php foreach ($driverStatusCounts as $label => $count): ?>
                        <div class="metric-row">
                            <div>
                                <strong><?= e($label) ?></strong>
                                <div class="bar-track">
                                    <div class="bar-fill" style="width: <?= e((string) round(($count / $driverTotal) * 100, 1)) ?>%"></div>
                                </div>
                            </div>
                            <span><?= e((string) $count) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
