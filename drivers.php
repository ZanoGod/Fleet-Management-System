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

    $driverAssignments = fetch_resource_booking_assignments($db, 'driver_id');

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
    </form>
</section>

<section class="card-shell section-card" id="driversResultsSection">
    <div class="section-title">
        <div>
            <h2>Driver List</h2>
            <p>Keep driver records ready for daily fleet operations with numbered active bookings.</p>
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
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
