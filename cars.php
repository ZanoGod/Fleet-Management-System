<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$activePage = 'cars';
$pageTitle = 'Fleet Management';
$pageSummary = 'Manage all company vehicles and booking assignments.';
$pageActions = '<a class="btn btn-accent" href="car-create.php">Add Vehicle</a>';
$pageStyles = ['assets/css/cars.css'];

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
            cars.car_type LIKE ?
            OR cars.plate_no LIKE ?
            OR cars.model_name LIKE ?
            OR EXISTS (
                SELECT 1
                FROM bookings AS booking_search
                WHERE booking_search.car_id = cars.id
                  AND booking_search.status IN (\'Pending\', \'Confirm\', \'In Service\')
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

    /*
    |--------------------------------------------------------------------------
    | ACTIVE BOOKING ASSIGNMENTS
    |--------------------------------------------------------------------------
    */

    $carAssignments = fetch_resource_booking_assignments($db, 'car_id');

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

    </form>

</section>

<section class="card-shell section-card" id="carsResultsSection">
    <div class="section-title">
        <div>
            <h2>Fleet List</h2>
            <p>View vehicle availability and active booking schedules.</p>
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

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>