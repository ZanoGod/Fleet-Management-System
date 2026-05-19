<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'cars';
$pageTitle = 'Cars / Fleets';
$pageSummary = 'View the total cars in your fleet and maintain vehicle master data.';
$pageActions = '<a class="btn btn-accent" href="car-create.php">Add Car</a>';
$flash = get_flash();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

$cars = [];
$stats = [
    'total' => 0,
    'available' => 0,
    'assigned' => 0,
    'maintenance' => 0,
];

if ($db instanceof mysqli) {
    $where = [];
    $types = '';
    $params = [];

    if ($filters['search'] !== '') {
        $where[] = '(car_type LIKE ? OR plate_no LIKE ? OR model_name LIKE ?)';
        $searchValue = '%' . $filters['search'] . '%';
        $types .= 'sss';
        array_push($params, $searchValue, $searchValue, $searchValue);
    }

    if ($filters['status'] !== '') {
        $where[] = 'availability_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    $sql = 'SELECT id, car_type, plate_no, model_name, seat_capacity, availability_status, note FROM cars';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY car_type ASC, plate_no ASC';

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

    $statsResult = $db->query(
        "SELECT
            COUNT(*) AS total,
            SUM(availability_status = 'Available') AS available,
            SUM(availability_status = 'Assigned') AS assigned,
            SUM(availability_status = 'Maintenance') AS maintenance
         FROM cars"
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
        <span>Total Cars</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>All fleet vehicles in the system</small>
    </div>
    <div class="card-shell overview-card">
        <span>Available</span>
        <strong><?= e((string) ($stats['available'] ?? 0)) ?></strong>
        <small>Ready for new assignments</small>
    </div>
    <div class="card-shell overview-card">
        <span>Assigned</span>
        <strong><?= e((string) ($stats['assigned'] ?? 0)) ?></strong>
        <small>Currently attached to trips</small>
    </div>
    <div class="card-shell overview-card">
        <span>Maintenance</span>
        <strong><?= e((string) ($stats['maintenance'] ?? 0)) ?></strong>
        <small>Vehicles needing attention</small>
    </div>
</section>

<section class="card-shell section-card mb-4">
    <div class="section-title">
        <div>
            <h2>Search Fleet</h2>
            <p>Filter by car type, plate number, model, or status.</p>
        </div>
    </div>

    <form method="get" class="filter-grid">
        <div>
            <label for="search" class="form-label">Search</label>
            <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']) ?>" placeholder="Car type, plate number, model">
        </div>
        <div>
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All Statuses</option>
                <?php foreach (car_statuses() as $status): ?>
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
            <h2>Fleet List</h2>
            <p>Use this page to view the total cars and update their status.</p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table data-table">
            <thead>
                <tr>
                    <th>Car Type</th>
                    <th>Plate Number</th>
                    <th>Model</th>
                    <th>Seat Capacity</th>
                    <th>Status</th>
                    <th>Note</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cars === []): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted-soft">No cars found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cars as $car): ?>
                        <tr>
                            <td><span class="table-pill car-pill"><?= e($car['car_type']) ?></span></td>
                            <td><span class="table-pill plate-pill"><?= e($car['plate_no']) ?></span></td>
                            <td class="fw-semibold"><?= e($car['model_name']) ?></td>
                            <td><?= e((string) $car['seat_capacity']) ?></td>
                            <td><span class="resource-pill <?= e(vehicle_status_class($car['availability_status'])) ?>"><?= e($car['availability_status']) ?></span></td>
                            <td class="note-cell"><?= e($car['note'] ?: '-') ?></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <a class="btn btn-sm btn-shell" href="car-edit.php?id=<?= e((string) $car['id']) ?>">Edit</a>
                                    <form method="post" action="car-delete.php?id=<?= e((string) $car['id']) ?>" onsubmit="return confirm('Delete this car?');">
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
