<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'reports';
$pageTitle = 'Reports';
$pageSummary = 'Filter booking activity by custom date ranges and review operations at a glance.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">Open Bookings</a>';
$flash = get_flash();

// Simplified filters array
$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'start_date' => trim((string) ($_GET['start_date'] ?? date('Y-m-01'))),
    'end_date' => trim((string) ($_GET['end_date'] ?? date('Y-m-t'))),
];

$bookingStatusCounts = array_fill_keys(booking_statuses(), 0);
$carStatusCounts = array_fill_keys(car_statuses(), 0);
$driverStatusCounts = array_fill_keys(driver_statuses(), 0);
$reportBookings = [];
$reportSummary = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'in_service' => 0,
    'completed' => 0,
];
$errors = [];
$rangeLabel = 'All booking dates';
$dateFrom = null;
$dateTo = null;

// Straightforward date processing
try {
    if ($filters['start_date'] === '' || $filters['end_date'] === '') {
        $errors[] = 'Please choose both start and end dates for the report.';
    } else {
        $startDate = new DateTimeImmutable($filters['start_date']);
        $endDate = new DateTimeImmutable($filters['end_date']);

        if ($endDate < $startDate) {
            $errors[] = 'End date cannot be earlier than the start date.';
        } else {
            $dateFrom = $startDate->format('Y-m-d');
            $dateTo = $endDate->format('Y-m-d');
            $rangeLabel = $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y');
        }
    }
} catch (Throwable $throwable) {
    $errors[] = 'Please choose a valid report date format.';
}

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(b.guest_company_name LIKE ? OR b.operator_name LIKE ? OR o.full_name LIKE ? OR c.car_type LIKE ? OR c.plate_no LIKE ? OR c2.car_type LIKE ? OR c2.plate_no LIKE ? OR b.custom_car_name LIKE ? OR d.full_name LIKE ? OR b.custom_driver_name LIKE ? OR b.even_odd LIKE ? OR b.remark LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'ssssssssssss';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue);
    }

    if ($dateFrom !== null && $dateTo !== null && $errors === []) {
        $where[] = '(b.start_date <= ? AND b.end_date >= ?)';
        $types .= 'ss';
        array_push($params, $dateTo, $dateFrom);
    }

    $sql = "SELECT
                b.id,
                b.guest_company_name,
                b.operator_name,
                b.custom_car_name,
                b.custom_driver_name,
                b.secondary_car_id,
                b.even_odd,
                b.start_date,
                b.end_date,
                b.status,
                b.remark,
                c.car_type,
                c.plate_no,
                c2.car_type AS secondary_car_type,
                c2.plate_no AS secondary_plate_no,
                d.full_name AS driver_name,
                o.full_name AS operator_full_name
            FROM bookings AS b
            LEFT JOIN cars AS c ON c.id = b.car_id
            LEFT JOIN cars AS c2 ON c2.id = b.secondary_car_id
            LEFT JOIN drivers AS d ON d.id = b.driver_id
            LEFT JOIN operators AS o ON o.id = b.operator_id";

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY b.start_date DESC, b.id DESC';

    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt && $errors === []) {
        if ($params !== []) {
            bind_statement_params($statement, $types, $params);
        }

        $statement->execute();
        $result = $statement->get_result();

        while ($row = $result->fetch_assoc()) {
            $reportBookings[] = $row;
            $reportSummary['total']++;

            if (($row['status'] ?? '') === 'Pending') {
                $reportSummary['pending']++;
            }

            if (($row['status'] ?? '') === 'Confirm') {
                $reportSummary['confirmed']++;
            }

            if (($row['status'] ?? '') === 'In Service') {
                $reportSummary['in_service']++;
            }

            if (($row['status'] ?? '') === 'Completed') {
                $reportSummary['completed']++;
            }

            if (isset($bookingStatusCounts[$row['status']])) {
                $bookingStatusCounts[$row['status']]++;
            }
        }

        $statement->close();
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

<?php if ($errors !== []): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<section class="card-shell section-card mb-4" id="reportsFiltersSection">
    <div class="section-title">
        <div>
            <h2>Report Filters</h2>
            <p>Select a date range to filter the booking report.</p>
        </div>
    </div>

    <form method="get" action="reports.php#reportsResultsSection" class="filter-grid" style="grid-template-columns: 2fr 1fr 1fr auto;" data-preserve-scroll="reportsFiltersSection">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Guest, car, operator, driver, remark">
        </div>
        <div>
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($filters['start_date']) ?>">
        </div>
        <div>
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($filters['end_date']) ?>">
        </div>
        <div class="d-grid align-self-end gap-2 d-md-flex">
            <button type="submit" class="btn btn-shell flex-grow-1">Apply</button>
            <a class="btn btn-outline-secondary" href="reports.php">Reset</a>
        </div>
    </form>
</section>

<section class="overview-grid">
    <div class="card-shell overview-card overview-card-text">
        <span>Report Range</span>
        <strong><?= e($rangeLabel) ?></strong>
        <small>Current booking period</small>
    </div>
    <div class="card-shell overview-card">
        <span>Total Bookings</span>
        <strong><?= e((string) $reportSummary['total']) ?></strong>
        <small>Bookings matching the filters</small>
    </div>
    <div class="card-shell overview-card">
        <span>Pending</span>
        <strong><?= e((string) $reportSummary['pending']) ?></strong>
        <small>Awaiting confirmation</small>
    </div>
    <div class="card-shell overview-card">
        <span>Confirmed</span>
        <strong><?= e((string) $reportSummary['confirmed']) ?></strong>
        <small>Ready to operate</small>
    </div>
    <div class="card-shell overview-card">
        <span>In Service</span>
        <strong><?= e((string) $reportSummary['in_service']) ?></strong>
        <small>Trips running in this report</small>
    </div>
    <div class="card-shell overview-card">
        <span>Completed</span>
        <strong><?= e((string) $reportSummary['completed']) ?></strong>
        <small>Finished trips in this report</small>
    </div>
</section>

<section class="card-shell section-card mt-4" id="reportsResultsSection">
    <div class="section-title">
        <div>
            <h2>Booking Report Results</h2>
            <p>Filtered booking records for <?= e($rangeLabel) ?>.</p>
        </div>
    </div>

    <div class="table-full-content">
        <table class="table data-table align-middle" style="width: 100%; white-space: normal;">
            <thead>
                <tr>
                    <th style="width: 15%;">Guest / Company</th>
                    <th style="width: 15%;">Car</th>
                    <th style="width: 10%;">Operator</th>
                    <th style="width: 15%;">Driver</th>
                    <th style="width: 5%;">E/O</th>
                    <th style="width: 10%;">Dates</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 20%;">Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reportBookings === []): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted-soft">No bookings found for the selected report filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportBookings as $booking): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-wrap" style="line-height: 1.3; font-size: 1.05rem;">
                                    <?= e($booking['guest_company_name']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-start">
                                    <?php foreach (booking_car_entries($booking) as $carLabel): ?>
                                        <span class="table-pill car-pill text-wrap text-start mb-1"><?= e($carLabel) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <span class="table-pill operator-pill text-wrap text-start" style="font-size: 0.85em;">
                                    <?= e(booking_operator_display($booking)) ?>
                                </span>
                            </td>
                            <td>
                                <span class="table-pill driver-pill text-wrap text-start" style="font-size: 0.9em; line-height: 1.2;">
                                    <?= e(booking_driver_display($booking)) ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: var(--muted); font-size: 0.9em;"><?= e($booking['even_odd'] ?: '-') ?></span>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--cocoa); font-size: 0.95em; line-height: 1.2;">
                                    <?= e(format_display_date($booking['start_date'])) ?>
                                </div>
                                <div class="soft-note" style="font-size: 0.85em; margin-top: 2px;">
                                    <?= e(format_display_date($booking['end_date'])) ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span>
                            </td>
                            <td>
                                <div class="text-wrap text-muted" style="font-size: 0.85em; line-height: 1.4;">
                                    <?= e($booking['remark'] ?: '-') ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<br>

<section class="mini-grid">
    <div class="card-shell stack-card">
        <div class="section-title">
            <div>
                <h2>Booking Status Report</h2>
                <p>Status distribution for the current filtered bookings.</p>
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
                <p>Current fleet readiness across all cars.</p>
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
                <p>Current driver availability across the full directory.</p>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
