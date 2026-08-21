<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'cars';
$pageTitle = 'Fleet Management';
$pageSummary = 'Manage all company vehicles and booking assignments.';
$pageActions = '<a class="btn btn-shell" href="monthly-contracts.php">Monthly Contract</a> <a class="btn btn-accent" href="car-create.php">Add Vehicle</a>';
$pageStyles = ['assets/css/cars.css'];

$flash = get_flash();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

/*
|--------------------------------------------------------------------------
| SELECTED MONTH
|--------------------------------------------------------------------------
| The month selector controls which booking assignments are displayed.
| It defaults to the current month and also allows historical months.
*/
$selectedMonthValue = trim((string) ($_GET['month'] ?? ''));
$selectedMonth = DateTimeImmutable::createFromFormat('!Y-m', $selectedMonthValue);

if (!$selectedMonth || $selectedMonth->format('Y-m') !== $selectedMonthValue) {
    $selectedMonth = new DateTimeImmutable('first day of this month');
}

$selectedMonthValue = $selectedMonth->format('Y-m');
$selectedMonthStart = $selectedMonth->modify('first day of this month')->setTime(0, 0, 0);
$selectedMonthEnd = $selectedMonth->modify('last day of this month')->setTime(23, 59, 59);
$previousMonth = $selectedMonth->modify('-1 month');
$nextMonth = $selectedMonth->modify('+1 month');
$currentMonthValue = (new DateTimeImmutable('first day of this month'))->format('Y-m');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalRecords = 0;
$totalPages = 1;

$buildMonthUrl = static function (string $month) use ($filters): string {
    $query = array_filter([
        'search' => $filters['search'],
        'status' => $filters['status'],
        'month' => $month,
    ], static fn(string $value): bool => $value !== '');

    return 'cars.php?' . http_build_query($query) . '#carsResultsSection';
};

$cars = [];
$carAssignments = [];

$stats = [
    'total' => 0,
    'available' => 0,
    'assigned' => 0,
    'maintenance_count' => 0,
];

if ($db instanceof mysqli) {

    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {

        $where[] = '(
            cars.car_type LIKE ?
            OR cars.plate_no LIKE ?
            OR cars.model_name LIKE ?
            OR EXISTS (
                SELECT 1
                FROM bookings AS booking_search
                WHERE booking_search.car_id = cars.id
                  AND booking_search.status IN (\'Pending\', \'Confirm\')
                  AND booking_search.guest_company_name LIKE ?
            )
        )';

        $searchValue = '%' . $filters['search'] . '%';

        $types .= 'ssss';

        array_push(
            $params,
            $searchValue,
            $searchValue,
            $searchValue,
            $searchValue
        );
    }

    if ($filters['status'] !== '') {

        $where[] = 'cars.availability_status = ?';

        $types .= 's';

        $params[] = $filters['status'];
    }

    $sql = '
        SELECT
            cars.id,
            cars.car_type,
            cars.plate_no,
            cars.model_name,
            cars.seat_capacity,
            cars.availability_status
        FROM cars
    ';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY cars.id DESC';

    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt) {

        if ($params !== []) {
            bind_statement_params($statement, $types, $params);
        }

        $statement->execute();

        $result = $statement->get_result();

        while ($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }

        $statement->close();
    }

    $totalRecords = count($cars);
    $totalPages = max(1, (int) ceil($totalRecords / $perPage));
    $page = min($page, $totalPages);
    $cars = array_slice($cars, ($page - 1) * $perPage, $perPage);

    /*
    |--------------------------------------------------------------------------
    | BOOKING ASSIGNMENTS FOR SELECTED MONTH
    |--------------------------------------------------------------------------
    | Load bookings that overlap the selected month. Unlike the old
    | active-booking helper, this also keeps completed historical bookings
    | available when the user moves back to a past month.
    */

    $carAssignments = [];

    $assignmentStatement = $db->prepare(
        "SELECT
            car_id,
            start_date,
            end_date,
            guest_company_name,
            status,
            id
         FROM bookings
         WHERE car_id IS NOT NULL
           AND start_date <= ?
           AND end_date >= ?
           AND status NOT IN ('Cancelled', 'Canceled', 'Cancel')
         ORDER BY start_date ASC, end_date ASC, id ASC"
    );

    if ($assignmentStatement instanceof mysqli_stmt) {
        $monthEndSql = $selectedMonthEnd->format('Y-m-d H:i:s');
        $monthStartSql = $selectedMonthStart->format('Y-m-d H:i:s');

        bind_statement_params(
            $assignmentStatement,
            'ss',
            [$monthEndSql, $monthStartSql]
        );

        $assignmentStatement->execute();
        $assignmentResult = $assignmentStatement->get_result();

        while ($assignment = $assignmentResult->fetch_assoc()) {
            $carId = (int) ($assignment['car_id'] ?? 0);

            if ($carId <= 0) {
                continue;
            }

            $carAssignments[$carId][] = $assignment;
        }

        $assignmentStatement->close();
    }

    /*
    |--------------------------------------------------------------------------
    | STATISTICS
    |--------------------------------------------------------------------------
    */

    $statsResult = $db->query(
        "
        SELECT
            COUNT(*) AS total,
            SUM(availability_status = 'Available') AS available,
            SUM(availability_status = 'Assigned') AS assigned,
            SUM(availability_status = 'Maintenance') AS maintenance_count
        FROM cars
        "
    );

    if ($statsResult instanceof mysqli_result) {

        $stats = array_merge(
            $stats,
            $statsResult->fetch_assoc() ?: []
        );
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>

<style>
    .fleet-month-filter {
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        gap: 6px;
        min-width: 245px;
    }

    .fleet-month-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #765848;
    }

    .fleet-month-nav {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
    }

    .fleet-month-arrow,
    .fleet-month-today {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        border: 1px solid #d8c2b4;
        border-radius: 10px;
        background: #fff;
        color: #5c2d18;
        text-decoration: none;
        font-weight: 600;
        transition: background-color .15s ease, border-color .15s ease;
    }

    .fleet-month-arrow {
        width: 38px;
        font-size: 1.35rem;
        line-height: 1;
    }

    .fleet-month-arrow:hover,
    .fleet-month-today:hover {
        background: #fbf4ef;
        border-color: #a56b4d;
        color: #5c2d18;
    }

    .fleet-month-value {
        min-width: 118px;
        text-align: center;
        color: #4c3429;
        font-weight: 700;
        white-space: nowrap;
    }

    .fleet-month-today {
        padding: 0 12px;
        margin-left: 2px;
        font-size: .85rem;
    }

    .fleet-month-today.is-current {
        opacity: .55;
        cursor: default;
    }

    @media (max-width: 991.98px) {
        .fleet-month-filter {
            min-width: 0;
        }
    }
