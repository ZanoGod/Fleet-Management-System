<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'drivers';
$pageTitle = 'Drivers';
$pageSummary = 'View total drivers and maintain their availability for trip assignments.';
$pageActions = '<a class="btn btn-accent" href="driver-create.php">Add Driver</a>';
$pageStyles = ['assets/css/drivers.css'];
$flash = get_flash();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

/*
|--------------------------------------------------------------------------
| SELECTED MONTH
|--------------------------------------------------------------------------
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
        'page' => '1',
    ], static fn(string $value): bool => $value !== '');
    return 'drivers.php?' . http_build_query($query) . '#driversResultsSection';
};

$drivers = [];
$driverAssignments = [];
$stats = [
    'total' => 0,
    'available' => 0,
    'assigned' => 0,
    'leave_count' => 0,
    'inactive' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(
            drivers.full_name LIKE ?
            OR drivers.phone_number LIKE ?
            OR drivers.note LIKE ?
            OR EXISTS (
                SELECT 1
                FROM bookings AS booking_search
                WHERE booking_search.driver_id = drivers.id
                  AND booking_search.status IN (\'Pending\', \'Confirm\')
                  AND booking_search.guest_company_name LIKE ?
            )
        )';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'ssss';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue);
    }

    if ($filters['status'] !== '') {
        $where[] = 'drivers.driver_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    $sql = 'SELECT drivers.id, drivers.full_name, drivers.phone_number, drivers.driver_status FROM drivers';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY full_name ASC';

    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt) {
        if ($params !== []) {
            bind_statement_params($statement, $types, $params);
        }

        $statement->execute();
        $result = $statement->get_result();

        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }

        $statement->close();
    }

    $totalRecords = count($drivers);
    $totalPages = max(1, (int) ceil($totalRecords / $perPage));
    $page = min($page, $totalPages);
    $drivers = array_slice($drivers, ($page - 1) * $perPage, $perPage);

    /* Load historical/current bookings that overlap the selected month. */
    $driverAssignments = [];
    $assignmentStatement = $db->prepare(
        "SELECT
            driver_id,
            start_date,
            end_date,
            guest_company_name,
            status,
            id
         FROM bookings
         WHERE driver_id IS NOT NULL
           AND start_date <= ?
           AND end_date >= ?
           AND status NOT IN ('Cancelled', 'Canceled', 'Cancel')
         ORDER BY start_date ASC, end_date ASC, id ASC"
    );

    if ($assignmentStatement instanceof mysqli_stmt) {
        bind_statement_params(
            $assignmentStatement,
            'ss',
            [$selectedMonthEnd->format('Y-m-d H:i:s'), $selectedMonthStart->format('Y-m-d H:i:s')]
        );
        $assignmentStatement->execute();
        $assignmentResult = $assignmentStatement->get_result();
        while ($assignment = $assignmentResult->fetch_assoc()) {
            $driverId = (int) ($assignment['driver_id'] ?? 0);
            if ($driverId > 0) {
                $driverAssignments[$driverId][] = $assignment;
            }
        }
        $assignmentStatement->close();
    }

    $statsResult = $db->query(
        "SELECT
        COUNT(*) AS total,
        SUM(driver_status = 'Available') AS available,
        SUM(driver_status IN ('Assigned', 'On Trip')) AS assigned,
        SUM(driver_status = 'Leave') AS leave_count,
        SUM(driver_status = 'Inactive') AS inactive
     FROM drivers"
    );

    if ($statsResult instanceof mysqli_result) {
        $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);
    }
}

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/messages.php';
?>


