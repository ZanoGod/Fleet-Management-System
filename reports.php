<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'reports';
$pageTitle = 'Reports';
$pageSummary = 'Filter booking activity by weekly, monthly, or custom date ranges and review operations at a glance.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">Open Bookings</a>';
$flash = get_flash();

$reportRangeTypes = ['monthly', 'weekly', 'manual'];
$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'range_type' => trim((string) ($_GET['range_type'] ?? 'monthly')),
    'calendar_date' => trim((string) ($_GET['calendar_date'] ?? date('Y-m-d'))),
    'start_date' => trim((string) ($_GET['start_date'] ?? date('Y-m-01'))),
    'end_date' => trim((string) ($_GET['end_date'] ?? date('Y-m-t'))),
];

if (!in_allowed_values($filters['range_type'], $reportRangeTypes)) {
    $filters['range_type'] = 'monthly';
}

$bookingStatusCounts = array_fill_keys(booking_statuses(), 0);
$carStatusCounts = array_fill_keys(car_statuses(), 0);
$driverStatusCounts = array_fill_keys(driver_statuses(), 0);
$reportBookings = [];
$reportSummary = [
    'total' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
];
$errors = [];
$rangeLabel = 'All booking dates';
$dateFrom = null;
$dateTo = null;

try {
    if ($filters['range_type'] === 'manual') {
        if ($filters['start_date'] === '' || $filters['end_date'] === '') {
            $errors[] = 'Please choose both start and end dates for a manual report.';
        } else {
            $startDate = new DateTimeImmutable($filters['start_date']);
            $endDate = new DateTimeImmutable($filters['end_date']);

            if ($endDate < $startDate) {
                $errors[] = 'Manual end date cannot be earlier than the start date.';
            } else {
                $dateFrom = $startDate->format('Y-m-d');
                $dateTo = $endDate->format('Y-m-d');
                $rangeLabel = $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y');
            }
        }
    } elseif ($filters['calendar_date'] !== '') {
        $calendarDate = new DateTimeImmutable($filters['calendar_date']);

        if ($filters['range_type'] === 'weekly') {
            $weekStart = $calendarDate->modify('monday this week');
            $weekEnd = $weekStart->modify('+6 days');
            $dateFrom = $weekStart->format('Y-m-d');
            $dateTo = $weekEnd->format('Y-m-d');
            $rangeLabel = $weekStart->format('d M Y') . ' to ' . $weekEnd->format('d M Y');
        } else {
            $monthStart = $calendarDate->modify('first day of this month');
            $monthEnd = $calendarDate->modify('last day of this month');
            $dateFrom = $monthStart->format('Y-m-d');
            $dateTo = $monthEnd->format('Y-m-d');
            $rangeLabel = $monthStart->format('F Y');
        }
    }
} catch (Throwable $throwable) {
    $errors[] = 'Please choose a valid report date.';
}

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(b.guest_company_name LIKE ? OR b.operator_name LIKE ? OR o.full_name LIKE ? OR c.car_type LIKE ? OR c.plate_no LIKE ? OR b.custom_car_name LIKE ? OR d.full_name LIKE ? OR b.custom_driver_name LIKE ? OR b.even_odd LIKE ? OR b.remark LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'ssssssssss';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue);
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
                b.even_odd,
                b.start_date,
                b.end_date,
                b.status,
                b.remark,
                c.car_type,
                c.plate_no,
                d.full_name AS driver_name,
                o.full_name AS operator_full_name
            FROM bookings AS b
            LEFT JOIN cars AS c ON c.id = b.car_id
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

<section class="card-shell section-card mb-4">
    <div class="section-title">
        <div>
            <h2>Report Filters</h2>
            <p>Use monthly, weekly, or manual dates with search to narrow the booking report.</p>
        </div>
    </div>

    <form method="get" class="filter-grid reports-filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Guest, car, operator, driver, remark">
        </div>
        <div>
            <label for="range_type" class="form-label">Range Type</label>
            <select class="form-select" id="range_type" name="range_type">
                <option value="monthly" <?= selected($filters['range_type'], 'monthly') ?>>Monthly</option>
                <option value="weekly" <?= selected($filters['range_type'], 'weekly') ?>>Weekly</option>
                <option value="manual" <?= selected($filters['range_type'], 'manual') ?>>Manual</option>
            </select>
        </div>
        <div>
            <label for="calendar_date" class="form-label">Calendar Date</label>
            <input type="date" class="form-control" id="calendar_date" name="calendar_date" value="<?= e($filters['calendar_date']) ?>">
        </div>
        <div>
            <label for="start_date" class="form-label">Manual Start</label>
            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($filters['start_date']) ?>">
        </div>
        <div>
            <label for="end_date" class="form-label">Manual End</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($filters['end_date']) ?>">
        </div>
        <div class="d-grid align-self-end gap-2">
            <button type="submit" class="btn btn-shell">Apply</button>
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
        <span>Completed</span>
        <strong><?= e((string) $reportSummary['completed']) ?></strong>
        <small>Finished trips in this report</small>
    </div>
</section>

<section class="card-shell section-card mt-4">
    <div class="section-title">
        <div>
            <h2>Booking Report Results</h2>
            <p>Filtered booking records for <?= e($rangeLabel) ?>.</p>
        </div>
    </div>

    <div class="table-responsive table-full-content">
        <table class="table data-table table-readable">
            <thead>
                <tr>
                    <th>Guest / Company</th>
                    <th>Car</th>
                    <th>Operator</th>
                    <th>Driver</th>
                    <th>Even / Odd</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reportBookings === []): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted-soft">No bookings found for the selected report filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reportBookings as $booking): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($booking['guest_company_name']) ?></td>
                            <td><span class="table-pill car-pill"><?= e(booking_car_display($booking)) ?></span></td>
                            <td><span class="table-pill operator-pill"><?= e(booking_operator_display($booking)) ?></span></td>
                            <td><span class="table-pill driver-pill"><?= e(booking_driver_display($booking)) ?></span></td>
                            <td><?= e($booking['even_odd'] ?: '-') ?></td>
                            <td><?= e(format_display_date($booking['start_date'])) ?></td>
                            <td><?= e(format_display_date($booking['end_date'])) ?></td>
                            <td><span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span></td>
                            <td class="remark-cell"><?= e($booking['remark'] ?: '-') ?></td>
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
