<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'reports';
$pageTitle = 'Reports';
$pageSummary = 'Filter booking activity by custom date ranges and review operations at a glance.';
$pageActions = '<a class="btn btn-shell" href="bookings.php">Open Bookings</a>';
$pageStyles = ['assets/css/reports.css'];
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


$carUtilization = [];

if ($db instanceof mysqli && $errors === []) {

    $sql = "
    SELECT
        c.id,
        CONCAT(c.car_type,' (',c.plate_no,')') AS car_name,

        COUNT(
            CASE
                WHEN b.car_id = c.id
                OR b.secondary_car_id = c.id
                THEN 1
            END
        ) AS total_bookings,

        SUM(
            CASE
                WHEN b.car_id = c.id
                OR b.secondary_car_id = c.id
                THEN DATEDIFF(b.end_date,b.start_date)+1
                ELSE 0
            END
        ) AS total_days

    FROM cars c

    LEFT JOIN bookings b
        ON (
            (b.car_id=c.id OR b.secondary_car_id=c.id)
            AND b.start_date<=?
            AND b.end_date>=?
        )

    GROUP BY c.id

    ORDER BY total_bookings DESC
    ";

    $stmt = $db->prepare($sql);

    $stmt->bind_param("ss", $dateTo, $dateFrom);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $days = max(
            1,
            (strtotime($dateTo) - strtotime($dateFrom)) / 86400 + 1
        );

        $row['utilization'] = round(
            ($row['total_days'] / $days) * 100,
            1
        );

        $carUtilization[] = $row;
    }

    $stmt->close();
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

    <form method="get" action="reports.php#reportsResultsSection" class="filter-grid report-filter-grid" data-preserve-scroll="reportsFiltersSection">
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


<section class="mini-grid report-metrics-grid">
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

    <!-- fleet utilization section -->
    <div class="card-shell stack-card">

        <div class="section-title">
            <div>
                <h2>Fleet Utilization</h2>
                <p>Most frequently used vehicles</p>
            </div>
        </div>

        <?php
        $maxBookings = max(1, max(array_column($carUtilization, 'total_bookings')));
        ?>

        <?php foreach (array_slice($carUtilization, 0, 5) as $car): ?>

            <div class="metric-row">

                <div>

                    <strong><?= e($car['car_name']) ?></strong>

                    <div class="bar-track">

                        <div
                            class="bar-fill"
                            style="width:<?= round(($car['total_bookings'] / $maxBookings) * 100) ?>%">
                        </div>

                    </div>

                </div>

                <span>

                    <?= $car['total_bookings'] ?> Trips

                </span>

            </div>

        <?php endforeach; ?>

    </div>

    <!--Fleet Pie Chart-->
    <div class="card-shell stack-card">
        <div class="section-title">
            <div>
                <h2>Vehicle Distribution</h2>
                <p>Booking share by vehicle</p>
            </div>
        </div>

        <div style="height:300px">
            <canvas id="fleetPie"></canvas>
        </div>
    </div>

</section>

<br>
<div class="report-print-area" id="reportPrintableArea" data-report-title="Booking Report - <?= e($rangeLabel) ?>">
    <div class="report-print-header">
        <span>GSS Fleet Management</span>
        <h2>Booking Report</h2>
        <p><?= e($rangeLabel) ?> | Generated <?= e(date('d M Y H:i')) ?></p>
    </div>

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

    <section class="card-shell section-card mt-4" id="reportsResultsSection">
        <div class="section-title">
            <div>
                <h2>Booking Report Results</h2>
                <p>Filtered booking records for <?= e($rangeLabel) ?>.</p>
            </div>
            <div class="report-actions no-print">
                <button type="button" class="btn btn-accent btn-sm" data-report-export="pdf" title="Open the print dialog and choose Save as PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Export PDF
                </button>
                <button type="button" class="btn btn-shell btn-sm" data-report-export="print">
                    <i class="bi bi-printer"></i>
                    Print
                </button>
            </div>
        </div>

        <div class="report-table-shell">
            <table class="table data-table report-table align-middle">
                <colgroup>
                    <col class="report-col-guest">
                    <col class="report-col-car">
                    <col class="report-col-operator">
                    <col class="report-col-driver">
                    <col class="report-col-even-odd">
                    <col class="report-col-dates">
                    <col class="report-col-status">
                    <col class="report-col-remark">
                </colgroup>
                <thead>
                    <tr>
                        <th>Guest / Company</th>
                        <th>Car</th>
                        <th>Operator</th>
                        <th>Driver</th>
                        <th>E/O</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Remark</th>
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
                                <td data-label="Guest / Company">
                                    <div class="report-guest">
                                        <?= e($booking['guest_company_name']) ?>
                                    </div>
                                </td>
                                <td data-label="Car">
                                    <div class="report-pill-list">
                                        <?php foreach (booking_car_entries($booking) as $carLabel): ?>
                                            <span class="table-pill car-pill report-pill"><?= e($carLabel) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td data-label="Operator">
                                    <span class="table-pill operator-pill report-pill">
                                        <?= e(booking_operator_display($booking)) ?>
                                    </span>
                                </td>
                                <td data-label="Driver">
                                    <span class="table-pill driver-pill report-pill">
                                        <?= e(booking_driver_display($booking)) ?>
                                    </span>
                                </td>
                                <td data-label="E/O">
                                    <span class="report-even-odd"><?= e($booking['even_odd'] ?: '-') ?></span>
                                </td>
                                <td data-label="Dates">
                                    <div class="report-date-primary">
                                        <?= e(format_display_date($booking['start_date'])) ?>
                                    </div>
                                    <div class="soft-note report-date-secondary">
                                        <?= e(format_display_date($booking['end_date'])) ?>
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <span class="status-pill <?= e(status_badge_class($booking['status'])) ?>"><?= e($booking['status']) ?></span>
                                </td>
                                <td data-label="Remark">
                                    <div class="report-remark">
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



</div>


<?php
$topVehicles = array_slice($carUtilization, 0, 5);
$fleetLabels = array_column($topVehicles, 'car_name');
$fleetData   = array_column($topVehicles, 'total_bookings');

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const ctx = document.getElementById("fleetPie");

        if (!ctx) return;

        const fleetLabels = <?= json_encode($fleetLabels) ?>;
        const fleetData = <?= json_encode($fleetData) ?>;

        new Chart(ctx, {
            type: 'doughnut',

            data: {
                labels: fleetLabels,
                datasets: [{
                    data: fleetData,
                    borderWidth: 2
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

    });
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>