<style>
    .fleet-month-filter {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-left: 4px;
        white-space: nowrap;
    }
    .fleet-month-label {
        font-size: .78rem;
        font-weight: 600;
        color: var(--muted, #8f7a6d);
    }
    .fleet-month-nav {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .fleet-month-arrow,
    .fleet-month-today {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        border: 1px solid rgba(92, 48, 27, .18);
        border-radius: 10px;
        background: #fff;
        color: #5c301b;
        text-decoration: none;
        transition: .15s ease;
    }
    .fleet-month-arrow {
        width: 36px;
        font-size: 1.35rem;
        line-height: 1;
    }
    .fleet-month-arrow:hover,
    .fleet-month-today:hover {
        border-color: #5c301b;
        background: #faf5f0;
        color: #5c301b;
    }
    .fleet-month-value {
        min-width: 118px;
        text-align: center;
        font-weight: 700;
        color: #5c301b;
    }
    .fleet-month-today {
        padding: 0 12px;
        font-size: .84rem;
        font-weight: 600;
    }
    .fleet-month-today.is-current {
        opacity: .5;
        pointer-events: none;
    }
    @media (max-width: 1100px) {
        .fleet-month-filter {
            grid-column: 1 / -1;
            justify-content: flex-start;
            margin-top: 4px;
        }
    }
</style>

<section class="overview-grid">
    <div class="card-shell overview-card">
        <span>Total Drivers</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>All registered drivers</small>
    </div>
    <div class="card-shell overview-card">
        <span>Available</span>
        <strong><?= e((string) ($stats['available'] ?? 0)) ?></strong>
        <small>Ready for booking</small>
    </div>
    <div class="card-shell overview-card">
        <span>Assigned</span>
        <strong><?= e((string) ($stats['assigned'] ?? 0)) ?></strong>
        <small>Currently assigned</small>
    </div>
    <div class="card-shell overview-card">
        <span>On Leave</span>
        <strong><?= e((string) ($stats['leave_count'] ?? 0)) ?></strong>
        <small>Temporarily unavailable</small>
    </div>
</section>

<section class="card-shell section-card mb-4" id="driversFiltersSection">
    <div class="section-title">
        <div>
            <h2>Search Drivers</h2>
            <p>Filter by name, phone number, linked guest/company, or status.</p>
        </div>
    </div>

    <form method="get" action="drivers.php#driversResultsSection" class="filter-grid" data-preserve-scroll="driversFiltersSection">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Driver name, phone, or guest/company">
        </div>
        <div>
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (driver_statuses() as $status): ?>
                    <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="d-grid align-self-end">
            <button type="submit" class="btn btn-shell">Filter</button>
        </div>
        <input type="hidden" name="month" value="<?= e($selectedMonthValue) ?>">
        
        <div class="fleet-month-filter" aria-label="Booking month selector">
            <span class="fleet-month-label">Month</span>
            <div class="fleet-month-nav">
                <a class="fleet-month-arrow" href="<?= e($buildMonthUrl($previousMonth->format('Y-m'))) ?>" aria-label="Previous month" title="Previous month">‹</a>
                <span class="fleet-month-value"><?= e($selectedMonth->format('F Y')) ?></span>
                <a class="fleet-month-arrow" href="<?= e($buildMonthUrl($nextMonth->format('Y-m'))) ?>" aria-label="Next month" title="Next month">›</a>
                <?php if ($selectedMonthValue !== $currentMonthValue): ?>
                    <a class="fleet-month-today" href="<?= e($buildMonthUrl($currentMonthValue)) ?>">Today</a>
                <?php else: ?>
                    <span class="fleet-month-today is-current">Today</span>
                <?php endif; ?>
            </div>
        </div>
    </form>
</section>

<section class="card-shell section-card" id="driversResultsSection">
    <div class="section-title">
        <div>
            <h2>Driver List</h2>
            <p>Keep driver records ready for daily fleet operations with bookings for <?= e($selectedMonth->format('F Y')) ?>.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table drivers-table align-middle">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Phone Number</th>
                    <th>Status</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Guest / Company</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($drivers === []): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted-soft">No drivers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($drivers as $driver): ?>
                        <?php
                        $assignments = $driverAssignments[(int) $driver['id']] ?? [];
                        $showAssignmentIndex = count($assignments) > 1;
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e($driver['full_name']) ?></td>
                            <td><?= e($driver['phone_number']) ?></td>
                            <td><span class="resource-pill <?= e(driver_status_class($driver['driver_status'])) ?>"><?= e($driver['driver_status']) ?></span></td>
                            <td>
                                <?php if ($assignments === []): ?>
                                    <span class="assignment-empty">-</span>
                                <?php else: ?>
                                    <div class="assignment-list">
                                        <?php foreach ($assignments as $index => $assignment): ?>
                                            <div class="assignment-line">
                                                <?php if ($showAssignmentIndex): ?>
                                                    <span class="assignment-index"><?= e((string) ($index + 1)) ?></span>
                                                <?php endif; ?>
                                                <span><?= e(format_display_date($assignment['start_date'] ?? null)) ?></span>
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
                                                    <span class="assignment-index"><?= e((string) ($index + 1)) ?></span>
                                                <?php endif; ?>
                                                <span><?= e(format_display_date($assignment['end_date'] ?? null)) ?></span>
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
                                                    <span class="assignment-index"><?= e((string) ($index + 1)) ?></span>
                                                <?php endif; ?>
                                                <span><?= e($assignment['guest_company_name'] ?? '-') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <a class="btn btn-sm btn-shell" href="driver-edit.php?id=<?= e((string) $driver['id']) ?>">Edit</a>
                                    <form method="post" action="driver-delete.php?id=<?= e((string) $driver['id']) ?>" onsubmit="return confirm('Delete this driver?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
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
        <nav class="monthly-pagination fleet-pagination mt-3" aria-label="Driver pagination">
            <ul class="pagination justify-content-end mb-0 flex-wrap">
                <?php if ($page > 1): ?>
                    <?php $paginationQuery['page'] = (string) ($page - 1); ?>
                    <li class="page-item">
                        <a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#driversResultsSection">Previous</a>
                    </li>
                <?php endif; ?>

                <?php
                $paginationStart = max(1, $page - 2);
                $paginationEnd = min($totalPages, $page + 2);
                ?>

                <?php if ($paginationStart > 1): ?>
                    <?php $paginationQuery['page'] = '1'; ?>
                    <li class="page-item"><a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#driversResultsSection">1</a></li>
                    <?php if ($paginationStart > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $paginationStart; $i <= $paginationEnd; $i++): ?>
                    <?php $paginationQuery['page'] = (string) $i; ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#driversResultsSection">
                            <?= e((string) $i) ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($paginationEnd < $totalPages): ?>
                    <?php if ($paginationEnd < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <?php $paginationQuery['page'] = (string) $totalPages; ?>
                    <li class="page-item"><a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#driversResultsSection"><?= e((string) $totalPages) ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
