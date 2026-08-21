<?php

declare(strict_types=1);


require_once __DIR__ . '/../includes/bootstrap.php';

$activePage = 'monthly-drivers';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Fleet Management';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'GSS';
$pageKicker = 'Monthly Contracts';
$pageTitle = 'Drivers';
$pageSummary = 'Maintain drivers reserved for monthly contracts separately from daily booking drivers.';
$pageActions = '<a class="btn btn-accent" href="driver_create.php">Add Driver</a>';
$pageStyles = ['assets/css/drivers.css', 'assets/css/monthly-contracts.css'];
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
$buildMonthUrl = static function (string $month) use ($filters): string {
    $query = array_filter([
        'search' => $filters['search'],
        'status' => $filters['status'],
        'month' => $month,
        'page' => '1',
    ], static fn(string $value): bool => $value !== '');
    return 'drivers.php?' . http_build_query($query) . '#monthlyDriversResultsSection';
};

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$drivers = [];
$driverAssignments = [];
$stats = [
    'total' => 0,
    'available' => 0,
    'assigned' => 0,
    'leave_count' => 0,
    'inactive' => 0,
];


$filterQuery = array_filter(
    array_merge($filters, ['month' => $selectedMonthValue]),
    static fn(string $value): bool => $value !== ''
);

$totalRecords = 0;
$totalPages = 1;
$offset = 0;

