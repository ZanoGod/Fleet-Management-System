<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'reports';
$pageTitle = 'Reports';
$pageSummary = 'Review booking, car, and driver distribution for planning and operations.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">Open Bookings</a>';
$flash = get_flash();

$bookingStatusCounts = array_fill_keys(booking_statuses(), 0);
$carStatusCounts = array_fill_keys(car_statuses(), 0);
$driverStatusCounts = array_fill_keys(driver_statuses(), 0);
$upcomingBookings = [];

if ($db instanceof mysqli) {
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

    $upcomingResult = $db->query(
        "SELECT
            b.guest_company_name,
            b.start_date,
            b.end_date,
            b.status,
            c.car_type,
            c.plate_no,
            d.full_name AS driver_name
         FROM bookings AS b
         INNER JOIN cars AS c ON c.id = b.car_id
         INNER JOIN drivers AS d ON d.id = b.driver_id
         WHERE b.end_date >= CURDATE()
         ORDER BY b.start_date ASC, b.id ASC
         LIMIT 8"
    );

    if ($upcomingResult instanceof mysqli_result) {
        while ($row = $upcomingResult->fetch_assoc()) {
            $upcomingBookings[] = $row;
        }
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<section class="mini-grid">
    <div class="card-shell stack-card">
        <div class="section-title">
            <div>
                <h2>Booking Status Report</h2>
                <p>See how your trip pipeline is distributed.</p>
            </div>
        </div>
        <?php $bookingTotal = max(1, array_sum($bookingStatusCounts)); ?>
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

    <div class="card-shell stack-card">
        <div class="section-title">
            <div>
                <h2>Fleet Availability Report</h2>
                <p>Track vehicle readiness across the fleet.</p>
            </div>
        </div>
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
    <div class="card-shell stack-card">
        <div class="section-title">
            <div>
                <h2>Driver Availability Report</h2>
                <p>See who is available, on trip, or on leave.</p>
            </div>
        </div>
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
</section>

<section class="card-shell section-card mt-4">
    <div class="section-title">
        <div>
            <h2>Upcoming Trips</h2>
            <p>Near-term bookings for operations planning.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>Guest / Company</th>
                    <th>Car</th>
                    <th>Driver</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($upcomingBookings === []): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted-soft">No upcoming trips found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($upcomingBookings as $booking): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($booking['guest_company_name']) ?></td>
                            <td>
                                <span class="table-pill car-pill"><?= e($booking['car_type']) ?></span>
                                <div class="soft-note mt-2"><?= e($booking['plate_no']) ?></div>
                            </td>
                            <td><span class="table-pill driver-pill"><?= e($booking['driver_name']) ?></span></td>
                            <td><?= e(format_display_date($booking['start_date'])) ?></td>
                            <td><?= e(format_display_date($booking['end_date'])) ?></td>
                            <td><span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
