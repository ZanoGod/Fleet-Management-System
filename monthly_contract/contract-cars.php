<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'monthly-contract-cars';
$navigationItems = monthly_contract_navigation();
$sidebarKicker = 'Module';
$sidebarTitle = 'Monthly Contracts';
$sidebarBrandMark = 'MC';
$pageKicker = 'Monthly Contract Module';
$pageTitle = 'Monthly Contract Vehicles';
$pageSummary = 'Maintain vehicles reserved for monthly contracts separately from daily booking vehicles.';
$pageActions = '<a class="btn btn-shell" href="index.php">Daily Booking</a> <a class="btn btn-accent" href="monthly-contract-car-create.php">Add Vehicle</a>';
$pageStyles = ['assets/css/cars.css', 'assets/css/monthly-contracts.css'];
$flash = get_flash();

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];

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
            monthly_cars.car_type LIKE ?
            OR monthly_cars.plate_no LIKE ?
            OR monthly_cars.model_name LIKE ?
            OR EXISTS (
                SELECT 1
                FROM monthly_contracts AS contract_search
                WHERE contract_search.car_id = monthly_cars.id
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
        $where[] = 'monthly_cars.availability_status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    $sql = 'SELECT
                monthly_cars.id,
                monthly_cars.car_type,
                monthly_cars.plate_no,
                monthly_cars.model_name,
                monthly_cars.seat_capacity,
                monthly_cars.availability_status
            FROM monthly_cars';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY monthly_cars.id DESC';
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

    $carAssignments = fetch_monthly_contract_resource_assignments($db, 'car_id');
    $statsResult = $db->query(
        "SELECT
            COUNT(*) AS total,
            SUM(availability_status = 'Available') AS available,
            SUM(availability_status = 'Assigned') AS assigned,
            SUM(availability_status = 'Maintenance') AS maintenance_count
         FROM monthly_cars"
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
        <span>Total Vehicles</span>
        <strong><?= e((string) ($stats['total'] ?? 0)) ?></strong>
        <small>Monthly vehicle records</small>
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
        <span>Maintenance</span>
        <strong><?= e((string) ($stats['maintenance_count'] ?? 0)) ?></strong>
        <small>Unavailable right now</small>
    </div>
</section>

<section class="card-shell section-card mb-4" id="monthlyCarsFiltersSection">
    <div class="section-title">
        <div>
            <h2>Search Monthly Vehicles</h2>
            <p>Filter by vehicle, plate, model, monthly customer, contract number, or status.</p>
        </div>
    </div>

    <form method="get" action="monthly-contract-cars.php#monthlyCarsResultsSection" class="filter-grid" data-preserve-scroll="monthlyCarsFiltersSection">
        <div>
            <label for="search" class="form-label">Search</label>
            <input
                type="text"
                class="form-control"
                id="search"
                name="search"
                value="<?= e($filters['search']) ?>"
                placeholder="Vehicle, plate, model, customer, contract">
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

<section class="card-shell section-card" id="monthlyCarsResultsSection">
    <div class="section-title">
        <div>
            <h2>Monthly Vehicle List</h2>
            <p>Daily booking vehicles are managed separately from this list.</p>
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
                    <th>Contract / Customer</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cars === []): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted-soft">No monthly vehicles found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cars as $car): ?>
                        <?php
                        $assignments = $carAssignments[(int) $car['id']] ?? [];
                        $showAssignmentIndex = count($assignments) > 1;
                        ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) $car['car_type']) ?></td>
                            <td><?= e((string) $car['plate_no']) ?></td>
                            <td><?= e((string) $car['model_name']) ?></td>
                            <td><?= e((string) $car['seat_capacity']) ?></td>
                            <td>
                                <span class="resource-pill <?= e(vehicle_status_class((string) $car['availability_status'])) ?>">
                                    <?= e((string) $car['availability_status']) ?>
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
                                    <a class="btn btn-sm btn-shell" href="monthly-contract-car-edit.php?id=<?= e((string) $car['id']) ?>">Edit</a>
                                    <form
                                        method="post"
                                        action="monthly-contract-car-delete.php?id=<?= e((string) $car['id']) ?>"
                                        onsubmit="return confirm('Delete this monthly vehicle?');">
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