</style>

<section class="overview-grid">

    <div class="card-shell overview-card">
        <span>Total Vehicles</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>All registered company vehicles</small>
    </div>

    <div class="card-shell overview-card">
        <span>Available</span>
        <strong><?= e((string) ($stats['available'] ?? 0)) ?></strong>
        <small>Ready for assignment</small>
    </div>

    <div class="card-shell overview-card">
        <span>Assigned</span>
        <strong><?= e((string) ($stats['assigned'] ?? 0)) ?></strong>
        <small>Currently booked</small>
    </div>

    <div class="card-shell overview-card">
        <span>Maintenance</span>
        <strong><?= e((string) ($stats['maintenance_count'] ?? 0)) ?></strong>
        <small>Under maintenance</small>
    </div>

</section>

<section class="card-shell section-card mb-4" id="carsFiltersSection">
    <div class="section-title">
        <div>
            <h2>Search Fleet</h2>
            <p>Filter by car type, plate number, model, guest/company, or status.</p>
        </div>
    </div>

<form method="get" action="cars.php#carsResultsSection" class="filter-grid" data-preserve-scroll="carsFiltersSection">

        <input type="hidden" name="month" value="<?= e($selectedMonthValue) ?>">

        <div>
            <label for="search" class="form-label">Search</label>

            <input
                type="text"
                class="form-control"
                id="search"
                name="search"
                value="<?= e($filters['search']) ?>"
                placeholder="Car type, plate number, model, or guest/company">
        </div>

        <div>
            <label for="status" class="form-label">Status</label>

            <select class="form-select" id="status" name="status">

                <option value="">All Statuses</option>

                <option value="Available" <?= selected($filters['status'], 'Available') ?>>
                    Available
                </option>

                <option value="Assigned" <?= selected($filters['status'], 'Assigned') ?>>
                    Assigned
                </option>

                <option value="Maintenance" <?= selected($filters['status'], 'Maintenance') ?>>
                    Maintenance
                </option>

            </select>
        </div>

        <div class="d-grid align-self-end">
            <button type="submit" class="btn btn-shell">
                Filter
            </button>
        </div>

        <div class="fleet-month-filter" aria-label="Booking month selector">
            <span class="fleet-month-label">Month</span>
            <div class="fleet-month-nav">
                <a
                    class="fleet-month-arrow"
                    href="<?= e($buildMonthUrl($previousMonth->format('Y-m'))) ?>"
                    aria-label="Previous month"
                    title="Previous month">‹</a>

                <span class="fleet-month-value">
                    <?= e($selectedMonth->format('F Y')) ?>
                </span>

                <a
                    class="fleet-month-arrow"
                    href="<?= e($buildMonthUrl($nextMonth->format('Y-m'))) ?>"
                    aria-label="Next month"
                    title="Next month">›</a>

                <?php if ($selectedMonthValue !== $currentMonthValue): ?>
                    <a
                        class="fleet-month-today"
                        href="<?= e($buildMonthUrl($currentMonthValue)) ?>">Today</a>
                <?php else: ?>
                    <span class="fleet-month-today is-current">Today</span>
                <?php endif; ?>
            </div>
        </div>

    </form>

</section>