if ($db instanceof mysqli) {

    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(
            monthly_drivers.full_name LIKE ?
            OR monthly_drivers.phone_number LIKE ?
            OR monthly_drivers.note LIKE ?
            OR EXISTS (
                SELECT 1
                FROM monthly_contracts AS contract_search
                WHERE contract_search.driver_id = monthly_drivers.id
                  AND contract_search.status IN (\'Draft\', \'Active\')
                  AND (
                    contract_search.customer_name LIKE ?
                    OR contract_search.contract_no LIKE ?
                  )
            )
        )';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'sssss';
        array_push($params, $searchValue, $searchValue, $searchValue, $searchValue, $searchValue);
    }

    if ($filters['status'] !== '') {
        $where[] = 'monthly_drivers.driver_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    // Server-side pagination: count matching records and fetch only 10 rows.
    $countSql = 'SELECT COUNT(*) AS total FROM monthly_drivers';

    if ($where !== []) {
        $countSql .= ' WHERE ' . implode(' AND ', $where);
    }

    $countStatement = $db->prepare($countSql);

    if ($countStatement instanceof mysqli_stmt) {
        if ($params !== []) {
            bind_statement_params($countStatement, $types, $params);
        }

        $countStatement->execute();
        $countRow = $countStatement->get_result()->fetch_assoc() ?: [];
        $totalRecords = (int) ($countRow['total'] ?? 0);
        $countStatement->close();
    }

    $totalPages = max(1, (int) ceil($totalRecords / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $sql = 'SELECT
                monthly_drivers.id,
                monthly_drivers.full_name,
                monthly_drivers.phone_number,
                monthly_drivers.driver_status
            FROM monthly_drivers';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY monthly_drivers.full_name ASC LIMIT ? OFFSET ?';

    $statement = $db->prepare($sql);

    if ($statement instanceof mysqli_stmt) {
        $queryTypes = $types . 'ii';
        $queryParams = $params;
        $queryParams[] = $perPage;
        $queryParams[] = $offset;

        bind_statement_params($statement, $queryTypes, $queryParams);

        $statement->execute();
        $result = $statement->get_result();

        while ($row = $result->fetch_assoc()) {
            $drivers[] = $row;
        }

        $statement->close();
    }

    /* Load monthly contracts that overlap the selected month. */
    $driverAssignments = [];
    $assignmentStatement = $db->prepare(
        "SELECT
            driver_id,
            start_date,
            end_date,
            contract_no,
            customer_name,
            status,
            id
         FROM monthly_contracts
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
            SUM(driver_status = 'Assigned') AS assigned,
            SUM(driver_status = 'Leave') AS leave_count,
            SUM(driver_status = 'Inactive') AS inactive
         FROM monthly_drivers"
    );

    if ($statsResult instanceof mysqli_result) {
        $stats = array_merge($stats, $statsResult->fetch_assoc() ?: []);
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/messages.php';
?>




<section class="overview-grid">
    <div class="card-shell overview-card">
        <span>Total Drivers</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>Monthly driver records</small>
    </div>
    <div class="card-shell overview-card">
        <span>Available</span>
        <strong><?= e((string) ($stats['available'] ?? 0)) ?></strong>
        <small>Ready for monthly assignment</small>
    </div>
    <div class="card-shell overview-card">
        <span>Assigned</span>
        <strong><?= e((string) ($stats['assigned'] ?? 0)) ?></strong>
        <small>Linked to monthly contracts</small>
    </div>
    <div class="card-shell overview-card">
        <span>On Leave</span>
        <strong><?= e((string) ($stats['leave_count'] ?? 0)) ?></strong>
        <small>Temporarily unavailable</small>
    </div>
</section>

<section class="card-shell section-card mb-4" id="monthlyDriversFiltersSection">
    <div class="section-title">
        <div>
            <h2>Search Monthly Drivers</h2>
            <p>Filter by driver name, phone number, monthly customer, contract number, or status.</p>
        </div>
    </div>

    <form method="get" action="drivers.php#monthlyDriversResultsSection" class="filter-grid" data-preserve-scroll="monthlyDriversFiltersSection">
        <div>
            <label for="search" class="form-label">Search</label>
            <input
                type="text"
                class="form-control"
                id="search"
                name="search"
                value="<?= e($filters['search']) ?>"
                placeholder="Driver, phone, customer, contract">
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

<section class="card-shell section-card" id="monthlyDriversResultsSection">
    <div class="section-title">
        <div>
            <h2>Monthly Driver List</h2>
            <p><?= e((string) $totalRecords) ?> driver records found. Showing 10 drivers per page for <?= e($selectedMonth->format('F Y')) ?>.</p>
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
                    <th>Contract / Customer</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($drivers === []): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted-soft">No monthly drivers found.</td>
                    </tr>
                <?php else: ?>
                    <?php $rowNo = $offset + 1; ?>
                    <?php foreach ($drivers as $driver): ?>
                        <?php
                        $assignments = $driverAssignments[(int) $driver['id']] ?? [];
                        $showAssignmentIndex = count($assignments) > 1;
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) $driver['full_name']) ?></td>
                            <td><?= e((string) $driver['phone_number']) ?></td>
                            <td>
                                <span class="resource-pill <?= e(driver_status_class((string) $driver['driver_status'])) ?>">
                                    <?= e((string) $driver['driver_status']) ?>
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
                                                <span><?= e((string) ($assignment['contract_no'] ?? '-')) ?> / <?= e((string) ($assignment['customer_name'] ?? '-')) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <a class="btn btn-sm btn-shell" href="driver_edit.php?id=<?= e((string) $driver['id']) ?>">Edit</a>
                                    <form
                                        method="post"
                                        action="driver_delete.php?id=<?= e((string) $driver['id']) ?>"
                                        onsubmit="return confirm('Delete this monthly driver?');">
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
        $paginationQuery = $filterQuery;
        ?>
        <nav class="monthly-pagination fleet-pagination mt-3" aria-label="Monthly driver pagination">
            <ul class="pagination justify-content-end mb-0 flex-wrap">
                <?php if ($page > 1): ?>
                    <?php $paginationQuery['page'] = (string) ($page - 1); ?>
                    <li class="page-item">
                        <a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyDriversResultsSection">Previous</a>
                    </li>
                <?php endif; ?>

                <?php
                $paginationStart = max(1, $page - 2);
                $paginationEnd = min($totalPages, $page + 2);
                ?>

                <?php if ($paginationStart > 1): ?>
                    <?php $paginationQuery['page'] = '1'; ?>
                    <li class="page-item">
                        <a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyDriversResultsSection">1</a>
                    </li>
                    <?php if ($paginationStart > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $paginationStart; $i <= $paginationEnd; $i++): ?>
                    <?php $paginationQuery['page'] = (string) $i; ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyDriversResultsSection">
                            <?= e((string) $i) ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($paginationEnd < $totalPages): ?>
                    <?php if ($paginationEnd < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <?php $paginationQuery['page'] = (string) $totalPages; ?>
                    <li class="page-item">
                        <a class="page-link" href="drivers.php?<?= e(http_build_query($paginationQuery)) ?>#monthlyDriversResultsSection">
                            <?= e((string) $totalPages) ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
