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

    $sql = 'SELECT
                monthly_drivers.id,
                monthly_drivers.full_name,
                monthly_drivers.phone_number,
                monthly_drivers.driver_status
            FROM monthly_drivers';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY monthly_drivers.full_name ASC';
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

    $driverAssignments = fetch_monthly_contract_resource_assignments($db, 'driver_id');
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
    </form>
</section>

<section class="card-shell section-card" id="monthlyDriversResultsSection">
    <div class="section-title">
        <div>
            <h2>Monthly Driver List</h2>
            <p>Daily booking drivers are managed separately from this list.</p>
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
                        <td colspan="7" class="text-center py-5 text-muted-soft">No monthly drivers found.</td>
                    </tr>
                <?php else: ?>
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
                                        action="river_delete.php?id=<?= e((string) $driver['id']) ?>"
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
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
