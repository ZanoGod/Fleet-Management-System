<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'drivers';
$pageTitle = 'Drivers';
$pageSummary = 'View total drivers and maintain their availability for trip assignments.';
$pageActions = '<a class="btn btn-accent" href="driver-create.php">Add Driver</a>';
$flash = get_flash();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

$drivers = [];
$stats = [
    'total' => 0,
    'available' => 0,
    'on_trip' => 0,
    'leave_count' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(full_name LIKE ? OR phone_number LIKE ? OR license_no LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'sss';
        array_push($params, $searchValue, $searchValue, $searchValue);
    }

    if ($filters['status'] !== '') {
        $where[] = 'driver_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    $sql = 'SELECT id, full_name, phone_number, license_no, driver_status, note FROM drivers';

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

    $statsResult = $db->query(
        "SELECT
            COUNT(*) AS total,
            SUM(driver_status = 'Available') AS available,
            SUM(driver_status = 'On Trip') AS on_trip,
            SUM(driver_status = 'Leave') AS leave_count
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
        <span>On Trip</span>
        <strong><?= e((string) ($stats['on_trip'] ?? 0)) ?></strong>
        <small>Currently assigned</small>
    </div>
    <div class="card-shell overview-card">
        <span>On Leave</span>
        <strong><?= e((string) ($stats['leave_count'] ?? 0)) ?></strong>
        <small>Temporarily unavailable</small>
    </div>
</section>

<section class="card-shell section-card mb-4">
    <div class="section-title">
        <div>
            <h2>Search Drivers</h2>
            <p>Filter by name, phone number, license number, or status.</p>
        </div>
    </div>

    <form method="get" class="filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Driver name, phone, license">
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

<section class="card-shell section-card">
    <div class="section-title">
        <div>
            <h2>Driver List</h2>
            <p>Keep driver records ready for daily fleet operations.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Phone Number</th>
                    <th>License Number</th>
                    <th>Status</th>
                    <th>Note</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($drivers === []): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted-soft">No drivers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($drivers as $driver): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($driver['full_name']) ?></td>
                            <td><?= e($driver['phone_number']) ?></td>
                            <td><span class="table-pill operator-pill"><?= e($driver['license_no']) ?></span></td>
                            <td><span class="resource-pill <?= e(driver_status_class($driver['driver_status'])) ?>"><?= e($driver['driver_status']) ?></span></td>
                            <td class="note-cell"><?= e($driver['note'] ?: '-') ?></td>
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