<section class="card-shell section-card" id="carsResultsSection">
    <div class="section-title">
        <div>
            <h2>Fleet List</h2>
            <p>View vehicle availability and booking schedules for <?= e($selectedMonth->format('F Y')) ?>.</p>
        </div>
    </div>

    <div class="table-responsive">

        <table class="table data-table cars-table align-middle">

            <thead>
                <tr>
                    <th>Car Type</th>
                    <th>Plate Number</th>
                    <th>Model</th>
                    <th>Seat Capacity</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Guest / Company</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($cars === []): ?>

                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted-soft">
                            No vehicles found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($cars as $car): ?>

                        <?php
                        $assignments = $carAssignments[(int) $car['id']] ?? [];
                        $showAssignmentIndex = count($assignments) > 1;
                        ?>

                        <tr>

                            <td class="fw-semibold">
                                <?= e($car['car_type']) ?>
                            </td>

                            <td>
                                <?= e($car['plate_no']) ?>
                            </td>

                            <td>
                                <?= e($car['model_name']) ?>
                            </td>

                            <td>
                                <?= e((string) $car['seat_capacity']) ?>
                            </td>

                            <td>
                                <span class="resource-pill <?= e(vehicle_status_class($car['availability_status'])) ?>">
                                    <?= e($car['availability_status']) ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($assignments === []): ?>

                                    <span class="assignment-empty">-</span>

                                <?php else: ?>

                                    <div class="assignment-list">

                                        <?php foreach ($assignments as $index => $assignment): ?>

                                            <div class="assignment-line">

                                                <?php if ($showAssignmentIndex): ?>
                                                    <span class="assignment-index">
                                                        <?= e((string) ($index + 1)) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <span>
                                                    <?= e(format_display_date($assignment['start_date'] ?? null)) ?>
                                                </span>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($assignments === []): ?>

                                    <span class="assignment-empty">-</span>

                                <?php else: ?>

                                    <div class="assignment-list">

                                        <?php foreach ($assignments as $index => $assignment): ?>

                                            <div class="assignment-line">

                                                <?php if ($showAssignmentIndex): ?>
                                                    <span class="assignment-index">
                                                        <?= e((string) ($index + 1)) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <span>
                                                    <?= e(format_display_date($assignment['end_date'] ?? null)) ?>
                                                </span>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($assignments === []): ?>

                                    <span class="assignment-empty">-</span>

                                <?php else: ?>

                                    <div class="assignment-list">

                                        <?php foreach ($assignments as $index => $assignment): ?>

                                            <div class="assignment-line assignment-line-guest">

                                                <?php if ($showAssignmentIndex): ?>
                                                    <span class="assignment-index">
                                                        <?= e((string) ($index + 1)) ?>
                                                    </span>
                                                <?php endif; ?>

                                                <span>
                                                    <?= e($assignment['guest_company_name'] ?? '-') ?>
                                                </span>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php endif; ?>

                            </td>

                            <td class="text-center">

                                <div class="d-flex justify-content-center gap-2 flex-wrap">

                                    <a
                                        class="btn btn-sm btn-shell"
                                        href="car-edit.php?id=<?= e((string) $car['id']) ?>">
                                        Edit
                                    </a>

                                    <form
                                        method="post"
                                        action="car-delete.php?id=<?= e((string) $car['id']) ?>"
                                        onsubmit="return confirm('Delete this vehicle?');">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger">
                                            Delete
                                        </button>
                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <?php if ($totalPages > 1): ?>
        <?php
        $paginationQuery = array_filter([
            'search' => $filters['search'],
            'status' => $filters['status'],
            'month' => $selectedMonthValue,
        ], static fn(string $value): bool => $value !== '');
        ?>
        <nav class="monthly-pagination fleet-pagination mt-3" aria-label="Fleet pagination">
            <ul class="pagination justify-content-end mb-0 flex-wrap">
                <?php if ($page > 1): ?>
                    <?php $paginationQuery['page'] = (string) ($page - 1); ?>
                    <li class="page-item">
                        <a class="page-link" href="cars.php?<?= e(http_build_query($paginationQuery)) ?>#carsResultsSection">Previous</a>
                    </li>
                <?php endif; ?>

                <?php
                $paginationStart = max(1, $page - 2);
                $paginationEnd = min($totalPages, $page + 2);
                ?>

                <?php if ($paginationStart > 1): ?>
                    <?php $paginationQuery['page'] = '1'; ?>
                    <li class="page-item"><a class="page-link" href="cars.php?<?= e(http_build_query($paginationQuery)) ?>#carsResultsSection">1</a></li>
                    <?php if ($paginationStart > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $paginationStart; $i <= $paginationEnd; $i++): ?>
                    <?php $paginationQuery['page'] = (string) $i; ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="cars.php?<?= e(http_build_query($paginationQuery)) ?>#carsResultsSection"><?= e((string) $i) ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($paginationEnd < $totalPages): ?>
                    <?php if ($paginationEnd < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <?php $paginationQuery['page'] = (string) $totalPages; ?>
                    <li class="page-item"><a class="page-link" href="cars.php?<?= e(http_build_query($paginationQuery)) ?>#carsResultsSection"><?= e((string) $totalPages) ?></a></li>
                <?php endif; ?>

            </ul>
        </nav>
    <?php endif; ?>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
